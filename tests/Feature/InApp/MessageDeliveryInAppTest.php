<?php

declare(strict_types=1);

use SchoolPalm\MessageDelivery\Contracts\TenantProviderSettings;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\MessageDelivery;
use SchoolPalm\MessageDelivery\Models\DatabaseNotification;

/*
|--------------------------------------------------------------------------
| Test Setup
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    $this->app->bind(
        TenantProviderSettings::class,
        fn(): TenantProviderSettings => new class implements TenantProviderSettings
        {
            public function providerFor(string $channel): ?string
            {
                return match ($channel) {
                    'in_app' => 'database-notifications',
                    'email' => 'laravel-mail',
                    default => null,
                };
            }

            public function configurationFor(string $channel, string $provider): array
            {
                return match ($provider) {
                    'database-notifications' => [],
                    'laravel-mail' => ['mailer' => 'array'],
                    default => [],
                };
            }

            public function enabled(string $channel, string $provider): bool
            {
                return true;
            }
        }
    );
});

/*
|--------------------------------------------------------------------------
| TEST 1: Send in-app notification through public API
|--------------------------------------------------------------------------
*/

it('sends in-app notification through public api', function (): void {
    $result = MessageDelivery::withContext([])
        ->inApp()
        ->to([
            ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 1],
        ])
        ->with(['title' => 'Welcome'])
        ->text('Welcome to the platform!')
        ->send();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->status->toBe('sent')
        ->provider->toBe('database-notifications');

    $notification = DatabaseNotification::first();

    expect($notification)->not->toBeNull()
        ->and($notification->title)->toBe('Welcome')
        ->and($notification->body)->toBe('Welcome to the platform!');
});

/*
|--------------------------------------------------------------------------
| TEST 2: In-app notification with text content
|--------------------------------------------------------------------------
*/

it('sends in-app notification with text content', function (): void {
    $result = MessageDelivery::withContext([])
        ->inApp()
        ->to([
            ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 1],
        ])
        ->with(['title' => 'Fee Reminder'])
        ->text('Your school fees are due.')
        ->send();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->status->toBe('sent');

    $notification = DatabaseNotification::first();

    expect($notification->body)->toBe('Your school fees are due.');
});

/*
|--------------------------------------------------------------------------
| TEST 3: In-app notification to multiple recipients
|--------------------------------------------------------------------------
*/

it('sends in-app notification to multiple recipients through public api', function (): void {
    $result = MessageDelivery::withContext([])
        ->inApp()
        ->to([
            ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 1],
            ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 2],
        ])
        ->with(['title' => 'Broadcast'])
        ->text('Hello everyone.')
        ->send();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue();

    expect(DatabaseNotification::count())->toBe(2);
});

/*
|--------------------------------------------------------------------------
| TEST 4: In-app notification with priority
|--------------------------------------------------------------------------
*/

it('sends in-app notification with priority', function (): void {
    $result = MessageDelivery::withContext([])
        ->inApp()
        ->to([
            ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 1],
        ])
        ->with(['title' => 'Urgent'])
        ->text('High priority notification.')
        ->priority('high')
        ->send();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue();

    $notification = DatabaseNotification::first();

    expect($notification->data['priority'])->toBe('high');
});

/*
|--------------------------------------------------------------------------
| TEST 5: Context propagation
|--------------------------------------------------------------------------
*/

it('propagates context through in-app notification delivery', function (): void {
    $result = MessageDelivery::withContext([
        'tenant_id' => 'tenant-1',
        'school_id' => 'school-1',
    ])
        ->inApp()
        ->to([
            ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 1],
        ])
        ->with(['title' => 'Context Test'])
        ->text('Context propagation test.')
        ->send();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue();

    $notification = DatabaseNotification::first();

    expect($notification->data)->toHaveKey('context')
        ->and($notification->data['context'])->toHaveKey('tenant_id')
        ->and($notification->data['context']['tenant_id'])->toBe('tenant-1');
});
