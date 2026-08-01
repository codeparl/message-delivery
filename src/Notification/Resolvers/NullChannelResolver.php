<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Resolvers;

use SchoolPalm\MessageDelivery\Notification\Contracts\ChannelResolver;
use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;

/**
 * Default channel resolver.
 *
 * Uses the requested channels when provided; otherwise falls back
 * to the configured default channel.
 */
final class NullChannelResolver implements ChannelResolver
{
    /**
     * Create a channel resolver.
     *
     * @param  string $defaultChannel
     */
    public function __construct(
        protected readonly string $defaultChannel
    ) {}


    /**
     * Resolve the channels to deliver through.
     *
     * @return array<int, string>
     */
    public function resolve(
        NotificationEvent $event,
        array $preferences = []
    ): array {
        if (! empty($event->requestedChannels)) {
            return $event->requestedChannels;
        }

        return [$this->defaultChannel];
    }
}

