<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\Sms\AfricasTalking;

use SchoolPalm\MessageDelivery\Providers\ProviderDefinition;

/**
 * Africa's Talking SMS provider definition.
 *
 * This class describes the Africa's Talking SMS provider and
 * supplies its immutable metadata. It does not send messages
 * or resolve configuration.
 *
 * The definition is consumed by:
 *
 * - ProviderRegistry
 * - ProviderManager
 * - Administration interfaces
 * - Configuration validation
 *
 * Africa's Talking provides SMS messaging services across
 * African countries through their REST API.
 *
 * @see https://africastalking.com/
 */
final class AfricasTalkingDefinition
{
    /**
     * Create the Africa's Talking SMS provider definition.
     *
     * @return ProviderDefinition
     */
    public static function make(): ProviderDefinition
    {
        return new ProviderDefinition(
            name: 'africas-talking',

            channel: 'sms',

            label: 'Africa\'s Talking',

            configuration: [

                /*
                |--------------------------------------------------------------------------
                | API Configuration
                |--------------------------------------------------------------------------
                */

                'api_key',

                'username',


                /*
                |--------------------------------------------------------------------------
                | Message Configuration
                |--------------------------------------------------------------------------
                */

                'sender_id',

            ],

            capabilities: [

                'plain-text',

                'bulk',

                'unicode',

                'delivery_status',

            ],
        );
    }
}

