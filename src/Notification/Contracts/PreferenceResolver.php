<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Contracts;

use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;

/**
 * Resolves user/recipient preferences for a notification event.
 *
 * Preferences are channel opt-ins and communication settings.
 * Implementations are supplied by application adapters.
 */
interface PreferenceResolver
{
    /**
     * Resolve preferences for the event.
     *
     * @return array<string, mixed>
     */
    public function resolve(
        NotificationEvent $event
    ): array;
}

