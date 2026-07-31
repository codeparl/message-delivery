<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use SchoolPalm\MessageDelivery\Channels\PushChannel;
use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Providers\Push\Firebase\FirebasePushProvider;

/*
|--------------------------------------------------------------------------
| Channel Delegation
|--------------------------------------------------------------------------
*/

it('push channel delegates to firebase provider', function (): void {
    Http::fake([
        'https://fcm.googleapis.com/*' => Http::response([
            'name' => 'projects/test-project/messages/0:171234567890',
        ], 200),
    ]);

    $channel = new PushChannel();
    $provider = new FirebasePushProvider([
        'credentials_json' => '{"type":"service_account","client_email":"test@project.iam.gserviceaccount.com"}',
        'project_id' => 'test-project',
        'access_token' => 'test-access-token',
    ]);
    $message = new Message(
        channel: 'push',
        recipients: ['device-token-123'],
        text: 'Test push channel delegation.',
        data: ['title' => 'Test Title'],
    );

    $result = $channel->send(message: $message, provider: $provider);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('firebase-push');
});

/*
|--------------------------------------------------------------------------
| Provider Override
|--------------------------------------------------------------------------
*/

it('push channel supports provider override', function (): void {
    Http::fake([
        'https://fcm.googleapis.com/*' => Http::response([
            'name' => 'projects/test-project/messages/0:171234567890',
        ], 200),
    ]);

    $channel = new PushChannel();
    $provider = new FirebasePushProvider([
        'credentials_json' => '{"type":"service_account","client_email":"test@project.iam.gserviceaccount.com"}',
        'project_id' => 'test-project',
        'access_token' => 'test-access-token',
    ]);
    $message = new Message(
        channel: 'push',
        recipients: ['device-token-123'],
        text: 'Provider override test.',
        provider: 'firebase-push',
        data: ['title' => 'Override'],
    );

    $result = $channel->send(message: $message, provider: $provider);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('firebase-push');
});

/*
|--------------------------------------------------------------------------
| Priority
|--------------------------------------------------------------------------
*/

it('push channel supports priority', function (): void {
    Http::fake([
        'https://fcm.googleapis.com/*' => Http::response([
            'name' => 'projects/test-project/messages/0:171234567890',
        ], 200),
    ]);

    $channel = new PushChannel();
    $provider = new FirebasePushProvider([
        'credentials_json' => '{"type":"service_account","client_email":"test@project.iam.gserviceaccount.com"}',
        'project_id' => 'test-project',
        'access_token' => 'test-access-token',
    ]);
    $message = new Message(
        channel: 'push',
        recipients: ['device-token-123'],
        text: 'Priority push test.',
        priority: 'high',
        data: ['title' => 'Priority'],
    );

    $result = $channel->send(message: $message, provider: $provider);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Metadata
|--------------------------------------------------------------------------
*/

it('push channel supports metadata', function (): void {
    Http::fake([
        'https://fcm.googleapis.com/*' => Http::response([
            'name' => 'projects/test-project/messages/0:171234567890',
        ], 200),
    ]);

    $channel = new PushChannel();
    $provider = new FirebasePushProvider([
        'credentials_json' => '{"type":"service_account","client_email":"test@project.iam.gserviceaccount.com"}',
        'project_id' => 'test-project',
        'access_token' => 'test-access-token',
    ]);
    $message = new Message(
        channel: 'push',
        recipients: ['device-token-123'],
        text: 'Metadata test.',
        priority: 'high',
        context: ['tenant_id' => 'tenant-1', 'school_id' => 'school-1'],
        data: ['title' => 'Metadata'],
    );

    $result = $channel->send(message: $message, provider: $provider);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Channel Validation
|--------------------------------------------------------------------------
*/

it('push channel throws exception for incompatible provider', function (): void {
    $channel = new PushChannel();
    $provider = new class implements MessageProvider {
        public function name(): string
        {
            return 'fake-provider';
        }
        public function channel(): string
        {
            return 'email';
        }
        public function send(Message $message): DeliveryResult
        {
            return DeliveryResult::success('fake');
        }
        public function configured(): bool
        {
            return true;
        }
        public function metadata(): array
        {
            return [];
        }
    };

    $message = new Message(
        channel: 'push',
        recipients: ['device-token-123'],
        text: 'Test.',
    );

    expect(fn() => $channel->send($message, $provider))
        ->toThrow(\InvalidArgumentException::class);
});
