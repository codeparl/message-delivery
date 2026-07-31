<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Channels;

use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;

final class PushChannel extends Channel
{
    /**
     * Get channel name.
     */
    public function name(): string
    {
        return 'push';
    }


    /**
     * Send push notification through resolved provider.
     *
     * @param Message $message
     * @param MessageProvider $provider
     *
     * @return DeliveryResult
     */
    public function send(
        Message $message,
        MessageProvider $provider
    ): DeliveryResult {

        $this->validateProvider($provider);

        return $provider->send(
            $message
        );
    }
}
