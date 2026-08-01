<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification;

use SchoolPalm\MessageDelivery\Notification\Contracts\NotificationEngine;
use SchoolPalm\MessageDelivery\Notification\DTO\NotificationDispatch;
use SchoolPalm\MessageDelivery\Notification\Support\NotificationResult;

/**
 * Public entry point for the Notification Engine.
 *
 * Provides a fluent API for dispatching notifications without
 * touching MessageDelivery channel builders directly.
 *
 * Example:
 *
 * Notification::dispatch(
 *     event: 'fee.payment_received',
 *     data: ['amount' => 5000],
 * );
 *
 * Notification::event('student.admitted')
 *     ->data([...])
 *     ->priority('high')
 *     ->dispatch();
 */
final class NotificationManager
{
    /**
     * Create a notification manager.
     *
     * @param  NotificationEngine $engine
     */
    public function __construct(
        protected NotificationEngine $engine
    ) {}


    /**
     * Dispatch a notification event.
     *
     * @param  string               $event
     * @param  array<string, mixed> $data
     * @param  array<string, mixed> $context
     * @param  array<string, mixed> $metadata
     * @param  array<int, string>   $channels
     * @param  string|null          $language
     * @param  string|null          $priority
     * @param  string|null          $template
     */
    public function dispatch(
        string $event,

        array $data = [],

        array $context = [],

        array $metadata = [],

        array $channels = [],

        ?string $language = null,

        ?string $priority = null,

        ?string $template = null,
    ): NotificationResult {

        return $this->event($event)
            ->data($data)
            ->context($context)
            ->metadata($metadata)
            ->channels($channels)
            ->language($language)
            ->priority($priority)
            ->template($template)
            ->dispatch();
    }


    /**
     * Start a fluent notification dispatch.
     *
     * @param  string $event
     */
    public function event(
        string $event
    ): NotificationDispatch {

        return new NotificationDispatch(
            event: $event,
            engine: $this->engine,
        );
    }
}

