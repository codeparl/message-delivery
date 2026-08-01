<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Services;

use SchoolPalm\MessageDelivery\Contracts\DeliveryRecorder;
use SchoolPalm\MessageDelivery\Contracts\TenantProviderSettings;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Models\MessageDelivery;

/**
 * Database implementation of the DeliveryRecorder contract.
 *
 * This service persists message delivery lifecycle events
 * (queued, sent, failed) to the message_deliveries database
 * table using Eloquent.
 *
 * It maps Message and DeliveryResult data to the model fields
 * and handles the appropriate status transitions.
 *
 * Who uses it:
 * - MessageManager calls recordQueued before dispatching to queue
 * - MessageManager calls recordSent/recordFailed after delivery
 * - Future queue job handlers may also call recordSent/recordFailed
 *
 * What it should NOT do:
 * - NOT send messages or resolve providers
 * - NOT handle queue dispatch
 * - NOT perform logging (AppLogger is used separately)
 * - NOT implement business logic beyond persistence
 */
final class DatabaseDeliveryRecorder implements DeliveryRecorder
{
    /**
     * Create a new DatabaseDeliveryRecorder instance.
     *
     * @param  TenantProviderSettings|null  $settings  Optional tenant settings used to resolve provider names
     */
    public function __construct(
        protected readonly ?TenantProviderSettings $settings = null,
    ) {}


    /**
     * Record a message as queued for delivery.
     *
     * Creates a new MessageDelivery record with:
     * - status: 'queued'
     * - queued_at: current timestamp
     * - channel, provider, recipient, subject from message
     * - tenant_id, school_id from message context
     * - metadata from message data
     */
    public function recordQueued(
        Message $message
    ): MessageDelivery {

        return MessageDelivery::create([
            'channel' => $message->channel,
            'provider' => $this->resolveProvider($message),
            'recipient' => $this->resolveRecipient($message),
            'status' => 'queued',
            'subject' => $message->data['subject'] ?? null,
            'tenant_id' => $message->context('tenant_id'),
            'school_id' => $message->context('school_id'),
            'metadata' => $this->buildMetadata($message),
            'queued_at' => now(),
        ]);
    }


    /**
     * Record a successful delivery.
     *
     * Updates the existing delivery record with:
     * - status: 'sent'
     * - provider: from DeliveryResult
     * - provider_message_id: from DeliveryResult
     * - sent_at: current timestamp
     * - metadata merges result metadata
     *
     * Looks up the record by channel and recipient.
     * If no queued record exists, creates a new one.
     */
    public function recordSent(
        Message $message,
        DeliveryResult $result
    ): MessageDelivery {

        $delivery = $this->findOrNew($message);

        $delivery->fill([
            'status' => 'sent',
            'provider' => $result->provider ?? $message->provider ?? 'unknown',
            'provider_message_id' => $result->providerMessageId,
            'sent_at' => now(),
            'error' => null,
            'metadata' => array_merge(
                $delivery->metadata ?? [],
                $result->metadata,
                $this->buildMetadata($message),
            ),
        ]);

        $delivery->save();

        return $delivery;
    }


    /**
     * Record a failed delivery.
     *
     * Updates the existing delivery record with:
     * - status: 'failed'
     * - error: from DeliveryResult
     * - provider: from DeliveryResult
     * - metadata merges result metadata
     *
     * Looks up the record by channel and recipient.
     * If no queued record exists, creates a new one.
     */
    public function recordFailed(
        Message $message,
        DeliveryResult $result
    ): MessageDelivery {

        $delivery = $this->findOrNew($message);

        $delivery->fill([
            'status' => 'failed',
            'provider' => $result->provider ?? $message->provider ?? 'unknown',
            'error' => $result->error,
            'metadata' => array_merge(
                $delivery->metadata ?? [],
                $result->metadata,
                $this->buildMetadata($message),
            ),
        ]);

        $delivery->save();

        return $delivery;
    }


    /**
     * Resolve a single recipient string from the message.
     *
     * Uses the first recipient when multiple recipients exist.
     *
     * @param  Message  $message
     * @return string
     */
    private function resolveRecipient(Message $message): string
    {
        $recipients = $message->recipients;

        if (empty($recipients)) {
            return 'unknown';
        }

        $recipient = $recipients[0];

        return is_string($recipient)
            ? $recipient
            : (string) json_encode($recipient);
    }


    /**
     * Resolve the provider name for a message.
     *
     * Priority:
     * 1. Explicit provider set on the message
     * 2. Provider resolved from TenantProviderSettings
     * 3. Fallback to 'unknown'
     *
     * @param  Message  $message
     * @return string
     */
    private function resolveProvider(Message $message): string
    {
        if ($message->provider !== null) {
            return $message->provider;
        }

        if ($this->settings !== null) {
            return $this->settings->providerFor($message->channel)
                ?? 'unknown';
        }

        return 'unknown';
    }


    /**
     * Build metadata array from the message.
     *
     * @param  Message  $message
     * @return array<string, mixed>
     */
    private function buildMetadata(Message $message): array
    {
        $metadata = [];

        if ($message->hasView()) {
            $metadata['view'] = $message->view;
        }

        if ($message->hasTemplate()) {
            $metadata['template'] = $message->template;
        }

        if ($message->hasText()) {
            $metadata['has_text'] = true;
        }

        if ($message->priority !== null) {
            $metadata['priority'] = $message->priority;
        }

        if (! empty($message->context)) {
            $metadata['context'] = $message->context;
        }

        return $metadata;
    }


    /**
     * Find an existing delivery record by channel and
     * first recipient, or create a new one.
     *
     * @param  Message  $message
     * @return MessageDelivery
     */
    private function findOrNew(Message $message): MessageDelivery
    {
        $recipient = $this->resolveRecipient($message);

        $delivery = MessageDelivery::where(
            'channel',
            $message->channel
        )->where(
            'recipient',
            $recipient
        )->whereIn(
            'status',
            ['queued', 'processing']
        )->latest()->first();

        if ($delivery !== null) {
            return $delivery;
        }

        return new MessageDelivery([
            'channel' => $message->channel,
            'recipient' => $recipient,
            'tenant_id' => $message->context('tenant_id'),
            'school_id' => $message->context('school_id'),
            'subject' => $message->data['subject'] ?? null,
        ]);
    }
}
