<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Contracts;

interface NotificationContext
{
    /**
     * Get the identifier used to scope notifications.
     *
     * Returns null when operating outside a notification context.
     */
    public function notificationContext(): string|int|null;
}