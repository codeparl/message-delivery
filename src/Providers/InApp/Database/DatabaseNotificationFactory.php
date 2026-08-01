<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\InApp\Database;

use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Contracts\ProviderFactory;

/**
 * Factory for creating DatabaseNotificationProvider instances.
 *
 * This factory implements the ProviderFactory contract and is
 * registered with the ProviderRegistry to create configured
 * DatabaseNotificationProvider instances.
 *
 * Usage:
 * - The factory is registered with ProviderRegistry under
 *   channel 'in_app' with name 'database-notifications'.
 * - When ProviderManager resolves a provider for a message,
 *   it calls create() with the configuration array.
 *
 * Responsibilities:
 * - ONLY creates DatabaseNotificationProvider instances.
 * - Does NOT store notifications.
 * - Does NOT access settings or resolve tenants.
 */
final class DatabaseNotificationFactory implements ProviderFactory
{
    /**
     * Get the provider identifier.
     *
     * This value is used by ProviderRegistry and stored
     * in tenant provider settings.
     */
    public function name(): string
    {
        return 'database-notifications';
    }


    /**
     * Get the channel supported by this provider.
     */
    public function channel(): string
    {
        return 'in_app';
    }


    /**
     * Create a configured DatabaseNotificationProvider instance.
     *
     * @param  array<string, mixed>  $configuration
     * @return MessageProvider
     */
    public function create(
        array $configuration = []
    ): MessageProvider {

        return new DatabaseNotificationProvider(
            configuration: $configuration
        );
    }
}

