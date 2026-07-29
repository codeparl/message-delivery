<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Contracts;

interface ProviderFactory
{
    /**
     * Provider identifier.
     *
     * Example:
     *
     * egosms
     * ses
     * twilio
     */
    public function name(): string;


    /**
     * Channel supported by this provider.
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
     * Create configured provider instance.
     *
     * Configuration comes from:
     *
     * TenantProviderSettings
     *
     * Example:
     *
     * [
     *     'api_key' => 'xxxx',
     *     'sender_id' => 'SCHOOL'
     * ]
     */
    public function create(
        array $configuration = []
    ): MessageProvider;
}
