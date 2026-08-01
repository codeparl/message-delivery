<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Contracts;

use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;
use SchoolPalm\MessageDelivery\Notification\Support\NotificationCollection;

/**
 * Resolves the recipients for a notification event.
 *
 * Implementations are supplied by application adapters and MUST
 * return a collection of recipient identifiers (strings or
 * arrays such as notifiable_type/notifiable_id pairs).
 */
interface RecipientResolver
{
    /**
     * Resolve recipients for the event.
     */
    public function resolve(
        NotificationEvent $event
    ): NotificationCollection;
}

