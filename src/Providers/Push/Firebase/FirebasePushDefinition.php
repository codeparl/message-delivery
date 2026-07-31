<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\Push\Firebase;

use SchoolPalm\MessageDelivery\Providers\ConfigurationField;
use SchoolPalm\MessageDelivery\Providers\ProviderDefinition;

/**
 * Firebase Cloud Messaging provider definition.
 *
 * This class describes the Firebase push provider and supplies
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
 * Firebase Cloud Messaging (FCM) is Google's push notification
 * service that enables sending messages to Android, iOS, and
 * web devices.
 *
 * @see https://firebase.google.com/docs/cloud-messaging
 */
final class FirebasePushDefinition
{
    /**
     * Create the Firebase push provider definition.
     *
     * @return ProviderDefinition
     */
    public static function make(): ProviderDefinition
    {
        return new ProviderDefinition(
            name: 'firebase-push',

            channel: 'push',

            label: 'Firebase Cloud Messaging',

            configuration: [
                ConfigurationField::string('credentials_json')
                    ->withLabel('Service Account Credentials (JSON)')
                    ->withRequired(true)
                    ->withSecret(true)
                    ->withDescription('Paste the entire JSON content of your Firebase service account key file.'),

                ConfigurationField::string('project_id')
                    ->withLabel('Project ID')
                    ->withRequired(true)
                    ->withDescription('Your Firebase project ID (e.g., my-app-12345).'),

                ConfigurationField::string('server_key')
                    ->withLabel('Server Key (Legacy)')
                    ->withRequired(false)
                    ->withSecret(true)
                    ->withDescription('Optional legacy Firebase server key for backward compatibility.'),
            ],

            capabilities: [
                'notification',
                'data',
                'topics',
                'images',
                'actions',
                'delivery_status',
            ],
        );
    }
}

