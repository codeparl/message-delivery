<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\InApp\Database;

use RuntimeException;
use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Models\DatabaseNotification;

/**
 * Provider that stores notifications in the database for in-app display.
 *
 * This provider implements the MessageProvider contract and persists
 * notifications to the notifications table. Unlike external API providers,
 * this provider stores data locally for retrieval by the application's
 * UI (dashboard, mobile app, etc.).
 *
 * Flow:
 * 1. Provider receives Message with recipients and text content.
 * 2. Recipients are resolved to notifiable type/id pairs.
 * 3. A DatabaseNotification record is created for each recipient.
 * 4. Provider returns DeliveryResult based on storage outcome.
 *
 * Who uses it:
 * - Created by DatabaseNotificationFactory
 * - Called by InAppChannel::send()
 * - Used indirectly by MessageDelivery via DeliveryManager
 *
 * What it should NOT do:
 * - NOT resolve tenant configuration
 * - NOT send external API requests
 * - NOT modify the Message object
 */
final class DatabaseNotificationProvider implements MessageProvider
{
    /**
     * Create a new DatabaseNotificationProvider instance.
     *
     * @param  array<string, mixed>  $configuration
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
     * Recipients can be:
     *
     * 1. An associative array with 'notifiable_type' and 'notifiable_id':
     *    ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 1]
     *
     * 2. A string identifier (uses configured default notifiable type):
     *    'user-1'
     *
     * @param  Message  $message  The message to store as notification
     * @return DeliveryResult
     */
    public function send(
        Message $message
    ): DeliveryResult {

        try {

            $this->validateConfiguration();

            $title = $message->data['title']
                ?? $message->data['subject']
                ?? 'Notification';

            $body = $message->text ?? '';

            $notificationIds = [];
            $errors = [];

            foreach ($message->recipients as $recipient) {

                try {

                    [$notifiableType, $notifiableId] = $this->resolveRecipient($recipient);

                    $notification = DatabaseNotification::create([
                        'notifiable_type' => $notifiableType,
                        'notifiable_id' => $notifiableId,
                        'title' => $title,
                        'body' => $body,
                        'data' => array_merge(
                            $message->data,
                            [
                                'channel' => $message->channel,
                                'provider' => $this->name(),
                                'context' => $message->context,
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
                        is_string($recipient) ? $recipient : (json_encode($recipient) ?: 'unknown'),
                        $e->getMessage()
                    );
                }
            }

            if (! empty($errors) && empty($notificationIds)) {

                return DeliveryResult::failure(
                    error: implode('; ', $errors),
                    provider: $this->name(),
                    metadata: [
                        'recipient_count' => count($message->recipients),
                    ]
                );
            }

            return DeliveryResult::success(
                provider: $this->name(),
                providerMessageId: $notificationIds[0] ?? null,
                metadata: [
                    'recipient_count' => count($message->recipients),
                    'notification_ids' => $notificationIds,
                    'success_count' => count($notificationIds),
                    'error_count' => count($errors),
                ]
            );

        } catch (\Throwable $exception) {

            return DeliveryResult::failure(
                error: $exception->getMessage(),
                provider: $this->name(),
                metadata: [
                    'recipient_count' => count($message->recipients),
                ]
            );
        }
    }


    /**
     * Check whether the provider has valid configuration.
     *
     * Database notification provider has no required external
     * configuration since it uses the application's own database.
     * Always returns true when the database connection is available.
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
     * Resolve a recipient to notifiable type and ID.
     *
     * @param  string|array  $recipient
     * @return array{0: string, 1: mixed}
     */
    private function resolveRecipient(string|array $recipient): array
    {
        if (is_array($recipient)) {

            $type = $recipient['notifiable_type']
                ?? $recipient['type']
                ?? 'App\Models\User';

            $id = $recipient['notifiable_id']
                ?? $recipient['id']
                ?? throw new RuntimeException(
                    'Recipient array must contain notifiable_type and notifiable_id.'
                );

            return [$type, $id];
        }

        // For backward compatibility, treat simple string IDs
        // with a configurable default model
        $defaultModel = $this->configuration['default_notifiable']
            ?? 'App\Models\User';

        return [$defaultModel, $recipient];
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

