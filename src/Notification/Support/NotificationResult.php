<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Support;

use SchoolPalm\MessageDelivery\Notification\DTO\NotificationDecision;
use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;

/**
 * Result of a notification dispatch.
 */
final class NotificationResult
{
    public readonly DeliveryCollection $delivery;

    /**
     * Create a notification result.
     *
     * @param string $status dispatched | skipped | failed
     * @param NotificationEvent $event Original event
     * @param NotificationDecision|null $decision Resolved decision
     * @param array|DeliveryCollection $delivery Delivery results
     * @param string|null $reason Skip/failure reason
     */
    public function __construct(
        public readonly string $status,

        public readonly NotificationEvent $event,

        public readonly ?NotificationDecision $decision = null,

        array|DeliveryCollection $delivery = [],

        public readonly ?string $reason = null,
    ) {
        $this->delivery = $delivery instanceof DeliveryCollection
            ? $delivery
            : new DeliveryCollection($delivery);
    }

    /**
     * Create a dispatched result.
     */
    public static function dispatched(
        NotificationEvent $event,
        NotificationDecision $decision,
        array|DeliveryCollection $delivery
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

    public function wasSkipped(): bool
    {
        return $this->status === 'skipped';
    }

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
        return [
            'status' => $this->status,
            'event' => $this->event->toArray(),
            'decision' => $this->decision?->toArray(),
            'delivery' => $this->delivery->toArray(),
            'reason' => $this->reason,
        ];
    }
}
