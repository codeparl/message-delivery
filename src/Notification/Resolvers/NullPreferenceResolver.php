<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Resolvers;

use SchoolPalm\MessageDelivery\Notification\Contracts\PreferenceResolver;
use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;

/**
 * Default preference resolver.
 *
 * Returns no preferences so all channels are considered.
 */
final class NullPreferenceResolver implements PreferenceResolver
{
    /**
     * Resolve preferences for the event.
     *
     * @return array<string, mixed>
     */
    public function resolve(
        NotificationEvent $event
    ): array {
        return [];
    }
}

