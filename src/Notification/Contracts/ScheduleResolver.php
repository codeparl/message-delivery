<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Contracts;

use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;

/**
 * Resolves the schedule for a notification event.
 *
 * Implementations may return a delay (DateInterval, DateTimeInterface
 * or seconds) or null to deliver immediately.
 */
interface ScheduleResolver
{
    /**
     * Resolve the delivery schedule for the event.
     *
     * @return \DateInterval|\DateTimeInterface|int|null
     */
    public function resolve(
        NotificationEvent $event
    ): \DateInterval|\DateTimeInterface|int|null;
}

