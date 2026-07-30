<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Managers;

use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Registry\ChannelRegistry;

final class DeliveryManager
{
    public function __construct(
        protected ChannelRegistry $channelRegistry,

        protected ProviderManager $providerManager,
    ) {}


    /**
     * Deliver a message immediately.
     *
     * Resolves channel and provider then
     * delegates sending responsibility.
     */
    public function deliver(
        Message $message
    ): DeliveryResult {

        $channel = $this->channelRegistry->resolve(
            $message->channel
        );


        $provider = $this->providerManager->resolve(
            $message
        );


        return $channel->send(
            $message,
            $provider
        );
    }


    /**
     * Check whether a channel exists.
     */
    public function supports(
        string $channel
    ): bool {

        return $this->channelRegistry->has(
            $channel
        );
    }


    /**
     * Get available channels.
     */
    public function channels(): array
    {
        return $this->channelRegistry->all();
    }
}
