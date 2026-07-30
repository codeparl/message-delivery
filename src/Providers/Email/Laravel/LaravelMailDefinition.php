<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\Email\Laravel;

use SchoolPalm\MessageDelivery\Providers\ProviderDefinition;

final class LaravelMailDefinition
{
    /**
     * Create the Laravel Mail provider definition.
     *
     * This class describes the Laravel Mail provider and
     * supplies its immutable metadata. It does not send
     * messages or resolve configuration.
     *
     * The definition is consumed by:
     *
     * - ProviderRegistry
     * - ProviderManager
     * - Administration interfaces
     * - Configuration validation
     *
     * Laravel Mail acts as an adapter over Laravel's mail
     * system, allowing MessageDelivery to send email using
     * any configured Laravel mailer such as:
     *
     * - SMTP
     * - Amazon SES
     * - Mailgun
     * - Postmark
     * - Resend
     * - Log
     * - Array
     */
    public static function make(): ProviderDefinition
    {
        return new ProviderDefinition(
            name: 'laravel-mail',

            channel: 'email',

            label: 'Laravel Mail',

            configuration: [

                'mailer',

            ],

            capabilities: [

                'html',

                'text',

                'blade',

                'attachments',

                'cc',

                'bcc',

                'reply_to',

                'priority',

                'headers',

                'queue',

            ],
        );
    }
}
