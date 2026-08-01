<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\DTO;

/**
 * Immutable notification execution context.
 *
 * Carries information required by adapters such as tenant_id,
 * school_id, module, and request identifiers.
 */
final class NotificationContext
{
    /**
     * Create a notification context.
     *
     * @param  array<string, mixed> $data
     */
    public function __construct(
        protected readonly array $data = []
    ) {}


    /**
     * Get a context value.
     */
    public function get(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->data[$key] ?? $default;
    }


    /**
     * Check whether a context value exists.
     */
    public function has(
        string $key
    ): bool {
        return array_key_exists($key, $this->data);
    }


    /**
     * Return all context data.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }
}

