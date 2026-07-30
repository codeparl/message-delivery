<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\Email\Laravel;

use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Contracts\ProviderFactory;

/**
 * Factory for creating LaravelMailProvider instances.
 *
 * This factory implements the ProviderFactory contract and is
 * registered with the ProviderRegistry to create configured
 * LaravelMailProvider instances.
 *
 * Usage:
 * - The factory is registered with ProviderRegistry under
 *   channel 'email' with name 'laravel-mail'.
 * - When ProviderManager resolves a provider for a message,
 *   it calls create() with the configuration array.
 *
 * Responsibilities:
 * - ONLY creates LaravelMailProvider instances.
 * - Does NOT send emails.
 * - Does NOT access settings or resolve tenants.
 * - Does NOT validate configuration (delegated to provider).
 */
final class LaravelMailFactory implements ProviderFactory
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
     *     'channel' => 'email',
     *     'provider' => 'laravel-mail'
     * ]
     */
    public function name(): string
    {
        return 'laravel-mail';
    }


    /**
     * Get the channel supported by this provider.
     */
    public function channel(): string
    {
        return 'email';
    }


    /**
     * Create a configured LaravelMailProvider instance.
     *
     * Configuration is supplied by TenantProviderSettings.
     *
     * Expected configuration:
     *
     * [
     *     'mailer' => 'ses',  // Laravel mailer name
     * ]
     *
     * The provider will use Mail::mailer($mailer) for sending.
     *
     * @param  array<string, mixed>  $configuration
     * @return MessageProvider
     */
    public function create(
        array $configuration = []
    ): MessageProvider {

        return new LaravelMailProvider(
            configuration: $configuration
        );
    }
}

