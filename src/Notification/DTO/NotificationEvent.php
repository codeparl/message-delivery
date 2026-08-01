<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\DTO;

/**
 * Immutable notification event.
 *
 * Represents a single notification request originating from a
 * business module. Fields marked as "requested" are hints only;
 * the engine decides whether to use them.
 */
final class NotificationEvent
{
    /**
     * Create a notification event.
     *
     * @param  string               $event             Event name (e.g. fee.payment_received)
     * @param  array<string, mixed> $data              Payload data
     * @param  array<string, mixed> $context           Execution context (tenant, module, etc.)
     * @param  array<string, mixed> $metadata          Additional metadata
     * @param  array<int, string>   $requestedChannels Requested channels hint
     * @param  string|null          $requestedLanguage Requested language hint
     * @param  string|null          $requestedPriority Requested priority hint
     * @param  string|null          $requestedTemplate Requested template hint
     */
    public function __construct(
        public readonly string $event,

        public readonly array $data = [],

        public readonly array $context = [],

        public readonly array $metadata = [],

        public readonly array $requestedChannels = [],

        public readonly ?string $requestedLanguage = null,

        public readonly ?string $requestedPriority = null,

        public readonly ?string $requestedTemplate = null,
    ) {}


    /**
     * Get a data value.
     */
    public function data(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->data[$key] ?? $default;
    }


    /**
     * Get a context value.
     */
    public function context(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->context[$key] ?? $default;
    }


    /**
     * Get a metadata value.
     */
    public function metadata(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->metadata[$key] ?? $default;
    }


    /**
     * Convert event to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'event' => $this->event,
            'data' => $this->data,
            'context' => $this->context,
            'metadata' => $this->metadata,
            'requested_channels' => $this->requestedChannels,
            'requested_language' => $this->requestedLanguage,
            'requested_priority' => $this->requestedPriority,
            'requested_template' => $this->requestedTemplate,
        ];
    }
}

