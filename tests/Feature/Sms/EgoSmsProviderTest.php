<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Providers\Sms\EgoSms\EgoSmsProvider;

/*
|--------------------------------------------------------------------------
| Successful SMS Delivery
|--------------------------------------------------------------------------
*/

it('egosms sends sms and returns success delivery result', function (): void {
    Http::fake([
        '*' => Http::response([
            'message_id' => 'MSG-456',
            'status' => 'success',
        ], 200),
    ]);

    $provider = new EgoSmsProvider([
        'api_url' => 'https://api.egosms.co.ke/api/v1/send',
        'username' => 'test_user',
        'password' => 'test_pass',
        'sender_id' => 'TESTSMS',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678'],
        text: 'Hello, this is a test SMS.',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->status->toBe('sent')
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('egosms')
        ->and($result->providerMessageId)->toBe('MSG-456');
});

/*
|--------------------------------------------------------------------------
| Multiple Recipients
|--------------------------------------------------------------------------
*/

it('egosms sends to multiple recipients', function (): void {
    Http::fake([
        '*' => Http::response([
            'message_id' => 'MSG-MULTI',
            'status' => 'success',
        ], 200),
    ]);

    $provider = new EgoSmsProvider([
        'api_url' => 'https://api.egosms.co.ke/api/v1/send',
        'username' => 'test_user',
        'password' => 'test_pass',
        'sender_id' => 'TESTSMS',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678', '+254798765432'],
        text: 'Hello everyone!',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('egosms');
});

/*
|--------------------------------------------------------------------------
| Unicode Support
|--------------------------------------------------------------------------
*/

it('egosms supports unicode characters', function (): void {
    Http::fake([
        '*' => Http::response([
            'message_id' => 'MSG-UNI',
            'status' => 'success',
        ], 200),
    ]);

    $provider = new EgoSmsProvider([
        'api_url' => 'https://api.egosms.co.ke/api/v1/send',
        'username' => 'test_user',
        'password' => 'test_pass',
        'sender_id' => 'TESTSMS',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678'],
        text: 'Hello in Swahili: Habari! Mambo vipi? 😊',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('egosms');
});

/*
|--------------------------------------------------------------------------
| HTTP Request Verification
|--------------------------------------------------------------------------
*/

it('egosms sends correct http request', function (): void {
    Http::fake();

    $provider = new EgoSmsProvider([
        'api_url' => 'https://api.egosms.co.ke/api/v1/send',
        'username' => 'test_user',
        'password' => 'test_pass',
        'sender_id' => 'TESTSMS',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678'],
        text: 'Verify HTTP request.',
    );

    $provider->send($message);

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.egosms.co.ke/api/v1/send'
            && $request->method() === 'POST'
            && $request['username'] === 'test_user'
            && $request['password'] === 'test_pass'
            && $request['senderid'] === 'TESTSMS'
            && $request['message'] === 'Verify HTTP request.'
            && $request['recipients'] === ['+254712345678'];
    });
});
