<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Resolvers;

use SchoolPalm\MessageDelivery\Notification\Contracts\EventResolver;
use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;

/**
 * Default event resolver.
 *
 * Returns no additional metadata. Applications replace this
 * binding with their own resolver when needed.
 */
final class NullEventResolver implements EventResolver
{
    /**
     * Resolve event metadata.
     *
     * @return array<string, mixed>
     */
    public function resolve(
        NotificationEvent $event
    ): array {
        return [];
    }
}

