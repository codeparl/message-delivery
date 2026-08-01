<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Resolvers;

use SchoolPalm\MessageDelivery\Notification\Contracts\PriorityResolver;
use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;

/**
 * Default priority resolver.
 *
 * Uses the requested priority when provided; otherwise returns
 * null so the engine falls back to the default priority.
 */
final class NullPriorityResolver implements PriorityResolver
{
    /**
     * Resolve the priority for the event.
     */
    public function resolve(
        NotificationEvent $event
    ): ?string {
        return $event->requestedPriority;
    }
}

