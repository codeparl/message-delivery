<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Tests;

use Illuminate\Support\Facades\Mail;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use SchoolPalm\MessageDelivery\MessageDeliveryServiceProvider;
use SchoolPalm\AppLogger\AppLoggerServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Get package service providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            MessageDeliveryServiceProvider::class,
            AppLoggerServiceProvider::class,
        ];
    }


    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        // Use SQLite in-memory database for testing
        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set default mail driver to array for testing
        $app['config']->set('mail.default', 'array');

        $app['config']->set('mail.mailers', [
            'array' => [
                'transport' => 'array',
            ],
            'ses' => [
                'transport' => 'ses',
            ],
            'mailgun' => [
                'transport' => 'mailgun',
            ],
            'postmark' => [
                'transport' => 'postmark',
            ],
            'log' => [
                'transport' => 'log',
            ],
        ]);

        // Enable delivery tracking for tests
        $app['config']->set('message-delivery.delivery_tracking', true);

        // Use file driver for AppLogger to avoid requiring
        // the app_logs database table in test environment
        $app['config']->set('app-logger.driver', 'file');
        $app['config']->set('app-logger.file.disk', 'local');

        // Configure a local filesystem disk for test environment
        $app['config']->set('filesystems.disks.local', [
            'driver' => 'local',
            'root' => sys_get_temp_dir() . '/app-logger-tests',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Mailpit testing mailer
        |--------------------------------------------------------------------------
        |
        | Configured for integration tests only.
        | Requires Mailpit running on 127.0.0.1:1025.
        |
        */

        $app['config']->set(
            'mail.default',
            'mailpit'
        );

        $app['config']->set(
            'mail.mailers.mailpit',
            [
                'transport' => 'smtp',
                'host' => '127.0.0.1',
                'port' => 1025,
                'encryption' => null,
                'username' => null,
                'password' => null,
            ]
        );

        $app['config']->set(
            'mail.from',
            [
                'address' => 'testing@schoolpalm.local',
                'name' => 'SchoolPalm Test',
            ]
        );
    }


    /**
     * Define database migrations.
     *
     * Create the message_deliveries table directly
     * since migration files under src/ conflict with
     * PSR-4 autoloading.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineDatabaseMigrations(): void
    {
        \Illuminate\Support\Facades\Schema::create(
            'message_deliveries',
            function (\Illuminate\Database\Schema\Blueprint $table): void {
                $table->uuid('id')->primary();

                $table->string('tenant_id')->nullable();
                $table->string('school_id')->nullable();

                $table->string('channel');
                $table->string('provider');
                $table->string('recipient');
                $table->string('status');

                $table->string('provider_message_id')->nullable();
                $table->string('subject')->nullable();
                $table->json('metadata')->nullable();
                $table->text('error')->nullable();

                $table->datetime('queued_at')->nullable();
                $table->datetime('sent_at')->nullable();
                $table->datetime('delivered_at')->nullable();

                $table->timestamps();

                $table->index('tenant_id');
                $table->index('school_id');
                $table->index('channel');
                $table->index('status');
                $table->index('created_at');
            }
        );
    }
}
