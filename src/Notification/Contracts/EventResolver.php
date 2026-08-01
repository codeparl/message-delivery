<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Contracts;

use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;

/**
 * Resolves additional metadata for a notification event.
 *
 * This resolver may enrich the event with information such as
 * defaults, categories or routing hints. It MUST NOT contain
 * business-specific recipient logic.
 */
interface EventResolver
{
    /**
     * Resolve event metadata.
     *
     * @return array<string, mixed>
     */
    public function resolve(
        NotificationEvent $event
    ): array;
}

