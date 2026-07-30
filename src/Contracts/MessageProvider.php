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
     * egosms
     * ses
     * mailgun
     * firebase
     */
    public function name(): string;


    /**
     * Get supported channel.
     *
     * Example:
     *
     * sms
     * email
     * push
     * whatsapp
     */
    public function channel(): string;


    /**
     * Send message through provider.
     *
     * The provider is responsible for:
     *
     * - API communication
     * - authentication
     * - request formatting
     * - response handling
     *
     * It must return a DeliveryResult.
     */
    public function send(
        Message $message
    ): DeliveryResult;


    /**
     * Determine whether provider
     * has valid configuration.
     *
     * Example:
     *
     * SMS provider:
     * - api_url
     * - username
     * - password
     *
     * Email provider:
     * - api_key
     * - region
     */
    public function configured(): bool;


    /**
     * Get provider metadata.
     *
     * Used by:
     *
     * - provider discovery
     * - admin settings UI
     * - logging
     * - diagnostics
     *
     * Example:
     *
     * [
     *     'name' => 'egosms',
     *     'label' => 'EgoSMS',
     *     'channel' => 'sms',
     *     'capabilities' => [
     *          'unicode',
     *          'delivery_reports'
     *     ]
     * ]
     */
    public function metadata(): array;
}
