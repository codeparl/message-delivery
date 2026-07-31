<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\Sms\Twilio;

use SchoolPalm\MessageDelivery\Providers\ProviderDefinition;

/**
 * Twilio SMS provider definition.
 *
 * This class describes the Twilio SMS provider and supplies
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
 * Twilio SMS uses the Twilio Programmable Messaging API to send
 * SMS messages globally.
 *
 * @see https://www.twilio.com/docs/sms
 */
final class TwilioDefinition
{
    /**
     * Create the Twilio SMS provider definition.
     *
     * @return ProviderDefinition
     */
    public static function make(): ProviderDefinition
    {
        return new ProviderDefinition(
            name: 'twilio-sms',

            channel: 'sms',

            label: 'Twilio SMS',

            configuration: [

                /*
                |--------------------------------------------------------------------------
                | Authentication
                |--------------------------------------------------------------------------
                */

                'sid',

                'token',


                /*
                |--------------------------------------------------------------------------
                | Message Configuration
                |--------------------------------------------------------------------------
                */

                'from',

            ],

            capabilities: [

                'plain-text',

                'unicode',

                'delivery_status',

            ],
        );
    }
}

