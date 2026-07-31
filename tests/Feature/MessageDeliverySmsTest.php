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
                    'sms' => 'egosms',
                    default => null,
                };
            }

            public function configurationFor(string $channel, string $provider): array
            {
                return match ($provider) {
                    'egosms' => [
                        'api_url' => 'https://api.egosms.co.ke/api/v1/send',
                        'username' => 'test_user',
                        'password' => 'test_pass',
                        'sender_id' => 'TESTSMS',
                    ],
                    'twilio-sms' => [
                        'sid' => 'AC' . str_repeat('x', 32),
                        'token' => str_repeat('x', 32),
                        'from' => '+1234567890',
                    ],
                    'africas-talking' => [
                        'api_key' => 'test_api_key',
                        'username' => 'test_user',
                        'sender_id' => 'TESTSMS',
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
        '*' => Http::response([
            'message_id' => 'MSG-API-TEST',
            'status' => 'success',
        ], 200),
    ]);

    Queue::fake();
});

/*
|--------------------------------------------------------------------------
| TEST 1: SEND SMS THROUGH PUBLIC API
|--------------------------------------------------------------------------
*/

it('sends sms through public api', function (): void {
    $result = MessageDelivery::withContext([])
        ->sms()
        ->to('+254712345678')
        ->text('Hello student')
        ->send();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->status->toBe('sent')
        ->provider->toBe('egosms');
});

/*
|--------------------------------------------------------------------------
| TEST 2: SMS WITH TEXT
|--------------------------------------------------------------------------
*/

it('sends sms with text content', function (): void {
    $result = MessageDelivery::withContext([])
        ->sms()
        ->to('+254712345678')
        ->text('Your school fees are due.')
        ->send();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->status->toBe('sent');
});

/*
|--------------------------------------------------------------------------
| TEST 3: SMS TO MULTIPLE RECIPIENTS
|--------------------------------------------------------------------------
*/

it('sends sms to multiple recipients through public api', function (): void {
    $result = MessageDelivery::withContext([])
        ->sms()
        ->to(['+254712345678', '+254798765432'])
        ->text('Hello everyone.')
        ->send();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| TEST 4: SMS WITH PROVIDER OVERRIDE
|--------------------------------------------------------------------------
*/

it('sends sms with provider override', function (): void {
    Http::fake([
        'https://api.twilio.com/*' => Http::response([
            'sid' => 'SM' . str_repeat('x', 32),
            'status' => 'sent',
        ], 200),
    ]);

    $result = MessageDelivery::withContext([])
        ->sms()
        ->to('+254712345678')
        ->text('Override provider test.')
        ->provider('twilio-sms')
        ->send();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->provider->toBe('twilio-sms');
});

/*
|--------------------------------------------------------------------------
| TEST 5: SMS WITH PRIORITY
|--------------------------------------------------------------------------
*/

it('sends sms with priority', function (): void {
    $result = MessageDelivery::withContext([])
        ->sms()
        ->to('+254712345678')
        ->text('High priority SMS.')
        ->priority('high')
        ->send();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| TEST 6: SMS QUEUE INTEGRATION
|--------------------------------------------------------------------------
*/

it('queues sms and returns queued status', function (): void {
    $result = MessageDelivery::withContext([])
        ->sms()
        ->to('+254712345678')
        ->text('This SMS is queued.')
        ->queue();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->status->toBe('queued')
        ->isQueued()->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| TEST 7: QUEUE OPTIONS
|--------------------------------------------------------------------------
*/

it('passes queue options through the public api', function (): void {
    $result = MessageDelivery::withContext([])
        ->sms()
        ->to('+254712345678')
        ->text('SMS with queue options.')
        ->delay(60)
        ->onQueue('sms')
        ->tries(5)
        ->queue();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->status->toBe('queued')
        ->isQueued()->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| TEST 8: CONTEXT PROPAGATION
|--------------------------------------------------------------------------
*/

it('propagates context through sms delivery', function (): void {
    $result = MessageDelivery::withContext([
        'tenant_id' => 'tenant-1',
        'school_id' => 'school-1',
    ])
        ->sms()
        ->to('+254712345678')
        ->text('Context propagation test.')
        ->send();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| TEST 9: MULTIPLE PROVIDERS VIA API
|--------------------------------------------------------------------------
*/

it('supports provider override to switch between sms providers', function (): void {
    // Test with EgoSMS (default)
    $result = MessageDelivery::withContext([])
        ->sms()
        ->to('+254712345678')
        ->text('Test with EgoSMS.')
        ->send();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->provider->toBe('egosms');

    // Test with provider override to Twilio
    Http::fake([
        'https://api.twilio.com/*' => Http::response([
            'sid' => 'SM' . str_repeat('x', 32),
            'status' => 'sent',
        ], 200),
    ]);

    $result2 = MessageDelivery::withContext([])
        ->sms()
        ->to('+254712345678')
        ->text('Test with Twilio override.')
        ->provider('twilio-sms')
        ->send();

    expect($result2)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->provider->toBe('twilio-sms');
});
