<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery;

use Illuminate\Support\ServiceProvider;
use SchoolPalm\MessageDelivery\Channels\EmailChannel;
use SchoolPalm\MessageDelivery\Channels\SmsChannel;
use SchoolPalm\MessageDelivery\Channels\PushChannel;
use SchoolPalm\MessageDelivery\Channels\WhatsAppChannel;
use SchoolPalm\MessageDelivery\Contracts\DeliveryRecorder;
use SchoolPalm\MessageDelivery\Managers\DeliveryManager;
use SchoolPalm\MessageDelivery\Managers\MessageManager;
use SchoolPalm\MessageDelivery\Managers\ProviderManager;
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
            fn($app): DatabaseDeliveryRecorder => new DatabaseDeliveryRecorder()
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
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Publishable Migrations
        |--------------------------------------------------------------------------
        */

        if ($this->app->runningInConsole()) {

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
