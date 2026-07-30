<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\Sms\EgoSms;

use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Contracts\ProviderFactory;

final class EgoSmsFactory implements ProviderFactory
{
    /**
     * Get provider identifier.
     *
     * This value is stored in tenant provider settings.
     *
     * Example:
     *
     * [
     *     'channel' => 'sms',
     *     'provider' => 'egosms'
     * ]
     */
    public function name(): string
    {
        return 'egosms';
    }


    /**
     * Get supported channel.
     */
    public function channel(): string
    {
        return 'sms';
    }


    /**
     * Create configured EgoSMS provider.
     *
     * Configuration is supplied by
     * TenantProviderSettings.
     *
     * Example:
     *
     * [
     *     'api_url' => 'https://api.example.com/send',
     *     'username' => 'account',
     *     'password' => 'secret',
     *     'sender_id' => 'SCHOOL'
     * ]
     */
    public function create(
        array $configuration = []
    ): MessageProvider {

        return new EgoSmsProvider(
            configuration: $configuration
        );
    }
}
