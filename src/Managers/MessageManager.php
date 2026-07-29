<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Managers;

use SchoolPalm\MessageDelivery\Jobs\SendMessageJob;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\QueuedJobs\Facades\QueuedJobs;

final class MessageManager
{
    /**
     * Create message manager.
     */
    public function __construct(
        protected DeliveryManager $deliveryManager,
    ) {}


    /**
     * Send a message.
     *
     * If queued is true, dispatch through
     * schoolpalm/queued-jobs.
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


        return $this->deliveryManager->deliver(
            $message
        );
    }


    /**
     * Dispatch message to queue.
     */
    protected function queue(
        Message $message
    ): DeliveryResult {

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
}
