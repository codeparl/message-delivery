<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\WhatsApp\Twilio;

use SchoolPalm\MessageDelivery\Providers\ConfigurationField;
use SchoolPalm\MessageDelivery\Providers\ProviderDefinition;

/**
 * Twilio WhatsApp provider definition.
 *
 * This class describes the Twilio WhatsApp provider and supplies
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
 * Twilio WhatsApp uses the Twilio API with the whatsapp: prefix
 * to send WhatsApp messages.
 *
 * @see https://www.twilio.com/docs/whatsapp
 */
final class TwilioWhatsAppDefinition
{
    /**
     * Create the Twilio WhatsApp provider definition.
     *
     * @return ProviderDefinition
     */
    public static function make(): ProviderDefinition
    {
        return new ProviderDefinition(
            name: 'twilio-whatsapp',

            channel: 'whatsapp',

            label: 'Twilio WhatsApp',

            configuration: [
                ConfigurationField::string('sid')
                    ->withLabel('Account SID')
                    ->withRequired(true),

                ConfigurationField::string('token')
                    ->withLabel('Auth Token')
                    ->withRequired(true)
                    ->withSecret(true),

                ConfigurationField::string('from')
                    ->withLabel('From')
                    ->withRequired(true),
            ],

            capabilities: [
                'text',
                'media',
                'unicode',
                'delivery_status',
            ],
        );
    }
}
