<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Contracts;

use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;
use SchoolPalm\MessageDelivery\Notification\DTO\RetryPolicy;

/**
 * Resolves the retry policy for a notification event.
 *
 * Implementations are supplied by application adapters and may
 * return null to use the default queue retry settings.
 */
interface RetryResolver
{
    /**
     * Resolve the retry policy for the event.
     */
    public function resolve(
        NotificationEvent $event
    ): ?RetryPolicy;
}

