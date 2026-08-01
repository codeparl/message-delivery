<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery;

use Illuminate\Support\ServiceProvider;
use SchoolPalm\MessageDelivery\Channels\EmailChannel;
use SchoolPalm\MessageDelivery\Channels\InAppChannel;
use SchoolPalm\MessageDelivery\Channels\SmsChannel;
use SchoolPalm\MessageDelivery\Channels\PushChannel;
use SchoolPalm\MessageDelivery\Channels\WhatsAppChannel;
use SchoolPalm\MessageDelivery\Contracts\DeliveryRecorder;
use SchoolPalm\MessageDelivery\Managers\DeliveryManager;
use SchoolPalm\MessageDelivery\Managers\MessageManager;
use SchoolPalm\MessageDelivery\Managers\ProviderManager;
use SchoolPalm\MessageDelivery\Notification\Contracts\ChannelResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\EventResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\LanguageResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\NotificationEngine as NotificationEngineContract;
use SchoolPalm\MessageDelivery\Notification\Contracts\PreferenceResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\PriorityResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\RecipientResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\RetryResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\ScheduleResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\TemplateResolver;
use SchoolPalm\MessageDelivery\Notification\Engine\NotificationEngine;
use SchoolPalm\MessageDelivery\Notification\NotificationManager;
use SchoolPalm\MessageDelivery\Notification\Resolvers\NullChannelResolver;
use SchoolPalm\MessageDelivery\Notification\Resolvers\NullEventResolver;
use SchoolPalm\MessageDelivery\Notification\Resolvers\NullLanguageResolver;
use SchoolPalm\MessageDelivery\Notification\Resolvers\NullPreferenceResolver;
use SchoolPalm\MessageDelivery\Notification\Resolvers\NullPriorityResolver;
use SchoolPalm\MessageDelivery\Notification\Resolvers\NullRecipientResolver;
use SchoolPalm\MessageDelivery\Notification\Resolvers\NullRetryResolver;
use SchoolPalm\MessageDelivery\Notification\Resolvers\NullScheduleResolver;
use SchoolPalm\MessageDelivery\Notification\Resolvers\NullTemplateResolver;
use SchoolPalm\MessageDelivery\Providers\Email\Laravel\LaravelMailDefinition;
use SchoolPalm\MessageDelivery\Providers\Email\Laravel\LaravelMailFactory;
use SchoolPalm\MessageDelivery\Providers\Sms\AfricasTalking\AfricasTalkingDefinition;
use SchoolPalm\MessageDelivery\Providers\Sms\AfricasTalking\AfricasTalkingFactory;
use SchoolPalm\MessageDelivery\Providers\Sms\EgoSms\EgoSmsDefinition;
use SchoolPalm\MessageDelivery\Providers\Sms\EgoSms\EgoSmsFactory;
use SchoolPalm\MessageDelivery\Providers\Sms\Twilio\TwilioDefinition;
use SchoolPalm\MessageDelivery\Providers\Sms\Twilio\TwilioFactory;
use SchoolPalm\MessageDelivery\Providers\Push\Firebase\FirebasePushDefinition;
use SchoolPalm\MessageDelivery\Providers\Push\Firebase\FirebasePushFactory;
use SchoolPalm\MessageDelivery\Providers\WhatsApp\Meta\MetaWhatsAppDefinition;
use SchoolPalm\MessageDelivery\Providers\WhatsApp\Meta\MetaWhatsAppFactory;
use SchoolPalm\MessageDelivery\Providers\WhatsApp\Twilio\TwilioWhatsAppDefinition;
use SchoolPalm\MessageDelivery\Providers\WhatsApp\Twilio\TwilioWhatsAppFactory;
use SchoolPalm\MessageDelivery\Providers\InApp\Database\DatabaseNotificationDefinition;
use SchoolPalm\MessageDelivery\Providers\InApp\Database\DatabaseNotificationFactory;
use SchoolPalm\MessageDelivery\Registry\ChannelRegistry;
use SchoolPalm\MessageDelivery\Registry\DefinitionRegistry;
use SchoolPalm\MessageDelivery\Registry\ProviderRegistry;
use SchoolPalm\MessageDelivery\Services\DatabaseDeliveryRecorder;

final class MessageDeliveryServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * Registers:
     * - ChannelRegistry singleton
     * - ProviderRegistry singleton
     * - DefinitionRegistry singleton
     * - DeliveryRecorder singleton
     * - ProviderManager
     * - DeliveryManager
     * - MessageManager
     */
    public function register(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Configuration
        |--------------------------------------------------------------------------
        |
        | Merge the package config file so that config('message-delivery.*')
        | works out of the box. Applications may override values by publishing
        | the config with:
        |
        | php artisan vendor:publish --tag=message-delivery-config
        |
        */

        $this->mergeConfigFrom(
            __DIR__ . '/../config/message-delivery.php',
            'message-delivery'
        );


        /*
        |--------------------------------------------------------------------------
        | Registry Singletons
        |--------------------------------------------------------------------------
        */

        $this->app->singleton(
            ProviderRegistry::class,
            fn($app) => new ProviderRegistry()
        );

        $this->app->singleton(
            ChannelRegistry::class,
            fn($app) => new ChannelRegistry()
        );

        $this->app->singleton(
            DefinitionRegistry::class,
            fn($app) => new DefinitionRegistry()
        );


        /*
        |--------------------------------------------------------------------------
        | Delivery Recorder
        |--------------------------------------------------------------------------
        */

        $this->app->singleton(
            DeliveryRecorder::class,
            function ($app): DatabaseDeliveryRecorder {

                $settings = $app->bound(\SchoolPalm\MessageDelivery\Contracts\TenantProviderSettings::class)
                    ? $app->make(\SchoolPalm\MessageDelivery\Contracts\TenantProviderSettings::class)
                    : null;

                return new DatabaseDeliveryRecorder(
                    settings: $settings
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Manager Bindings
        |--------------------------------------------------------------------------
        */

        $this->app->singleton(
            ProviderManager::class,
            fn($app) => new ProviderManager(
                providerRegistry: $app->make(ProviderRegistry::class),
                settings: $app->make(\SchoolPalm\MessageDelivery\Contracts\TenantProviderSettings::class),
                definitionRegistry: $app->make(DefinitionRegistry::class),
            )
        );

        $this->app->singleton(
            DeliveryManager::class,
            fn($app) => new DeliveryManager(
                channelRegistry: $app->make(ChannelRegistry::class),
                providerManager: $app->make(ProviderManager::class)
            )
        );

        $this->app->singleton(
            MessageManager::class,
            fn($app) => new MessageManager(
                deliveryManager: $app->make(DeliveryManager::class),
                deliveryRecorder: $app->make(DeliveryRecorder::class),
                config: $app->make('config')->get('message-delivery', [])
            )
        );


        /*
        |--------------------------------------------------------------------------
        | Facade Binding
        |--------------------------------------------------------------------------
        */

        $this->app->singleton(
            'message-delivery',
            fn($app) => new MessageDelivery(
                context: null
            )
        );


        /*
        |--------------------------------------------------------------------------
        | Notification Resolver Bindings
        |--------------------------------------------------------------------------
        |
        | Default Null implementations are bound so the package works
        | without application adapters. SchoolPalm replaces these
        | bindings with its own resolvers.
        |
        */

        $this->app->bind(
            EventResolver::class,
            fn($app) => new NullEventResolver()
        );

        $this->app->bind(
            RecipientResolver::class,
            fn($app) => new NullRecipientResolver()
        );

        $this->app->bind(
            PreferenceResolver::class,
            fn($app) => new NullPreferenceResolver()
        );

        $this->app->bind(
            ChannelResolver::class,
            fn($app) => new NullChannelResolver(
                defaultChannel: $app->make('config')->get(
                    'message-delivery.default_channel',
                    'email'
                )
            )
        );

        $this->app->bind(
            LanguageResolver::class,
            fn($app) => new NullLanguageResolver()
        );

        $this->app->bind(
            TemplateResolver::class,
            fn($app) => new NullTemplateResolver()
        );

        $this->app->bind(
            PriorityResolver::class,
            fn($app) => new NullPriorityResolver()
        );

        $this->app->bind(
            ScheduleResolver::class,
            fn($app) => new NullScheduleResolver()
        );

        $this->app->bind(
            RetryResolver::class,
            fn($app) => new NullRetryResolver()
        );


        /*
        |--------------------------------------------------------------------------
        | Notification Engine
        |--------------------------------------------------------------------------
        */

        $this->app->singleton(
            NotificationEngineContract::class,
            fn($app) => new NotificationEngine(
                eventResolver: $app->make(EventResolver::class),
                recipientResolver: $app->make(RecipientResolver::class),
                preferenceResolver: $app->make(PreferenceResolver::class),
                channelResolver: $app->make(ChannelResolver::class),
                languageResolver: $app->make(LanguageResolver::class),
                templateResolver: $app->make(TemplateResolver::class),
                priorityResolver: $app->make(PriorityResolver::class),
                scheduleResolver: $app->make(ScheduleResolver::class),
                retryResolver: $app->make(RetryResolver::class),
                delivery: $app->make('message-delivery'),
                config: $app->make('config')->get(
                    'message-delivery.notification',
                    []
                ),
            )
        );


        /*
        |--------------------------------------------------------------------------
        | Notification Manager (Facade Root)
        |--------------------------------------------------------------------------
        */

        $this->app->singleton(
            'notification',
            fn($app) => new NotificationManager(
                engine: $app->make(NotificationEngineContract::class)
            )
        );
    }


    /**
     * Bootstrap any application services.
     *
     * Registers:
     * - Channels (Email, SMS)
     * - Provider factories (Laravel Mail, EgoSMS, Twilio, Africa's Talking)
     * - Provider definitions
     * - Migrations for publishing
     */
    public function boot(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Register Channels
        |--------------------------------------------------------------------------
        */

        $this->callAfterResolving(
            ChannelRegistry::class,
            function (ChannelRegistry $registry): void {

                $registry->register(
                    new EmailChannel()
                );

                $registry->register(
                    new SmsChannel()
                );

                $registry->register(
                    new WhatsAppChannel()
                );

                $registry->register(
                    new PushChannel()
                );

                $registry->register(
                    new InAppChannel()
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Register Email Provider Factory
        |--------------------------------------------------------------------------
        */

        $this->callAfterResolving(
            ProviderRegistry::class,
            function (ProviderRegistry $registry): void {

                $registry->register(
                    new LaravelMailFactory()
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Register SMS Provider Factories
        |--------------------------------------------------------------------------
        */

        $this->callAfterResolving(
            ProviderRegistry::class,
            function (ProviderRegistry $registry): void {

                $registry->register(
                    new EgoSmsFactory()
                );

                $registry->register(
                    new TwilioFactory()
                );

                $registry->register(
                    new AfricasTalkingFactory()
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Register WhatsApp Provider Factories
        |--------------------------------------------------------------------------
        */

        $this->callAfterResolving(
            ProviderRegistry::class,
            function (ProviderRegistry $registry): void {

                $registry->register(
                    new MetaWhatsAppFactory()
                );

                $registry->register(
                    new TwilioWhatsAppFactory()
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Register Push Provider Factories
        |--------------------------------------------------------------------------
        */

        $this->callAfterResolving(
            ProviderRegistry::class,
            function (ProviderRegistry $registry): void {

                $registry->register(
                    new FirebasePushFactory()
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Register In-App Provider Factory
        |--------------------------------------------------------------------------
        */

        $this->callAfterResolving(
            ProviderRegistry::class,
            function (ProviderRegistry $registry): void {

                $registry->register(
                    new DatabaseNotificationFactory()
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Register Provider Definitions
        |--------------------------------------------------------------------------
        */

        $this->callAfterResolving(
            DefinitionRegistry::class,
            function (DefinitionRegistry $registry): void {

                $registry->register(
                    LaravelMailDefinition::make()
                );

                $registry->register(
                    EgoSmsDefinition::create()
                );

                $registry->register(
                    TwilioDefinition::make()
                );

                $registry->register(
                    AfricasTalkingDefinition::make()
                );

                $registry->register(
                    MetaWhatsAppDefinition::make()
                );

                $registry->register(
                    TwilioWhatsAppDefinition::make()
                );

                $registry->register(
                    FirebasePushDefinition::make()
                );

                $registry->register(
                    DatabaseNotificationDefinition::make()
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Publishable Configuration
        |--------------------------------------------------------------------------
        |
        | php artisan vendor:publish --tag=message-delivery-config
        |
        */

        if ($this->app->runningInConsole()) {

            $this->publishes(
                [
                    __DIR__ . '/../config/message-delivery.php' =>
                    config_path('message-delivery.php'),
                ],
                'message-delivery-config'
            );


            /*
            |--------------------------------------------------------------------------
            | Publishable Migrations
            |--------------------------------------------------------------------------
            |
            | php artisan vendor:publish --tag=message-delivery-migrations
            |
            */

            $this->publishes(
                [
                    __DIR__ . '/Database/migrations' =>
                    database_path('migrations'),
                ],
                'message-delivery-migrations'
            );
        }
    }
}
