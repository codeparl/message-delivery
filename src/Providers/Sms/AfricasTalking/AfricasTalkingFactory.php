<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\Sms\AfricasTalking;

use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Contracts\ProviderFactory;

/**
 * Factory for creating AfricasTalkingProvider instances.
 *
 * This factory implements the ProviderFactory contract and is
 * registered with the ProviderRegistry to create configured
 * AfricasTalkingProvider instances.
 *
 * Usage:
 * - The factory is registered with ProviderRegistry under
 *   channel 'sms' with name 'africas-talking'.
 * - When ProviderManager resolves a provider for a message,
 *   it calls create() with the configuration array.
 *
 * Responsibilities:
 * - ONLY creates AfricasTalkingProvider instances.
 * - Does NOT send SMS messages.
 * - Does NOT access settings or resolve tenants.
 * - Does NOT validate configuration (delegated to provider).
 */
final class AfricasTalkingFactory implements ProviderFactory
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
     *     'provider' => 'africas-talking'
     * ]
     */
    public function name(): string
    {
        return 'africas-talking';
    }


    /**
     * Get the channel supported by this provider.
     */
    public function channel(): string
    {
        return 'sms';
    }


    /**
     * Create a configured AfricasTalkingProvider instance.
     *
     * Configuration is supplied by TenantProviderSettings.
     *
     * Expected configuration:
     *
     * [
     *     'api_key' => 'xxxxxxxx',
     *     'username' => 'my-username',
     *     'sender_id' => 'SCHOOL',
     * ]
     *
     * @param  array<string, mixed>  $configuration
     * @return MessageProvider
     */
    public function create(
        array $configuration = []
    ): MessageProvider {

        return new AfricasTalkingProvider(
            configuration: $configuration
        );
    }
}

