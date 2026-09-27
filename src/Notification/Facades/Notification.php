<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Facades;

use Illuminate\Support\Facades\Facade;
use SchoolPalm\MessageDelivery\Notification\NotificationService;

/**
 * @method static \Illuminate\Database\Eloquent\Collection for(object|string $notifiable, mixed $notifiableId = null)
 * @method static \Illuminate\Database\Eloquent\Collection unread(object|string $notifiable, mixed $notifiableId = null)
 * @method static \Illuminate\Database\Eloquent\Collection read(object|string $notifiable, mixed $notifiableId = null)
 * @method static int unreadCount(object|string $notifiable, mixed $notifiableId = null)
 * @method static \SchoolPalm\MessageDelivery\Models\DatabaseNotification|null find(string $notificationId, object|string $notifiable, mixed $notifiableId = null)
 * @method static bool markAsRead(string $notificationId, object|string $notifiable, mixed $notifiableId = null)
 * @method static bool markAsUnread(string $notificationId, object|string $notifiable, mixed $notifiableId = null)
 * @method static int markAllAsRead(object|string $notifiable, mixed $notifiableId = null)
 * @method static \Illuminate\Database\Eloquent\Builder query(object|string $notifiable, mixed $notifiableId = null)
 *
 * @see \SchoolPalm\MessageDelivery\Notification\NotificationService
 */
final class Notification extends Facade
{
    /**
     * Get the service container binding.
     */
    protected static function getFacadeAccessor(): string
    {
        return NotificationService::class;
    }
}