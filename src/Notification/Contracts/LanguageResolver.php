<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Contracts;

use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;

/**
 * Resolves the language for a notification event.
 *
 * Implementations are supplied by application adapters and may
 * return null to use the default language.
 */
interface LanguageResolver
{
    /**
     * Resolve the language code for the event.
     */
    public function resolve(
        NotificationEvent $event
    ): ?string;
}

