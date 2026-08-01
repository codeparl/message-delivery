<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Resolvers;

use SchoolPalm\MessageDelivery\Notification\Contracts\TemplateResolver;
use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;
use SchoolPalm\MessageDelivery\Templates\Template;

/**
 * Default template resolver.
 *
 * Returns null so the engine sends the raw payload without a
 * template. Applications replace this binding with a resolver
 * that loads templates from their own storage.
 */
final class NullTemplateResolver implements TemplateResolver
{
    /**
     * Resolve the template for the event.
     */
    public function resolve(
        NotificationEvent $event,
        array $channels = [],
        ?string $language = null
    ): ?Template {
        return null;
    }
}

