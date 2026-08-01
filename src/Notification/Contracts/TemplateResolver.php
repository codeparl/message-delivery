<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Contracts;

use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;
use SchoolPalm\MessageDelivery\Templates\Template;

/**
 * Resolves the message template for a notification event.
 *
 * Implementations are supplied by application adapters and may
 * return null when no template is available.
 */
interface TemplateResolver
{
    /**
     * Resolve the template for the event.
     */
    public function resolve(
        NotificationEvent $event,
        array $channels = [],
        ?string $language = null
    ): ?Template;
}

