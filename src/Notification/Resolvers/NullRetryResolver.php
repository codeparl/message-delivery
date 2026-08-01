<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Resolvers;

use SchoolPalm\MessageDelivery\Notification\Contracts\RetryResolver;
use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;
use SchoolPalm\MessageDelivery\Notification\DTO\RetryPolicy;

/**
 * Default retry resolver.
 *
 * Returns null so the default queue retry settings apply.
 */
final class NullRetryResolver implements RetryResolver
{
    /**
     * Resolve the retry policy for the event.
     */
    public function resolve(
        NotificationEvent $event
    ): ?RetryPolicy {
        return null;
    }
}

