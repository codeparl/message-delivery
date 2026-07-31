<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\Sms\Twilio;

use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Contracts\ProviderFactory;

/**
 * Factory for creating TwilioSmsProvider instances.
 *
 * This factory implements the ProviderFactory contract and is
 * registered with the ProviderRegistry to create configured
 * TwilioSmsProvider instances.
 *
 * Usage:
 * - The factory is registered with ProviderRegistry under
 *   channel 'sms' with name 'twilio-sms'.
 * - When ProviderManager resolves a provider for a message,
 *   it calls create() with the configuration array.
 *
 * Responsibilities:
 * - ONLY creates TwilioSmsProvider instances.
 * - Does NOT send SMS messages.
 * - Does NOT access settings or resolve tenants.
 * - Does NOT validate configuration (delegated to provider).
 */
final class TwilioFactory implements ProviderFactory
{
    /**
     * Get the provider identifier.
     *
     * This value is used by ProviderRegistry and stored
     * in tenant provider settings.
     *
     * Example:
     *
     * [
     *     'channel' => 'sms',
     *     'provider' => 'twilio-sms'
     * ]
     */
    public function name(): string
    {
        return 'twilio-sms';
    }


    /**
     * Get the channel supported by this provider.
     */
    public function channel(): string
    {
        return 'sms';
    }


    /**
     * Create a configured TwilioSmsProvider instance.
     *
     * Configuration is supplied by TenantProviderSettings.
     *
     * Expected configuration:
     *
     * [
     *     'sid' => 'ACxxxxxxxxxx',
     *     'token' => 'xxxxxxxxxxxx',
     *     'from' => '+1234567890',
     * ]
     *
     * @param  array<string, mixed>  $configuration
     * @return MessageProvider
     */
    public function create(
        array $configuration = []
    ): MessageProvider {

        return new TwilioProvider(
            configuration: $configuration
        );
    }
}

