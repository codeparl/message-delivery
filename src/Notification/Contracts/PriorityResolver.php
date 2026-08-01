<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Contracts;

use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;

/**
 * Resolves the priority for a notification event.
 *
 * Implementations may return null to use the default priority.
 */
interface PriorityResolver
{
    /**
     * Resolve the priority for the event.
     */
    public function resolve(
        NotificationEvent $event
    ): ?string;
}

