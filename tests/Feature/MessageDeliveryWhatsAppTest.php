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
                    'whatsapp' => 'twilio-whatsapp',
                    default => null,
                };
            }

            public function configurationFor(string $channel, string $provider): array
            {
                return match ($provider) {
                    'meta-whatsapp' => [
                        'access_token' => 'EAAxtesttoken123',
                        'phone_number_id' => '123456789',
                    ],
                    'twilio-whatsapp' => [
                        'sid' => 'AC' . str_repeat('x', 32),
                        'token' => str_repeat('x', 32),
                        'from' => '+1234567890',
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
        'https://api.twilio.com/*' => Http::response([
            'sid' => 'SM' . str_repeat('x', 32),
            'status' => 'sent',
        ], 200),
    ]);

    Queue::fake();
});

/*
|--------------------------------------------------------------------------
| TEST 1: SEND WHATSAPP THROUGH PUBLIC API
|--------------------------------------------------------------------------
*/

it('sends whatsapp through public api', function (): void {
    $result = MessageDelivery::withContext([])
        ->whatsapp()
        ->to('+254712345678')
        ->text('Hello student via WhatsApp')
        ->send();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->status->toBe('sent')
        ->provider->toBe('twilio-whatsapp');
});

/*
|--------------------------------------------------------------------------
| TEST 2: WHATSAPP WITH TEXT
|--------------------------------------------------------------------------
*/

it('sends whatsapp with text content', function (): void {
    $result = MessageDelivery::withContext([])
        ->whatsapp()
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
| TEST 3: WHATSAPP WITH PROVIDER OVERRIDE
|--------------------------------------------------------------------------
*/

it('sends whatsapp with provider override', function (): void {
    Http::fake([
        'https://graph.facebook.com/*' => Http::response([
            'messages' => [
                ['id' => 'wamid.META-API'],
            ],
        ], 200),
    ]);

    $result = MessageDelivery::withContext([])
        ->whatsapp()
        ->to('+254712345678')
        ->text('Override provider test.')
        ->provider('meta-whatsapp')
        ->send();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->provider->toBe('meta-whatsapp');
});

/*
|--------------------------------------------------------------------------
| TEST 4: WHATSAPP WITH PRIORITY
|--------------------------------------------------------------------------
*/

it('sends whatsapp with priority', function (): void {
    $result = MessageDelivery::withContext([])
        ->whatsapp()
        ->to('+254712345678')
        ->text('High priority WhatsApp.')
        ->priority('high')
        ->send();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| TEST 5: WHATSAPP QUEUE INTEGRATION
|--------------------------------------------------------------------------
*/

it('queues whatsapp and returns queued status', function (): void {
    $result = MessageDelivery::withContext([])
        ->whatsapp()
        ->to('+254712345678')
        ->text('This WhatsApp is queued.')
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
        ->whatsapp()
        ->to('+254712345678')
        ->text('WhatsApp with queue options.')
        ->delay(60)
        ->onQueue('whatsapp')
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

it('propagates context through whatsapp delivery', function (): void {
    $result = MessageDelivery::withContext([
        'tenant_id' => 'tenant-1',
        'school_id' => 'school-1',
    ])
        ->whatsapp()
        ->to('+254712345678')
        ->text('Context propagation test.')
        ->send();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| TEST 8: MULTIPLE PROVIDERS VIA API
|--------------------------------------------------------------------------
*/

it('supports provider override to switch between whatsapp providers', function (): void {
    // Test with Twilio WhatsApp (default)
    $result = MessageDelivery::withContext([])
        ->whatsapp()
        ->to('+254712345678')
        ->text('Test with Twilio WhatsApp.')
        ->send();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->provider->toBe('twilio-whatsapp');

    // Test with provider override to Meta
    Http::fake([
        'https://graph.facebook.com/*' => Http::response([
            'messages' => [
                ['id' => 'wamid.META-API2'],
            ],
        ], 200),
    ]);

    $result2 = MessageDelivery::withContext([])
        ->whatsapp()
        ->to('+254712345678')
        ->text('Test with Meta override.')
        ->provider('meta-whatsapp')
        ->send();

    expect($result2)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->provider->toBe('meta-whatsapp');
});
