<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Resolvers;

use SchoolPalm\MessageDelivery\Notification\Contracts\RecipientResolver;
use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;
use SchoolPalm\MessageDelivery\Notification\Support\NotificationCollection;

/**
 * Default recipient resolver.
 *
 * Returns an empty collection so the package works without
 * application adapters. The engine skips delivery gracefully
 * when no recipients are resolved.
 */
final class NullRecipientResolver implements RecipientResolver
{
    /**
     * Resolve recipients for the event.
     */
    public function resolve(
        NotificationEvent $event
    ): NotificationCollection {
        return new NotificationCollection();
    }
}

