<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\InApp\Database;

use SchoolPalm\MessageDelivery\Providers\ConfigurationField;
use SchoolPalm\MessageDelivery\Providers\ProviderDefinition;

/**
 * Database Notification provider definition.
 *
 * This class describes the in-app database notification provider
 * and supplies its immutable metadata. It does not store notifications
 * or resolve configuration.
 *
 * The definition is consumed by:
 *
 * - ProviderRegistry
 * - ProviderManager
 * - Administration interfaces
 * - Configuration validation
 */
final class DatabaseNotificationDefinition
{
    /**
     * Create the database notification provider definition.
     *
     * @return ProviderDefinition
     */
    public static function make(): ProviderDefinition
    {
        return new ProviderDefinition(
            name: 'database-notifications',

            channel: 'in_app',

            label: 'Database Notifications',

            configuration: [
                ConfigurationField::string('default_notifiable')
                    ->withLabel('Default Notifiable Model')
                    ->withRequired(false)
                    ->withDescription('Fully qualified model class for string-based recipient resolution (e.g., App\Models\User).'),
            ],

            capabilities: [
                'read_status',
                'unread_count',
                'metadata',
            ],
        );
    }
}

