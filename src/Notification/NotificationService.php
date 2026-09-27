<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use SchoolPalm\MessageDelivery\Models\DatabaseNotification;

final class NotificationService
{
    /**
     * Create the notification service.
     */
    public function __construct()
    {
    }

    /**
     * Get notifications for a notifiable entity.
     *
     * The DatabaseNotification global scope automatically limits
     * the query to the current notification context.
     *
     * @param object|string $notifiable
     * @param mixed|null     $notifiableId
     *
     * @return Collection<int, DatabaseNotification>
     */
    public function for(
        object|string $notifiable,
        mixed $notifiableId = null
    ): Collection {
        [
            $type,
            $id,
        ] = $this->resolveNotifiable(
            $notifiable,
            $notifiableId
        );

        return DatabaseNotification::query()
            ->where('notifiable_type', $type)
            ->where('notifiable_id', (string) $id)
            ->latest()
            ->get();
    }

    /**
     * Get unread notifications for a notifiable entity.
     *
     * The current context scope is automatically applied.
     *
     * @param object|string $notifiable
     * @param mixed|null     $notifiableId
     *
     * @return Collection<int, DatabaseNotification>
     */
    public function unread(
        object|string $notifiable,
        mixed $notifiableId = null
    ): Collection {
        [
            $type,
            $id,
        ] = $this->resolveNotifiable(
            $notifiable,
            $notifiableId
        );

        return DatabaseNotification::query()
            ->where('notifiable_type', $type)
            ->where('notifiable_id', (string) $id)
            ->whereNull('read_at')
            ->latest()
            ->get();
    }

    /**
     * Get read notifications for a notifiable entity.
     *
     * @param object|string $notifiable
     * @param mixed|null     $notifiableId
     *
     * @return Collection<int, DatabaseNotification>
     */
    public function read(
        object|string $notifiable,
        mixed $notifiableId = null
    ): Collection {
        [
            $type,
            $id,
        ] = $this->resolveNotifiable(
            $notifiable,
            $notifiableId
        );

        return DatabaseNotification::query()
            ->where('notifiable_type', $type)
            ->where('notifiable_id', (string) $id)
            ->whereNotNull('read_at')
            ->latest()
            ->get();
    }

    /**
     * Get the unread notification count.
     *
     * The current context scope is automatically applied.
     */
    public function unreadCount(
        object|string $notifiable,
        mixed $notifiableId = null
    ): int {
        [
            $type,
            $id,
        ] = $this->resolveNotifiable(
            $notifiable,
            $notifiableId
        );

        return DatabaseNotification::query()
            ->where('notifiable_type', $type)
            ->where('notifiable_id', (string) $id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Find a notification belonging to a notifiable entity.
     *
     * The current context scope is automatically applied.
     *
     * Returns null when the notification does not exist or belongs
     * to another notification context.
     */
    public function find(
        string $notificationId,
        object|string $notifiable,
        mixed $notifiableId = null
    ): ?DatabaseNotification {
        [
            $type,
            $id,
        ] = $this->resolveNotifiable(
            $notifiable,
            $notifiableId
        );

        return DatabaseNotification::query()
            ->whereKey($notificationId)
            ->where('notifiable_type', $type)
            ->where('notifiable_id', (string) $id)
            ->first();
    }

    /**
     * Mark a notification as read.
     *
     * Because the model has the global context scope, a notification
     * from another context cannot be marked as read through this
     * service.
     */
    public function markAsRead(
        string $notificationId,
        object|string $notifiable,
        mixed $notifiableId = null
    ): bool {
        $notification = $this->find(
            $notificationId,
            $notifiable,
            $notifiableId
        );

        if ($notification === null) {
            return false;
        }

        $notification->markAsRead();

        return true;
    }

    /**
     * Mark a notification as unread.
     *
     * Because the model has the global context scope, a notification
     * from another context cannot be modified through this service.
     */
    public function markAsUnread(
        string $notificationId,
        object|string $notifiable,
        mixed $notifiableId = null
    ): bool {
        $notification = $this->find(
            $notificationId,
            $notifiable,
            $notifiableId
        );

        if ($notification === null) {
            return false;
        }

        $notification->markAsUnread();

        return true;
    }

    /**
     * Mark all notifications for a notifiable entity as read.
     *
     * The current notification context is automatically applied.
     *
     * @return int Number of notifications updated
     */
    public function markAllAsRead(
        object|string $notifiable,
        mixed $notifiableId = null
    ): int {
        [
            $type,
            $id,
        ] = $this->resolveNotifiable(
            $notifiable,
            $notifiableId
        );

        return DatabaseNotification::query()
            ->where('notifiable_type', $type)
            ->where('notifiable_id', (string) $id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);
    }

    /**
     * Build a notification query for a notifiable entity.
     *
     * This is useful when the caller needs additional filtering,
     * pagination, limits, or custom ordering.
     *
     * The global context scope remains active.
     *
     * @param object|string $notifiable
     * @param mixed|null     $notifiableId
     *
     * @return Builder<DatabaseNotification>
     */
    public function query(
        object|string $notifiable,
        mixed $notifiableId = null
    ): Builder {
        [
            $type,
            $id,
        ] = $this->resolveNotifiable(
            $notifiable,
            $notifiableId
        );

        return DatabaseNotification::query()
            ->where('notifiable_type', $type)
            ->where('notifiable_id', (string) $id);
    }

    /**
     * Resolve a notifiable object or explicit type/id pair.
     *
     * @return array{0: string, 1: mixed}
     */
    private function resolveNotifiable(
        object|string $notifiable,
        mixed $notifiableId = null
    ): array {
        /*
         * Eloquent/notifiable object.
         */
        if (is_object($notifiable)) {
            $type = method_exists(
                $notifiable,
                'getMorphClass'
            )
                ? $notifiable->getMorphClass()
                : get_class($notifiable);

            $id = method_exists(
                $notifiable,
                'getKey'
            )
                ? $notifiable->getKey()
                : null;

            if ($id === null) {
                throw new \InvalidArgumentException(
                    'The notifiable object must have a primary key.'
                );
            }

            return [
                $type,
                $id,
            ];
        }

        /*
         * Explicit type + ID.
         *
         * Example:
         *
         * $service->for(
         *     'App\Models\User',
         *     15
         * );
         */
        if ($notifiableId === null) {
            throw new \InvalidArgumentException(
                'A notifiable ID is required when the notifiable '
                . 'type is provided as a string.'
            );
        }

        return [
            $notifiable,
            $notifiableId,
        ];
    }
}