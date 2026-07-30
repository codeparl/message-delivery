<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Channels;

use SchoolPalm\MessageDelivery\Contracts\MessageChannel;
use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use InvalidArgumentException;

abstract class Channel implements MessageChannel
{
    /**
     * Send message.
     */
    abstract public function send(
        Message $message,
        MessageProvider $provider
    ): DeliveryResult;


    /**
     * Check provider compatibility.
     */
    public function supports(
        MessageProvider $provider
    ): bool {

        return $provider->channel() === $this->name();
    }


    /**
     * Ensure provider supports channel.
     */
    protected function validateProvider(
        MessageProvider $provider
    ): void {

        if (! $this->supports($provider)) {

            throw new InvalidArgumentException(
                "Provider [{$provider->name()}] does not support channel [{$this->name()}]."
            );
        }
    }
}
