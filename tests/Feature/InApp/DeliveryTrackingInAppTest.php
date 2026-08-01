<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use SchoolPalm\MessageDelivery\Contracts\DeliveryRecorder;
use SchoolPalm\MessageDelivery\Contracts\TenantProviderSettings;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\MessageDelivery;
use SchoolPalm\MessageDelivery\Models\MessageDelivery as MessageDeliveryModel;

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

    Queue::fake();
});

/*
|--------------------------------------------------------------------------
| TEST 1: Queued delivery record for in-app notification
|--------------------------------------------------------------------------
*/

it('creates queued delivery record for in-app notification', function (): void {
    MessageDelivery::withContext([
        'tenant_id' => 'tenant-1',
        'school_id' => 'school-1',
    ])
        ->inApp()
        ->to([
            ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 1],
        ])
        ->title('Queued In-App')
        ->text('Queued in-app notification.')
        ->queue();

    $delivery = MessageDeliveryModel::first();

    expect($delivery)->not->toBeNull()
        ->and($delivery->status)->toBe('queued')
        ->and($delivery->channel)->toBe('in_app')
        ->and($delivery->provider)->toBe('database-notifications')
        ->and($delivery->tenant_id)->toBe('tenant-1')
        ->and($delivery->school_id)->toBe('school-1')
        ->and($delivery->queued_at)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| TEST 2: Successful in-app delivery record
|--------------------------------------------------------------------------
*/

it('updates successful delivery record for in-app notification', function (): void {
    MessageDelivery::withContext([])
        ->inApp()
        ->to([
            ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 1],
        ])
        ->title('Sent In-App')
        ->text('Sent in-app notification.')
        ->send();

    $delivery = MessageDeliveryModel::first();

    expect($delivery)->not->toBeNull()
        ->and($delivery->status)->toBe('sent')
        ->and($delivery->provider)->toBe('database-notifications')
        ->and($delivery->sent_at)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| TEST 3: Failed in-app delivery record
|--------------------------------------------------------------------------
*/

it('stores failed in-app delivery record', function (): void {
    $recorder = app(DeliveryRecorder::class);

    $message = new Message(
        channel: 'in_app',
        recipients: [
            ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 99],
        ],
        text: 'This will fail.',
        data: ['title' => 'Failure Test'],
    );

    $result = DeliveryResult::failure(
        error: 'Storage rejected the notification',
        provider: 'database-notifications',
    );

    $delivery = $recorder->recordFailed($message, $result);

    expect($delivery)->not->toBeNull()
        ->and($delivery->status)->toBe('failed')
        ->and($delivery->error)->toBe('Storage rejected the notification')
        ->and($delivery->provider)->toBe('database-notifications')
        ->and($delivery->channel)->toBe('in_app');
});

/*
|--------------------------------------------------------------------------
| TEST 4: Channel and provider stored
|--------------------------------------------------------------------------
*/

it('stores channel and provider for in-app delivery', function (): void {
    MessageDelivery::withContext([])
        ->inApp()
        ->to([
            ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 1],
        ])
        ->title('Test')
        ->text('Test message.')
        ->send();

    $delivery = MessageDeliveryModel::first();

    expect($delivery)->not->toBeNull()
        ->and($delivery->channel)->toBe('in_app')
        ->and($delivery->provider)->toBe('database-notifications');
});

/*
|--------------------------------------------------------------------------
| TEST 5: Metadata stored for in-app
|--------------------------------------------------------------------------
*/

it('stores metadata for in-app delivery', function (): void {
    MessageDelivery::withContext([
        'tenant_id' => 'tenant-1',
    ])
        ->inApp()
        ->to([
            ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 1],
        ])
        ->title('Metadata Test')
        ->text('Test with metadata.')
        ->priority('high')
        ->send();

    $delivery = MessageDeliveryModel::first();

    expect($delivery)->not->toBeNull()
        ->and($delivery->metadata)->toBeArray()
        ->and($delivery->metadata)->toHaveKey('priority')
        ->and($delivery->metadata['priority'])->toBe('high')
        ->and($delivery->metadata)->toHaveKey('context')
        ->and($delivery->metadata['context'])->toHaveKey('tenant_id')
        ->and($delivery->metadata['context']['tenant_id'])->toBe('tenant-1');
});
