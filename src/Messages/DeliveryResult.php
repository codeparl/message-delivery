<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Messages;

final class DeliveryResult
{
    public function __construct(
        public readonly string $status,

        public readonly ?string $provider = null,

        public readonly ?string $providerMessageId = null,

        public readonly array $metadata = [],

        public readonly ?string $error = null,
    ) {}


    /**
     * Create successful delivery result.
     */
    public static function success(
        string $provider,
        ?string $providerMessageId = null,
        array $metadata = []
    ): self {

        return new self(
            status: 'sent',
            provider: $provider,
            providerMessageId: $providerMessageId,
            metadata: $metadata
        );
    }


    /**
     * Create queued delivery result.
     */
    public static function queuedResult(
        ?string $jobId = null
    ): self {

        return new self(
            status: 'queued',
            metadata: [
                'job_id' => $jobId
            ]
        );
    }


    /**
     * Create failed delivery result.
     */
    public static function failure(
        string $error,
        ?string $provider = null,
        array $metadata = []
    ): self {

        return new self(
            status: 'failed',
            provider: $provider,
            error: $error,
            metadata: $metadata
        );
    }


    /**
     * Create delivered result.
     */
    public static function deliveredResult(
        string $provider,
        ?string $providerMessageId = null,
        array $metadata = []
    ): self {

        return new self(
            status: 'delivered',
            provider: $provider,
            providerMessageId: $providerMessageId,
            metadata: $metadata
        );
    }


    /**
     * Check successful delivery.
     */
    public function isSuccessful(): bool
    {
        return in_array(
            $this->status,
            [
                'sent',
                'delivered'
            ],
            true
        );
    }


    /**
     * Check failed delivery.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }


    /**
     * Check queued delivery.
     */
    public function isQueued(): bool
    {
        return $this->status === 'queued';
    }


    /**
     * Convert result to array.
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'provider' => $this->provider,
            'provider_message_id' => $this->providerMessageId,
            'metadata' => $this->metadata,
            'error' => $this->error,
        ];
    }
}
