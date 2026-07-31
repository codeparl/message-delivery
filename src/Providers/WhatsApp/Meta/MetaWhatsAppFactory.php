<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\WhatsApp\Meta;

use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Contracts\ProviderFactory;

/**
 * Factory for creating MetaWhatsAppProvider instances.
 *
 * This factory implements the ProviderFactory contract and is
 * registered with the ProviderRegistry to create configured
 * MetaWhatsAppProvider instances.
 *
 * Responsibilities:
 * - ONLY creates MetaWhatsAppProvider instances.
 * - Does NOT send WhatsApp messages.
 * - Does NOT access settings or resolve tenants.
 * - Does NOT validate configuration (delegated to provider).
 */
final class MetaWhatsAppFactory implements ProviderFactory
{
    /**
     * Get the provider identifier.
     *
     * This value is used by ProviderRegistry and stored
     * in tenant provider settings.
     */
    public function name(): string
    {
        return 'meta-whatsapp';
    }


    /**
     * Get the channel supported by this provider.
     */
    public function channel(): string
    {
        return 'whatsapp';
    }


    /**
     * Create a configured MetaWhatsAppProvider instance.
     *
     * Configuration is supplied by TenantProviderSettings.
     *
     * Expected configuration:
     *
     * [
     *     'access_token' => 'EAAx...',
     *     'phone_number_id' => '123456789',
     *     'version' => 'v23.0',
     *     'verify_ssl' => true,
     * ]
     *
     * @param  array<string, mixed>  $configuration
     * @return MessageProvider
     */
    public function create(
        array $configuration = []
    ): MessageProvider {

        return new MetaWhatsAppProvider(
            configuration: $configuration
        );
    }
}
