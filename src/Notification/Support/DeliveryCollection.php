<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Support;

use ArrayAccess;
use Countable;

/**
 * Collection wrapper for notification delivery results keyed by channel.
 *
 * @implements ArrayAccess<string, mixed>
 */
final class DeliveryCollection implements ArrayAccess, Countable
{
    /**
     * @param array<string, mixed> $items
     */
    public function __construct(
        protected array $items = []
    ) {}

    /**
     * Get the delivery result for a specific channel.
     */
    public function get(string $channel, mixed $default = null): mixed
    {
        return $this->items[$channel] ?? $default;
    }

    /**
     * Check if a channel result exists.
     */
    public function has(string $channel): bool
    {
        return isset($this->items[$channel]);
    }

    /**
     * Get all raw delivery results.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Convert the collection to an array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_map(function ($result) {
            if (is_object($result) && method_exists($result, 'toArray')) {
                return $result->toArray();
            }

            return $result;
        }, $this->items);
    }

    // ArrayAccess implementation
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get((string) $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    // Countable implementation
    public function count(): int
    {
        return count($this->items);
    }
}
