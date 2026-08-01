<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for the Notification Engine.
 *
 * @method static \SchoolPalm\MessageDelivery\Notification\Support\NotificationResult dispatch(string $event, array $data = [], array $context = [], array $metadata = [], array $channels = [], ?string $language = null, ?string $priority = null, ?string $template = null)
 * @method static \SchoolPalm\MessageDelivery\Notification\DTO\NotificationDispatch event(string $event)
 *
 * @see \SchoolPalm\MessageDelivery\Notification\NotificationManager
 */
class Notification extends Facade
{
    /**
     * Get the facade accessor.
     */
    protected static function getFacadeAccessor()
    {
        return 'notification';
    }
}

