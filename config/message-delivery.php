<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Channel
    |--------------------------------------------------------------------------
    |
    | Default communication channel when none is explicitly selected.
    |
    */

    'default_channel' => env(
        'MESSAGE_DEFAULT_CHANNEL',
        'email'
    ),

    /*
    |--------------------------------------------------------------------------
    | Notification Engine
    |--------------------------------------------------------------------------
    |
    | Default language and priority used by the Notification Engine
    | when resolvers do not provide a value.
    |
    */

    'notification' => [

        'default_language' => env(
            'MESSAGE_DEFAULT_LANGUAGE',
            'en'
        ),

        'default_priority' => env(
            'MESSAGE_DEFAULT_PRIORITY',
            'normal'
        ),

    ],

    /*
    |--------------------------------------------------------------------------
    | Delivery Tracking
    |--------------------------------------------------------------------------
    |
    | Records delivery lifecycle events and writes operational logs.
    |
    */

    'delivery_tracking' => env(
        'MESSAGE_DELIVERY_TRACKING',
        true
    ),

    /*
    |--------------------------------------------------------------------------
    | Registered Providers
    |--------------------------------------------------------------------------
    |
    | Default provider for each channel.
    | Applications may override these values.
    |
    */

    'channels' => [

        'email' => env(
            'MESSAGE_EMAIL_PROVIDER',
            'laravel-mail'
        ),

        'sms' => env(
            'MESSAGE_SMS_PROVIDER',
            'egosms'
        ),

        'whatsapp' => env(
            'MESSAGE_WHATSAPP_PROVIDER',
            'twilio-whatsapp'
        ),

        'push' => env(
            'MESSAGE_PUSH_PROVIDER',
            'firebase'
        ),

    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Configuration
    |--------------------------------------------------------------------------
    |
    | These values are intended for:
    |
    | • local development
    | • package testing
    | • demo applications
    |
    | In production, providers may obtain their configuration from
    | tenant settings instead.
    |
    */

    'providers' => [

        'laravel-mail' => [

            'mailer' => env(
                'MESSAGE_MAIL_MAILER',
                env('MAIL_MAILER', 'smtp')
            ),

        ],

        'ses' => [

            'mailer' => 'ses',

        ],

        'mailgun' => [

            'mailer' => 'mailgun',

        ],

        'postmark' => [

            'mailer' => 'postmark',

        ],

        'resend' => [

            'mailer' => 'resend',

        ],

        'egosms' => [

            'api_url' => env(
                'EGOSMS_API_URL'
            ),

            'username' => env(
                'EGOSMS_USERNAME'
            ),

            'password' => env(
                'EGOSMS_PASSWORD'
            ),

            'sender_id' => env(
                'EGOSMS_SENDER_ID'
            ),

        ],

        'twilio-sms' => [

            'sid' => env(
                'TWILIO_SID'
            ),

            'token' => env(
                'TWILIO_TOKEN'
            ),

            'from' => env(
                'TWILIO_FROM'
            ),

        ],

        'twilio-whatsapp' => [

            'sid' => env(
                'TWILIO_SID'
            ),

            'token' => env(
                'TWILIO_TOKEN'
            ),

            'from' => env(
                'TWILIO_WHATSAPP_FROM'
            ),

        ],

        'firebase' => [

            'credentials' => env(
                'FIREBASE_CREDENTIALS'
            ),

        ],

    ],

];
