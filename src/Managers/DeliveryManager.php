<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Managers;

use SchoolPalm\MessageDelivery\Contracts\MessageChannel;
use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
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
     * Deliver message.
     */
    public function deliver(
        Message $message
    ): DeliveryResult {

        $channel = $this->channelRegistry
            ->resolve(
                $message->channel
            );


        $provider = $this->providerManager
            ->resolve(
                $message
            );


        return $channel->send(
            $message,
            $provider
        );
    }
}
