<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Support;

use SchoolPalm\MessageDelivery\Notification\DTO\NotificationDecision;
use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;

/**
 * Result of a notification dispatch.
 *
 * Carries the dispatch status, the original event, the resolved
 * decision and, when delivered, the underlying delivery results.
 */
final class NotificationResult
{
    /**
     * Create a notification result.
     *
     * @param  string                    $status   dispatched | skipped | failed
     * @param  NotificationEvent         $event    Original event
     * @param  NotificationDecision|null $decision Resolved decision
     * @param  array                     $delivery Delivery results keyed by channel
     * @param  string|null               $reason   Skip/failure reason
     */
    public function __construct(
        public readonly string $status,

        public readonly NotificationEvent $event,

        public readonly ?NotificationDecision $decision = null,

        public readonly array $delivery = [],

        public readonly ?string $reason = null,
    ) {}


    /**
     * Create a dispatched result.
     */
    public static function dispatched(
        NotificationEvent $event,
        NotificationDecision $decision,
        array $delivery
    ): self {
        return new self(
            status: 'dispatched',
            event: $event,
            decision: $decision,
            delivery: $delivery,
        );
    }


    /**
     * Create a skipped result.
     */
    public static function skipped(
        NotificationEvent $event,
        ?NotificationDecision $decision = null,
        string $reason = 'No recipients resolved.'
    ): self {
        return new self(
            status: 'skipped',
            event: $event,
            decision: $decision,
            reason: $reason,
        );
    }


    /**
     * Check whether the dispatch was skipped.
     */
    public function wasSkipped(): bool
    {
        return $this->status === 'skipped';
    }


    /**
     * Check whether the dispatch succeeded.
     */
    public function wasDispatched(): bool
    {
        return $this->status === 'dispatched';
    }


    /**
     * Convert the result to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        // Safely extract data from the delivery result objects for serialization
        $deliveryData = array_map(function ($result) {
            if (is_object($result)) {
                if (method_exists($result, 'all')) {
                    return $result->all();
                }
                if (method_exists($result, 'toArray')) {
                    return $result->toArray();
                }
            }

            return $result;
        }, $this->delivery);

        return [
            'status' => $this->status,
            'event' => $this->event->toArray(),
            'decision' => $this->decision?->toArray(),
            'delivery' => $deliveryData,
            'reason' => $this->reason,
        ];
    }
}
