<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Contracts;

use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;

/**
 * Resolves the delivery channels for a notification event.
 *
 * Implementations decide which channels (email, sms, whatsapp,
 * push, in_app) should be used for the notification.
 */
interface ChannelResolver
{
    /**
     * Resolve the channels to deliver through.
     *
     * @return array<int, string>
     */
    public function resolve(
        NotificationEvent $event,
        array $preferences = []
    ): array;
}

