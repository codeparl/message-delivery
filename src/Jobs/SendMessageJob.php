<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Jobs;

use SchoolPalm\MessageDelivery\Managers\DeliveryManager;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\QueuedJobs\Jobs\ContextAwareJob;

final class SendMessageJob extends ContextAwareJob
{
    public function __construct(
        private readonly Message $message
    ) {}


    /**
     * Execute message delivery.
     */
    public function handle(
        DeliveryManager $deliveryManager
    ): void {

        try {

            $result = $deliveryManager->deliver(
                $this->message
            );


            $this->completeResult([
                'message' => $this->message->toArray(),
                'delivery' => $result->toArray(),
            ]);
        } catch (\Throwable $exception) {


            $this->failResult(
                $exception->getMessage()
            );


            throw $exception;
        }
    }
}
