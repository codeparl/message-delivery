<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\WhatsApp\Meta;

use SchoolPalm\MessageDelivery\Providers\ConfigurationField;
use SchoolPalm\MessageDelivery\Providers\ProviderDefinition;

/**
 * Meta WhatsApp Cloud API provider definition.
 *
 * This class describes the Meta WhatsApp provider and supplies
 * its immutable metadata. It does not send messages or resolve
 * configuration.
 *
 * The definition is consumed by:
 *
 * - ProviderRegistry
 * - ProviderManager
 * - Administration interfaces
 * - Configuration validation
 *
 * @see https://developers.facebook.com/docs/whatsapp/cloud-api
 */
final class MetaWhatsAppDefinition
{
    /**
     * Create the Meta WhatsApp provider definition.
     *
     * @return ProviderDefinition
     */
    public static function make(): ProviderDefinition
    {
        return new ProviderDefinition(
            name: 'meta-whatsapp',

            channel: 'whatsapp',

            label: 'Meta WhatsApp Cloud API',

            configuration: [
                ConfigurationField::string('access_token')
                    ->withLabel('Access Token')
                    ->withRequired(true)
                    ->withSecret(true),

                ConfigurationField::string('phone_number_id')
                    ->withLabel('Phone Number ID')
                    ->withRequired(true),

                ConfigurationField::string('version')
                    ->withLabel('API Version')
                    ->withDefault('v23.0'),

                ConfigurationField::boolean('verify_ssl')
                    ->withLabel('Verify SSL')
                    ->withDefault(true),
            ],

            capabilities: [
                'text',
                'template',
                'media',
                'reply',
                'unicode',
                'delivery_status',
            ],
        );
    }
}
