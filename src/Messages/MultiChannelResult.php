<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Messages;

/**
 * Represents delivery results from multiple channels.
 *
 * This object collects individual DeliveryResult instances
 * keyed by channel name (e.g. 'email', 'sms').
 *
 * Who consumes it:
 * - Module developers calling ->send() or ->queue()
 *   on MultiChannelMessageBuilder.
 *
 * Responsibility:
 * - Aggregate per-channel delivery results
 * - Provide success/failure checks across all channels
 *
 * What it does NOT handle:
 * - Does NOT send messages
 * - Does NOT know about providers or APIs
 * - Does NOT implement delivery logic
 */
final class MultiChannelResult
{
    /**
     * Per-channel delivery results.
     *
     * @var array<string, DeliveryResult>
     */
    private array $results = [];

    /**
     * Add a result for a given channel.
     */
    public function add(
        string $channel,
        DeliveryResult $result
    ): void {
        $this->results[$channel] = $result;
    }

    /**
     * Get all channel results.
     *
     * @return array<string, DeliveryResult>
     */
    public function all(): array
    {
        return $this->results;
    }

    /**
     * Get result for a specific channel.
     *
     * @return DeliveryResult|null
     */
    public function get(
        string $channel
    ): ?DeliveryResult {
        return $this->results[$channel] ?? null;
    }

    /**
     * Determine whether any channel failed.
     */
    public function hasFailures(): bool
    {
        foreach ($this->results as $result) {
            if ($result->isFailed()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether all channels succeeded.
     *
     * Returns true only when every channel
     * has a successful (sent/delivered) status.
     */
    public function isSuccessful(): bool
    {
        if (empty($this->results)) {
            return false;
        }

        foreach ($this->results as $result) {
            if (! $result->isSuccessful()) {
                return false;
            }
        }

        return true;
    }
}
