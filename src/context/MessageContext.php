<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Context;

final class MessageContext
{
    /**
     * Create a message execution context.
     *
     * The context carries information required by
     * external adapters such as:
     *
     * - tenant resolution
     * - settings lookup
     * - logging
     * - caching
     * - auditing
     */
    public function __construct(
        protected array $data = []
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
     * Check if context value exists.
     */
    public function has(
        string $key
    ): bool {

        return array_key_exists(
            $key,
            $this->data
        );
    }


    /**
     * Return all context data.
     */
    public function all(): array
    {
        return $this->data;
    }
}
