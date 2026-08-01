<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Contracts;

use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;
use SchoolPalm\MessageDelivery\Notification\Support\NotificationResult;

/**
 * Contract for the notification orchestration engine.
 *
 * The engine is responsible for coordinating resolvers and
 * delegating message delivery to the MessageDelivery package.
 *
 * Implementations MUST remain orchestration-only and MUST NOT
 * contain business rules.
 */
interface NotificationEngine
{
    /**
     * Dispatch a notification event.
     *
     * The engine resolves recipients, preferences, channels,
     * template, language, priority, schedule and retry policy
     * before delegating delivery to the MessageDelivery package.
     */
    public function dispatch(
        NotificationEvent $event
    ): NotificationResult;
}

