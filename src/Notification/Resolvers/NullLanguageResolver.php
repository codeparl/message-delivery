<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Resolvers;

use SchoolPalm\MessageDelivery\Notification\Contracts\LanguageResolver;
use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;

/**
 * Default language resolver.
 *
 * Uses the requested language when provided; otherwise returns
 * null so the engine falls back to the default language.
 */
final class NullLanguageResolver implements LanguageResolver
{
    /**
     * Resolve the language code for the event.
     */
    public function resolve(
        NotificationEvent $event
    ): ?string {
        return $event->requestedLanguage;
    }
}

