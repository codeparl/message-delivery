<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Resolvers;

use SchoolPalm\MessageDelivery\Notification\Contracts\ScheduleResolver;
use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;

/**
 * Default schedule resolver.
 *
 * Returns null so messages are delivered immediately.
 */
final class NullScheduleResolver implements ScheduleResolver
{
    /**
     * Resolve the delivery schedule for the event.
     *
     * @return \DateInterval|\DateTimeInterface|int|null
     */
    public function resolve(
        NotificationEvent $event
    ): \DateInterval|\DateTimeInterface|int|null {
        return null;
    }
}

