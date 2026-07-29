<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Contracts;

use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Contracts\MessageProvider;

interface MessageChannel
{
    /**
     * Get channel name.
     *
     * Example:
     * sms
     * email
     * push
     * whatsapp
     */
    public function name(): string;


    /**
     * Send message through provider.
     */
    public function send(
        Message $message,
        MessageProvider $provider
    ): DeliveryResult;
}
