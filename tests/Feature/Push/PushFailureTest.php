<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Providers\Push\Firebase\FirebasePushProvider;

/*
|--------------------------------------------------------------------------
| API Failure Response
|--------------------------------------------------------------------------
*/

it('firebase push returns failure when api returns error', function (): void {
    Http::fake([
        'https://fcm.googleapis.com/*' => Http::response(null, 500),
    ]);

    $provider = new FirebasePushProvider([
        'credentials_json' => '{"type":"service_account","client_email":"test@project.iam.gserviceaccount.com"}',
        'project_id' => 'test-project',
        'access_token' => 'test-access-token',
    ]);
    $message = new Message(
        channel: 'push',
        recipients: ['device-token-fail'],
        text: 'This will fail.',
        data: ['title' => 'Fail'],
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isFailed()->toBeTrue()
        ->and($result->provider)->toBe('firebase-push')
        ->and($result->error)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Invalid Credentials
|--------------------------------------------------------------------------
*/

it('firebase push returns failure for invalid credentials json', function (): void {
    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response([
            'error' => 'invalid_grant',
            'error_description' => 'Invalid JWT Signature',
        ], 400),
    ]);

    $provider = new FirebasePushProvider([
        'credentials_json' => '{"type":"service_account","client_email":"invalid@project.iam.gserviceaccount.com","private_key":"invalid-key"}',
        'project_id' => 'test-project',
        'access_token' => 'test-access-token',
    ]);
    $message = new Message(
        channel: 'push',
        recipients: ['device-token-bad'],
        text: 'Test with invalid credentials.',
        data: ['title' => 'Bad Auth'],
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isFailed()->toBeTrue()
        ->and($result->error)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Timeout Handling
|--------------------------------------------------------------------------
*/

it('firebase push handles timeout gracefully', function (): void {
    Http::fake([
        'https://fcm.googleapis.com/*' => function () {
            throw new ConnectionException('Timeout');
        },
    ]);

    $provider = new FirebasePushProvider([
        'credentials_json' => '{"type":"service_account","client_email":"test@project.iam.gserviceaccount.com"}',
        'project_id' => 'test-project',
        'access_token' => 'test-access-token',
    ]);
    $message = new Message(
        channel: 'push',
        recipients: ['device-token-timeout'],
        text: 'Timeout test.',
        data: ['title' => 'Timeout'],
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isFailed()->toBeTrue()
        ->and($result->error)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Invalid Project ID
|--------------------------------------------------------------------------
*/

it('firebase push returns failure for invalid project id', function (): void {
    Http::fake([
        'https://fcm.googleapis.com/*' => Http::response([
            'error' => [
                'code' => 404,
                'message' => 'Project not found.',
            ],
        ], 404),
    ]);

    $provider = new FirebasePushProvider([
        'credentials_json' => '{"type":"service_account","client_email":"test@project.iam.gserviceaccount.com"}',
        'project_id' => 'invalid-project',
        'access_token' => 'test-access-token',
    ]);
    $message = new Message(
        channel: 'push',
        recipients: ['device-token-404'],
        text: 'Test invalid project.',
        data: ['title' => '404'],
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isFailed()->toBeTrue()
        ->and($result->provider)->toBe('firebase-push')
        ->and($result->error)->toContain('404');
});
