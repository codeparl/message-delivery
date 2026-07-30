<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery;

use Illuminate\Support\ServiceProvider;
use SchoolPalm\MessageDelivery\Channels\EmailChannel;
use SchoolPalm\MessageDelivery\Contracts\DeliveryRecorder;
use SchoolPalm\MessageDelivery\Managers\DeliveryManager;
use SchoolPalm\MessageDelivery\Managers\MessageManager;
use SchoolPalm\MessageDelivery\Managers\ProviderManager;
use SchoolPalm\MessageDelivery\Providers\Email\Laravel\LaravelMailFactory;
use SchoolPalm\MessageDelivery\Registry\ChannelRegistry;
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
                settings: $app->make(\SchoolPalm\MessageDelivery\Contracts\TenantProviderSettings::class)
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
    }


    /**
     * Bootstrap any application services.
     *
     * Registers:
     * - Channels (Email)
     * - Provider factories (Laravel Mail)
     * - Migrations for publishing
     */
    public function boot(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Register Email Channel
        |--------------------------------------------------------------------------
        */

        $this->callAfterResolving(
            ChannelRegistry::class,
            function (ChannelRegistry $registry): void {

                $registry->register(
                    new EmailChannel()
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
