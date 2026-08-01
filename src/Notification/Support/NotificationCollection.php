<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Support;

/**
 * Immutable collection of notification recipients.
 *
 * Wraps an iterable recipient list and provides count/isEmpty
 * helpers used by the engine to short-circuit delivery.
 */
final class NotificationCollection
{
    /**
     * Recipients.
     *
     * @var array<int, mixed>
     */
    protected readonly array $items;

    /**
     * Create a notification collection.
     *
     * @param  iterable<int, mixed> $items
     */
    public function __construct(
        iterable $items = []
    ) {
        $this->items = is_array($items)
            ? array_values($items)
            : array_values(iterator_to_array($items));
    }


    /**
     * Get all recipients.
     *
     * @return array<int, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }


    /**
     * Get the first recipient.
     */
    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }


    /**
     * Get the number of recipients.
     */
    public function count(): int
    {
        return count($this->items);
    }


    /**
     * Check whether the collection is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }


    /**
     * Check whether the collection is not empty.
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }
}

