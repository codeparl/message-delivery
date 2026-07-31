<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use SchoolPalm\MessageDelivery\Contracts\TenantProviderSettings;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\MessageDelivery;

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
                    'push' => 'firebase-push',
                    default => null,
                };
            }

            public function configurationFor(string $channel, string $provider): array
            {
                return match ($provider) {
                    'firebase-push' => [
                        'credentials_json' => '{"type":"service_account","client_email":"test@project.iam.gserviceaccount.com"}',
                        'project_id' => 'test-project',
                        'access_token' => 'test-access-token',
                    ],
                    default => [],
                };
            }

            public function enabled(string $channel, string $provider): bool
            {
                return true;
            }
        }
    );

    Http::fake([
        'https://fcm.googleapis.com/*' => Http::response([
            'name' => 'projects/test-project/messages/0:api-test',
        ], 200),
    ]);

    Queue::fake();
});

/*
|--------------------------------------------------------------------------
| TEST 1: SEND PUSH THROUGH PUBLIC API
|--------------------------------------------------------------------------
*/

it('sends push through public api', function (): void {
    $result = MessageDelivery::withContext([])
        ->push()
        ->to('device-token-api')
        ->text('Hello student via push')
        ->with(['title' => 'SchoolPalm'])
        ->send();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->status->toBe('sent')
        ->provider->toBe('firebase-push');
});

/*
|--------------------------------------------------------------------------
| TEST 2: PUSH WITH TEXT
|--------------------------------------------------------------------------
*/

it('sends push with text content', function (): void {
    $result = MessageDelivery::withContext([])
        ->push()
        ->to('device-token-text')
        ->text('Your school fees are due.')
        ->with(['title' => 'Fee Reminder'])
        ->send();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->status->toBe('sent');
});

/*
|--------------------------------------------------------------------------
| TEST 3: PUSH WITH DATA PAYLOAD
|--------------------------------------------------------------------------
*/

it('sends push with data payload', function (): void {
    $result = MessageDelivery::withContext([])
        ->push()
        ->to('device-token-data')
        ->text('New assignment available')
        ->with([
            'title' => 'Assignment Alert',
            'data' => ['type' => 'assignment', 'id' => '123', 'subject' => 'Math'],
        ])
        ->send();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| TEST 4: PUSH WITH PRIORITY
|--------------------------------------------------------------------------
*/

it('sends push with priority', function (): void {
    $result = MessageDelivery::withContext([])
        ->push()
        ->to('device-token-prio')
        ->text('High priority push.')
        ->with(['title' => 'Emergency'])
        ->priority('high')
        ->send();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| TEST 5: PUSH QUEUE INTEGRATION
|--------------------------------------------------------------------------
*/

it('queues push and returns queued status', function (): void {
    $result = MessageDelivery::withContext([])
        ->push()
        ->to('device-token-queue')
        ->text('This push is queued.')
        ->with(['title' => 'Queued'])
        ->queue();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->status->toBe('queued')
        ->isQueued()->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| TEST 6: QUEUE OPTIONS
|--------------------------------------------------------------------------
*/

it('passes queue options through the public api', function (): void {
    $result = MessageDelivery::withContext([])
        ->push()
        ->to('device-token-qopts')
        ->text('Push with queue options.')
        ->with(['title' => 'Queue Options'])
        ->delay(60)
        ->onQueue('push')
        ->tries(5)
        ->queue();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->status->toBe('queued')
        ->isQueued()->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| TEST 7: CONTEXT PROPAGATION
|--------------------------------------------------------------------------
*/

it('propagates context through push delivery', function (): void {
    $result = MessageDelivery::withContext([
        'tenant_id' => 'tenant-1',
        'school_id' => 'school-1',
    ])
        ->push()
        ->to('device-token-ctx')
        ->text('Context propagation test.')
        ->with(['title' => 'Context'])
        ->send();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue();
});
