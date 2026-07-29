<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Channels;

use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;

final class EmailChannel extends Channel
{
    /**
     * Get channel name.
     */
    public function name(): string
    {
        return 'email';
    }


    /**
     * Send email message.
     */
    public function send(
        Message $message,
        MessageProvider $provider
    ): DeliveryResult {

        return $provider->send(
            $message
        );
    }
}
