<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use SchoolPalm\MessageDelivery\Database\Scopes\CurrentContextNotificationScope;

/**
 * Eloquent model for the notifications table.
 *
 * This model stores in-app/database notifications delivered
 * through the In-App notification channel.
 *
 * The optional context_id identifies the application context
 * that owns the notification.
 *
 * Notifications are automatically scoped to the current
 * notification context through CurrentContextNotificationScope.
 *
 * Context resolution itself is delegated to the
 * NotificationContext contract. MessageDelivery does not
 * know whether a context represents a school, branch, tenant,
 * organization, workspace, or another application concept.
 *
 * Responsibilities:
 * - Persist notifications
 * - Store the optional notification context
 * - Automatically scope notification queries
 * - Support polymorphic notifiable relationships
 * - Provide read/unread state management
 *
 * What it should NOT do:
 * - NOT resolve schools or tenants
 * - NOT resolve application-specific context models
 * - NOT resolve providers
 * - NOT send messages
 * - NOT handle queue dispatch
 * - NOT implement application-specific business logic
 */
final class DatabaseNotification extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'notifications';

    /**
     * Notifications use UUID primary keys.
     */
    public $incrementing = false;

    /**
     * The primary key data type.
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'context_id',
        'notifiable_type',
        'notifiable_id',
        'title',
        'body',
        'data',
        'channel',
        'provider',
        'read_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden when serialized.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'notifiable_type',
        'notifiable_id',
    ];

    /**
     * Boot the model.
     *
     * The notification context scope is applied to every normal
     * notification query.
     */
    protected static function booted(): void
    {
        /*
         * Automatically scope notifications to the current
         * application notification context.
         *
         * Examples:
         *
         * context_id = "1"
         *     → WHERE notifications.context_id = '1'
         *
         * context_id = "school-1"
         *     → WHERE notifications.context_id = 'school-1'
         *
         * no active context
         *     → WHERE notifications.context_id IS NULL
         */
        static::addGlobalScope(
            new CurrentContextNotificationScope()
        );

        /*
         * Generate the notification UUID automatically.
         */
        static::creating(function (self $notification): void {
            if (empty($notification->id)) {
                $notification->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the entity that received the notification.
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Determine whether the notification has a context.
     */
    public function hasContext(): bool
    {
        return $this->context_id !== null;
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead(): void
    {
        $this->update([
            'read_at' => now(),
        ]);
    }

    /**
     * Mark the notification as unread.
     */
    public function markAsUnread(): void
    {
        $this->update([
            'read_at' => null,
        ]);
    }

    /**
     * Determine whether the notification has been read.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}