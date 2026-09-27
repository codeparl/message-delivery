<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\InApp\Database;

use RuntimeException;
use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Models\DatabaseNotification;
use SchoolPalm\MessageDelivery\Templates\VariableResolver;

/**
 * Provider that stores notifications in the database for in-app display.
 *
 * This provider implements the MessageProvider contract and persists
 * notifications to the notifications table.
 *
 * Context handling:
 *
 * The notification context is supplied by the Message.
 *
 * Resolution order:
 *
 * 1. context_id
 * 2. school_id
 * 3. null (general notification)
 *
 * MessageDelivery does not interpret what the context represents.
 * The host/application is responsible for supplying the context.
 *
 * Flow:
 *
 * 1. Provider receives Message with recipients and content.
 * 2. Notification context is resolved from the Message.
 * 3. Recipients are resolved to notifiable type/id pairs.
 * 4. A DatabaseNotification record is created for each recipient.
 * 5. The context_id is persisted with the notification.
 * 6. Provider returns DeliveryResult based on storage outcome.
 *
 * What it should NOT do:
 *
 * - NOT resolve tenant configuration.
 * - NOT resolve the current application context.
 * - NOT resolve School or Tenant models.
 * - NOT send external API requests.
 * - NOT modify the Message object.
 */
final class DatabaseNotificationProvider implements MessageProvider
{
    /**
     * Create a new DatabaseNotificationProvider instance.
     *
     * @param array<string, mixed> $configuration
     */
    public function __construct(
        protected readonly array $configuration
    ) {}

    /**
     * Get the provider identifier.
     */
    public function name(): string
    {
        return 'database-notifications';
    }

    /**
     * Get the channel supported by this provider.
     */
    public function channel(): string
    {
        return 'in_app';
    }

    /**
     * Send/store a notification in the database.
     *
     * For each recipient, a DatabaseNotification record is created.
     *
     * Notification context is taken from the Message:
     *
     *     context_id
     *          ↓
     *     school_id
     *          ↓
     *     null
     *
     * The provider does not resolve or interpret the context.
     *
     * Recipients can be:
     *
     * 1. An Eloquent model/notifiable instance.
     *
     * 2. An associative array with:
     *    ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 1]
     *
     * 3. A string identifier using the configured default notifiable type.
     *
     * @param Message $message The message to store as notification
     *
     * @return DeliveryResult
     */
    public function send(
        Message $message
    ): DeliveryResult {
        try {
            $this->validateConfiguration();

            /*
             * Resolve the notification context from the Message.
             *
             * Message::notificationContext() applies the following
             * precedence:
             *
             *     context_id → school_id → null
             *
             * This value is captured once because the same notification
             * context applies to every recipient of this Message.
             */
            $notificationContext = $message->notificationContext();

            /*
             * Priority:
             *
             * subject → title → fallback "Notification"
             */
            $rawTitle = $message->data['subject']
                ?? $message->data['title']
                ?? 'Notification';

            $rawBody = $message->text ?? '';

            /*
             * Resolve template variables against message data.
             */
            $resolver = new VariableResolver();

            if (
                method_exists($message, 'hasTemplate')
                && $message->hasTemplate()
                && $message->template->hasSubject()
            ) {
                $title = $message->template->renderSubject(
                    $message->data
                );
            } else {
                $title = $resolver->resolve(
                    $rawTitle,
                    $message->data
                );
            }

            $body = $resolver->resolve(
                $rawBody,
                $message->data
            );

            /*
             * Remove redundant recipient payloads from persisted
             * notification data.
             */
            $cleanedData = $message->data;

            unset(
                $cleanedData['recipient'],
                $cleanedData['recipients']
            );

            $notificationIds = [];
            $errors = [];

            foreach ($message->recipients as $recipient) {
                try {
                    [
                        $notifiableType,
                        $notifiableId,
                    ] = $this->resolveRecipient($recipient);

                    /*
                     * Persist the notification context.
                     *
                     * MessageDelivery stores the identifier as a string
                     * because context identifiers are intentionally
                     * application-agnostic.
                     *
                     * null means this is a general/context-free
                     * notification.
                     */
                    $contextId = $notificationContext !== null
                        ? (string) $notificationContext
                        : null;

                    $notification = DatabaseNotification::create([
                        'context_id' => $contextId,

                        'notifiable_type' => $notifiableType,
                        'notifiable_id' => $notifiableId,

                        'title' => $title,
                        'body' => $body,

                        'data' => array_merge(
                            $cleanedData,
                            [
                                'channel' => $message->channel,
                                'provider' => $this->name(),
                                'priority' => $message->priority,
                            ]
                        ),

                        'channel' => $message->channel,
                        'provider' => $this->name(),
                    ]);

                    $notificationIds[] = $notification->id;
                } catch (\Throwable $e) {
                    $errors[] = sprintf(
                        'Failed to store notification for %s: %s',
                        is_string($recipient)
                            ? $recipient
                            : (
                                json_encode($recipient)
                                ?: 'unknown'
                            ),
                        $e->getMessage()
                    );
                }
            }

            /*
             * All recipients failed.
             */
            if (
                ! empty($errors)
                && empty($notificationIds)
            ) {
                return DeliveryResult::failure(
                    error: implode('; ', $errors),
                    provider: $this->name(),
                    metadata: [
                        'recipient_count' => count(
                            $message->recipients
                        ),
                        'context_id' => $notificationContext,
                    ]
                );
            }

            /*
             * At least one notification was successfully stored.
             */
            return DeliveryResult::success(
                provider: $this->name(),
                providerMessageId: $notificationIds[0] ?? null,
                metadata: [
                    'recipient_count' => count(
                        $message->recipients
                    ),
                    'notification_ids' => $notificationIds,
                    'success_count' => count($notificationIds),
                    'error_count' => count($errors),
                    'context_id' => $notificationContext,
                ]
            );
        } catch (\Throwable $exception) {
            return DeliveryResult::failure(
                error: $exception->getMessage(),
                provider: $this->name(),
                metadata: [
                    'recipient_count' => count(
                        $message->recipients
                    ),
                ]
            );
        }
    }

    /**
     * Check whether the provider has valid configuration.
     *
     * Database notifications use the application's own database,
     * so no external configuration is required.
     */
    public function configured(): bool
    {
        return true;
    }

    /**
     * Get provider metadata.
     *
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return [
            'name' => $this->name(),
            'label' => 'Database Notifications',
            'channel' => $this->channel(),
            'capabilities' => [
                'read_status',
                'unread_count',
                'metadata',
            ],
        ];
    }

    /**
     * Resolve a recipient to a notifiable type and ID pair.
     *
     * Supports:
     *
     * 1. Eloquent model / notifiable instances:
     *    extracts the morph class and primary key.
     *
     * 2. Associative arrays:
     *    - notifiable_type
     *    - notifiable_id
     *
     *    The aliases type/id and user_id are also supported.
     *
     * 3. Plain string identifiers:
     *    uses the configured default notifiable type.
     *
     * @param object|string|array $recipient
     *
     * @return array{0: string, 1: mixed}
     */
    private function resolveRecipient(
        object|string|array $recipient
    ): array {
        /*
         * If the recipient is a JSON-serialized string,
         * decode it first.
         */
        if (is_string($recipient)) {
            $decoded = json_decode(
                $recipient,
                true
            );

            if (
                json_last_error() === JSON_ERROR_NONE
                && is_array($decoded)
            ) {
                $recipient = $decoded;
            }
        }

        /*
         * 1. Eloquent model / notifiable instance.
         */
        if (is_object($recipient)) {
            $type = method_exists(
                $recipient,
                'getMorphClass'
            )
                ? $recipient->getMorphClass()
                : get_class($recipient);

            $id = method_exists(
                $recipient,
                'getKey'
            )
                ? $recipient->getKey()
                : (
                    $recipient->id
                    ?? throw new RuntimeException(
                        'Recipient object must resolve to a primary key.'
                    )
                );

            return [
                $type,
                $id,
            ];
        }

        /*
         * 2. Associative array.
         */
        if (is_array($recipient)) {
            $type = $recipient['notifiable_type']
                ?? $recipient['type']
                ?? 'App\Models\User';

            $id = $recipient['notifiable_id']
                ?? $recipient['id']
                ?? $recipient['user_id']
                ?? throw new RuntimeException(
                    'Recipient array must contain '
                    . 'notifiable_type and notifiable_id/id.'
                );

            return [
                $type,
                $id,
            ];
        }

        /*
         * 3. Plain string identifier.
         *
         * Backward-compatible behaviour.
         */
        $defaultModel = $this->configuration[
            'default_notifiable'
        ] ?? 'App\Models\User';

        return [
            $defaultModel,
            $recipient,
        ];
    }

    /**
     * Validate provider configuration before sending.
     *
     * @throws RuntimeException When configuration is invalid
     */
    protected function validateConfiguration(): void
    {
        if (! $this->configured()) {
            throw new RuntimeException(
                'Database notification provider is not properly configured.'
            );
        }
    }
}