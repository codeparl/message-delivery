<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Contracts;

use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;

interface MessageChannel
{
    /**
     * Get channel identifier.
     *
     * Examples:
     *
     * sms
     * email
     * push
     * whatsapp
     */
    public function name(): string;


    /**
     * Send message through this channel.
     *
     * The provider has already been resolved
     * by ProviderManager.
     */
    public function send(
        Message $message,
        MessageProvider $provider
    ): DeliveryResult;


    /**
     * Determine whether channel supports
     * a given provider.
     *
     * Example:
     *
     * Email channel:
     * - ses
     * - mailgun
     *
     * SMS channel:
     * - egosms
     * - africastalking
     */
    public function supports(
        MessageProvider $provider
    ): bool;
}
