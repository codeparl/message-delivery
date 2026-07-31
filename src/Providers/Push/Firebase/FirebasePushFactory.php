<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\Push\Firebase;

use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Contracts\ProviderFactory;

/**
 * Factory for creating FirebasePushProvider instances.
 *
 * This factory implements the ProviderFactory contract and is
 * registered with the ProviderRegistry to create configured
 * FirebasePushProvider instances.
 *
 * Usage:
 * - The factory is registered with ProviderRegistry under
 *   channel 'push' with name 'firebase-push'.
 * - When ProviderManager resolves a provider for a message,
 *   it calls create() with the configuration array.
 *
 * Responsibilities:
 * - ONLY creates FirebasePushProvider instances.
 * - Does NOT send push notifications.
 * - Does NOT access settings or resolve tenants.
 * - Does NOT validate configuration (delegated to provider).
 */
final class FirebasePushFactory implements ProviderFactory
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
     *     'channel' => 'push',
     *     'provider' => 'firebase-push'
     * ]
     */
    public function name(): string
    {
        return 'firebase-push';
    }


    /**
     * Get the channel supported by this provider.
     */
    public function channel(): string
    {
        return 'push';
    }


    /**
     * Create a configured FirebasePushProvider instance.
     *
     * Configuration is supplied by TenantProviderSettings.
     *
     * Expected configuration:
     *
     * [
     *     'credentials_json' => '{ "type": "service_account", ... }',
     *     'project_id' => 'my-project-id',
     *     'server_key' => 'optional-legacy-key',
     * ]
     *
     * @param  array<string, mixed>  $configuration
     * @return MessageProvider
     */
    public function create(
        array $configuration = []
    ): MessageProvider {

        return new FirebasePushProvider(
            configuration: $configuration
        );
    }
}

