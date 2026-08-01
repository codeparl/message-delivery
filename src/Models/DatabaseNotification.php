<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * Eloquent model for the notifications table.
 *
 * This model stores in-app/database notifications that can be
 * delivered to any notifiable model (User, Parent, Student, etc.)
 * through the In-App notification channel.
 *
 * Responsibilities:
 * - Persist notifications to the database
 * - Support polymorphic notifiable relationships
 * - Provide read/unread state management
 *
 * What it should NOT do:
 * - NOT send messages or resolve providers
 * - NOT handle queue dispatch
 * - NOT implement business logic beyond data representation
 */
final class DatabaseNotification extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notifications';

    /**
     * Indicates if the IDs are UUIDs (not auto-incrementing).
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
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
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'notifiable_type',
        'notifiable_id',
    ];


    /**
     * Boot the model and register a creating event
     * to auto-generate UUID primary keys.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $notification): void {
            if (empty($notification->id)) {
                $notification->id = (string) Str::uuid();
            }
        });
    }


    /**
     * Get the notifiable entity that the notification belongs to.
     *
     * @return MorphTo
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }


    /**
     * Mark the notification as read.
     */
    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }


    /**
     * Mark the notification as unread.
     */
    public function markAsUnread(): void
    {
        $this->update(['read_at' => null]);
    }


    /**
     * Determine whether the notification has been read.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}

