<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\WhatsApp\Twilio;

use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Contracts\ProviderFactory;

/**
 * Factory for creating TwilioWhatsAppProvider instances.
 *
 * This factory implements the ProviderFactory contract and is
 * registered with the ProviderRegistry to create configured
 * TwilioWhatsAppProvider instances.
 *
 * Responsibilities:
 * - ONLY creates TwilioWhatsAppProvider instances.
 * - Does NOT send WhatsApp messages.
 * - Does NOT access settings or resolve tenants.
 * - Does NOT validate configuration (delegated to provider).
 */
final class TwilioWhatsAppFactory implements ProviderFactory
{
    /**
     * Get the provider identifier.
     *
     * This value is used by ProviderRegistry and stored
     * in tenant provider settings.
     */
    public function name(): string
    {
        return 'twilio-whatsapp';
    }


    /**
     * Get the channel supported by this provider.
     */
    public function channel(): string
    {
        return 'whatsapp';
    }


    /**
     * Create a configured TwilioWhatsAppProvider instance.
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

        return new TwilioWhatsAppProvider(
            configuration: $configuration
        );
    }
}
