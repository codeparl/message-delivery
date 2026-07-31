<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Providers\Push\Firebase\FirebasePushProvider;

/*
|--------------------------------------------------------------------------
| Successful Push Delivery
|--------------------------------------------------------------------------
*/

it('firebase push sends notification and returns success delivery result', function (): void {
    Http::fake([
        'https://fcm.googleapis.com/*' => Http::response([
            'name' => 'projects/test-project/messages/0:171234567890',
        ], 200),
    ]);

    $provider = new FirebasePushProvider([
        'credentials_json' => '{"type":"service_account","client_email":"test@project.iam.gserviceaccount.com"}',
        'project_id' => 'test-project',
        'access_token' => 'test-access-token',
    ]);
    $message = new Message(
        channel: 'push',
        recipients: ['device-token-456'],
        text: 'Hello, this is a test push notification.',
        data: ['title' => 'Test Push'],
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->status->toBe('sent')
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('firebase-push')
        ->and($result->providerMessageId)->toBe('projects/test-project/messages/0:171234567890');
});

/*
|--------------------------------------------------------------------------
| Multiple Recipients
|--------------------------------------------------------------------------
*/

it('firebase push sends to multiple recipients', function (): void {
    Http::fake([
        'https://fcm.googleapis.com/v1/projects/test-project/messages:send' => Http::sequence()
            ->push(['name' => 'projects/test-project/messages/msg-1'], 200)
            ->push(['name' => 'projects/test-project/messages/msg-2'], 200),
    ]);

    $provider = new FirebasePushProvider([
        'credentials_json' => '{"type":"service_account","client_email":"test@project.iam.gserviceaccount.com"}',
        'project_id' => 'test-project',
        'access_token' => 'test-access-token',
    ]);
    $message = new Message(
        channel: 'push',
        recipients: ['device-token-1', 'device-token-2'],
        text: 'Hello everyone!',
        data: ['title' => 'Multi'],
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('firebase-push');
});

/*
|--------------------------------------------------------------------------
| Unicode Support
|--------------------------------------------------------------------------
*/

it('firebase push supports unicode characters', function (): void {
    Http::fake([
        'https://fcm.googleapis.com/*' => Http::response([
            'name' => 'projects/test-project/messages/uni-1',
        ], 200),
    ]);

    $provider = new FirebasePushProvider([
        'credentials_json' => '{"type":"service_account","client_email":"test@project.iam.gserviceaccount.com"}',
        'project_id' => 'test-project',
        'access_token' => 'test-access-token',
    ]);
    $message = new Message(
        channel: 'push',
        recipients: ['device-token-uni'],
        text: 'Hello in Swahili: Habari! Mambo vipi? 😊',
        data: ['title' => 'Unicode Test 🌍'],
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('firebase-push');
});

/*
|--------------------------------------------------------------------------
| HTTP Request Verification
|--------------------------------------------------------------------------
*/

it('firebase push sends correct http request', function (): void {
    Http::fake([
        'https://fcm.googleapis.com/*' => Http::response(),
    ]);

    $provider = new FirebasePushProvider([
        'credentials_json' => '{"type":"service_account","client_email":"test@project.iam.gserviceaccount.com"}',
        'project_id' => 'test-project',
        'access_token' => 'test-access-token',
    ]);
    $message = new Message(
        channel: 'push',
        recipients: ['device-token-http'],
        text: 'Verify HTTP request body.',
        data: [
            'title' => 'HTTP Test',
            'data' => ['type' => 'assignment', 'id' => '123'],
        ],
    );

    $provider->send($message);

    Http::assertSent(function (Request $request): bool {
        $body = $request->data();

        return $request->method() === 'POST'
            && str_contains($request->url(), '/v1/projects/test-project/messages:send')
            && isset($body['message']['token'])
            && $body['message']['token'] === 'device-token-http'
            && $body['message']['notification']['title'] === 'HTTP Test'
            && $body['message']['notification']['body'] === 'Verify HTTP request body.'
            && $body['message']['data']['type'] === 'assignment'
            && $body['message']['data']['id'] === '123';
    });
});

it('firebase push sends correct authorization headers', function (): void {
    Http::fake([
        'https://fcm.googleapis.com/*' => Http::response(),
    ]);

    $provider = new FirebasePushProvider([
        'credentials_json' => '{"type":"service_account","client_email":"test@project.iam.gserviceaccount.com"}',
        'project_id' => 'test-project',
        'access_token' => 'test-specific-token-abc',
    ]);
    $message = new Message(
        channel: 'push',
        recipients: ['device-token-auth'],
        text: 'Verify auth.',
        data: ['title' => 'Auth'],
    );

    $provider->send($message);

    Http::assertSent(function (Request $request): bool {
        return $request->hasHeader('Authorization')
            && $request->header('Authorization')[0] === 'Bearer test-specific-token-abc';
    });
});
