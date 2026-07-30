<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Managers;

use SchoolPalm\AppLogger\Context\AppContext;
use SchoolPalm\AppLogger\Facades\AppLogger;
use SchoolPalm\MessageDelivery\Contracts\DeliveryRecorder;
use SchoolPalm\MessageDelivery\Jobs\SendMessageJob;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\QueuedJobs\Facades\QueuedJobs;

/**
 * Manages the message delivery lifecycle.
 *
 * This manager orchestrates the delivery flow:
 *
 * Immediate:
 * 1. Record queued delivery (if tracking enabled)
 * 2. Deliver through DeliveryManager
 * 3. Record sent or failed (if tracking enabled)
 * 4. Write AppLogger events (if tracking enabled)
 *
 * Queued:
 * 1. Record queued delivery (if tracking enabled)
 * 2. Dispatch SendMessageJob to queue
 * 3. Write AppLogger queued event (if tracking enabled)
 *
 * Who uses it:
 * - ChannelMessageBuilder::send() and ::queue()
 * - SendMessageJob (for queued deliveries)
 *
 * What it should NOT do:
 * - NOT resolve providers directly
 * - NOT send messages directly
 * - NOT handle HTTP/API communication
 */
final class MessageManager
{
    /**
     * Create message manager.
     *
     * @param  DeliveryManager    $deliveryManager  Core delivery engine
     * @param  DeliveryRecorder   $deliveryRecorder Delivery persistence
     * @param  array<string, mixed>  $config         Package configuration
     */
    public function __construct(
        protected DeliveryManager $deliveryManager,

        protected DeliveryRecorder $deliveryRecorder,

        protected array $config = [],
    ) {}


    /**
     * Send a message.
     *
     * If queued is true, dispatch through
     * schoolpalm/queued-jobs.
     *
     * If delivery tracking is enabled in config,
     * delivery lifecycle events are recorded and
     * operational logs are written via AppLogger.
     */
    public function send(
        Message $message,
        bool $queued = false,
    ): DeliveryResult {

        if ($queued) {

            return $this->queue(
                $message
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Track: Record queued delivery
        |--------------------------------------------------------------------------
        */

        if ($this->trackingEnabled()) {

            $this->deliveryRecorder->recordQueued(
                $message
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Deliver
        |--------------------------------------------------------------------------
        */

        $deliveryResult = $this->deliveryManager->deliver(
            $message
        );


        /*
        |--------------------------------------------------------------------------
        | Track: Record result and log
        |--------------------------------------------------------------------------
        */

        if ($this->trackingEnabled()) {

            if ($deliveryResult->isSuccessful()) {

                $this->deliveryRecorder->recordSent(
                    $message,
                    $deliveryResult
                );

                $this->logSent(
                    $message,
                    $deliveryResult
                );

            } elseif ($deliveryResult->isFailed()) {

                $this->deliveryRecorder->recordFailed(
                    $message,
                    $deliveryResult
                );

                $this->logFailed(
                    $message,
                    $deliveryResult
                );
            }
        }


        return $deliveryResult;
    }


    /**
     * Dispatch message to queue.
     *
     * Records a queued delivery and dispatches the
     * SendMessageJob to the queue via queued-jobs.
     */
    protected function queue(
        Message $message
    ): DeliveryResult {

        /*
        |--------------------------------------------------------------------------
        | Track: Record queued delivery
        |--------------------------------------------------------------------------
        */

        $delivery = null;

        if ($this->trackingEnabled()) {

            $delivery = $this->deliveryRecorder->recordQueued(
                $message
            );

            $this->logQueued(
                $message,
                $delivery
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Create queue job builder
        |--------------------------------------------------------------------------
        |
        | QueuedJobs::job() returns JobBuilder.
        | Context must be applied before prepare().
        |
        */

        $builder = QueuedJobs::job(
            new SendMessageJob($message)
        );


        /*
        |--------------------------------------------------------------------------
        | Context propagation
        |--------------------------------------------------------------------------
        |
        | queued-jobs automatically captures current context.
        |
        | Explicit context supplied through:
        |
        | MessageDelivery::withContext()
        |
        | is merged and takes priority.
        |
        */

        if (! empty($message->context)) {

            $builder->withContext(
                $message->context
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Prepare queue job
        |--------------------------------------------------------------------------
        |
        | Converts JobBuilder into PendingJob.
        | Queue options are applied after this step.
        |
        */

        $pending = $builder->prepare();


        /*
        |--------------------------------------------------------------------------
        | Queue options forwarding
        |--------------------------------------------------------------------------
        */

        $options = $message->queueOptions;


        if ($options !== null) {


            if ($options->hasConnection()) {

                $pending->onConnection(
                    $options->connection
                );
            }


            if ($options->hasQueue()) {

                $pending->onQueue(
                    $options->queue
                );
            }


            if ($options->hasDelay()) {

                $pending->delay(
                    $options->delay
                );
            }


            if ($options->hasTries()) {

                $pending->tries(
                    $options->tries
                );
            }


            if ($options->hasTimeout()) {

                $pending->timeout(
                    $options->timeout
                );
            }


            if ($options->backoff !== null) {

                $pending->backoff(
                    $options->backoff
                );
            }


            if ($options->afterCommit) {

                $pending->afterCommit();
            }


            /*
            |--------------------------------------------------------------------------
            | Job middleware
            |--------------------------------------------------------------------------
            */

            if (! empty($options->middleware)) {

                $pending->middleware(
                    $options->middleware
                );
            }
        }


        $pending->dispatch();


        return DeliveryResult::queuedResult();
    }


    /**
     * Check whether delivery tracking is enabled.
     *
     * @return bool
     */
    private function trackingEnabled(): bool
    {
        return (bool) ($this->config['delivery_tracking']
            ?? true);
    }


    /*
    |--------------------------------------------------------------------------
    | AppLogger Events
    |--------------------------------------------------------------------------
    */


    /**
     * Log queued delivery event.
     *
     * @param  Message          $message
     * @param  mixed            $delivery
     */
    private function logQueued(
        Message $message,
        $delivery
    ): void {

        AppLogger::info(
            'message_delivery.queued',

            AppContext::make([
                'tenant_id' => $message->context('tenant_id'),
                'school_id' => $message->context('school_id'),
                'module' => 'message-delivery',
            ]),

            [
                'delivery_id' => $delivery->id ?? null,
                'channel' => $message->channel,
                'provider' => $message->provider ?? 'not_resolved',
                'recipient' => $this->firstRecipient($message),
            ]
        );
    }


    /**
     * Log successful delivery event.
     *
     * @param  Message         $message
     * @param  DeliveryResult  $result
     */
    private function logSent(
        Message $message,
        DeliveryResult $result
    ): void {

        AppLogger::info(
            'message_delivery.sent',

            AppContext::make([
                'tenant_id' => $message->context('tenant_id'),
                'school_id' => $message->context('school_id'),
                'module' => 'message-delivery',
            ]),

            [
                'delivery_id' => null,
                'channel' => $message->channel,
                'provider' => $result->provider ?? $message->provider ?? 'unknown',
                'provider_message_id' => $result->providerMessageId,
                'recipient' => $this->firstRecipient($message),
            ]
        );
    }


    /**
     * Log failed delivery event.
     *
     * @param  Message         $message
     * @param  DeliveryResult  $result
     */
    private function logFailed(
        Message $message,
        DeliveryResult $result
    ): void {

        AppLogger::error(
            'message_delivery.failed',

            AppContext::make([
                'tenant_id' => $message->context('tenant_id'),
                'school_id' => $message->context('school_id'),
                'module' => 'message-delivery',
            ]),

            [
                'delivery_id' => null,
                'channel' => $message->channel,
                'provider' => $result->provider ?? $message->provider ?? 'unknown',
                'recipient' => $this->firstRecipient($message),
                'error' => $result->error,
            ]
        );
    }


    /**
     * Get the first recipient string from the message.
     *
     * @param  Message  $message
     * @return string
     */
    private function firstRecipient(Message $message): string
    {
        $recipients = $message->recipients;

        if (empty($recipients)) {
            return 'unknown';
        }

        $recipient = $recipients[0];

        return is_string($recipient)
            ? $recipient
            : (string) json_encode($recipient);
    }
}
