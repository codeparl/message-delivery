<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Eloquent model for the message_deliveries table.
 *
 * This model stores the history of every message delivery
 * attempt made through the MessageDelivery package. Each
 * record tracks the full lifecycle of a single message
 * sent to a single recipient.
 *
 * Lifecycle statuses:
 * - queued:     Message dispatched to queue
 * - processing: Message is being sent by the provider
 * - sent:       Message was accepted by the provider
 * - delivered:  Confirmed delivery (if delivery receipts available)
 * - failed:     Message delivery failed
 *
 * Who uses it:
 * - DatabaseDeliveryRecorder creates and updates records
 * - Administration interfaces query delivery history
 * - Diagnostics and reporting tools
 *
 * What it should NOT do:
 * - NOT send messages or resolve providers
 * - NOT handle queue dispatch
 * - NOT perform logging
 * - NOT implement business logic beyond data representation
 */
final class MessageDelivery extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'message_deliveries';

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
        'tenant_id',
        'school_id',
        'channel',
        'provider',
        'recipient',
        'status',
        'provider_message_id',
        'subject',
        'metadata',
        'error',
        'queued_at',
        'sent_at',
        'delivered_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'metadata' => 'array',
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];


    /**
     * Boot the model and register a creating event
     * to auto-generate UUID primary keys.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $delivery): void {
            if (empty($delivery->id)) {
                $delivery->id = (string) Str::uuid();
            }
        });
    }


    /**
     * Check whether the delivery is in queued status.
     */
    public function isQueued(): bool
    {
        return $this->status === 'queued';
    }


    /**
     * Check whether the delivery was sent successfully.
     */
    public function isSent(): bool
    {
        return $this->status === 'sent';
    }


    /**
     * Check whether the delivery was confirmed delivered.
     */
    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }


    /**
     * Check whether the delivery failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
