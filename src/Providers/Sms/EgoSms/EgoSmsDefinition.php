<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\Sms\EgoSms;

use SchoolPalm\MessageDelivery\Providers\ProviderDefinition;

final class EgoSmsDefinition
{
    /**
     * Create provider definition.
     */
    public static function create(): ProviderDefinition
    {
        return new ProviderDefinition(
            name: 'egosms',

            channel: 'sms',

            label: 'EgoSMS',

            configuration: [

                /*
                |--------------------------------------------------------------------------
                | API Configuration
                |--------------------------------------------------------------------------
                */

                'api_url',


                /*
                |--------------------------------------------------------------------------
                | Authentication
                |--------------------------------------------------------------------------
                */

                'username',

                'password',


                /*
                |--------------------------------------------------------------------------
                | Message Configuration
                |--------------------------------------------------------------------------
                */

                'sender_id',

            ],


            capabilities: [

                'unicode',

                'delivery_reports',

            ]
        );
    }
}
