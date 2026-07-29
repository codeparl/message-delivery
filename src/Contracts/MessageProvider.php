<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Contracts;

use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;

interface MessageProvider
{
    /**
     * Get provider identifier.
     *
     * Example:
     *
     * ses
     * mailgun
     * egosms
     * twilio
     */
    public function name(): string;


    /**
     * Get supported channel.
     *
     * Example:
     *
     * email
     * sms
     * push
     * whatsapp
     */
    public function channel(): string;


    /**
     * Send message.
     */
    public function send(
        Message $message
    ): DeliveryResult;


    /**
     * Check whether provider is configured.
     *
     * Used when resolving tenant providers.
     */
    public function configured(): bool;


    /**
     * Get provider metadata.
     *
     * Useful for:
     *
     * - dashboard display
     * - provider discovery
     * - logging
     */
    public function metadata(): array;
}
