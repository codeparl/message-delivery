<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Providers\Sms\AfricasTalking\AfricasTalkingProvider;

/*
|--------------------------------------------------------------------------
| Successful SMS Delivery
|--------------------------------------------------------------------------
*/

it('africas talking sends sms and returns success delivery result', function (): void {
    Http::fake([
        'https://api.africastalking.com/*' => Http::response([
            'SMSMessageData' => [
                'messageId' => 'ATXid-456',
                'Recipients' => [
                    [
                        'number' => '+254712345678',
                        'status' => 'Success',
                        'cost' => 'KES 1.00',
                    ],
                ],
            ],
        ], 200),
    ]);

    $provider = new AfricasTalkingProvider([
        'api_key' => 'test_api_key',
        'username' => 'test_user',
        'sender_id' => 'TESTSMS',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678'],
        text: 'Hello from Africa\'s Talking!',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->status->toBe('sent')
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('africas-talking');
});

/*
|--------------------------------------------------------------------------
| Multiple Recipients
|--------------------------------------------------------------------------
*/

it('africas talking sends to multiple recipients', function (): void {
    Http::fake([
        'https://api.africastalking.com/*' => Http::response([
            'SMSMessageData' => [
                'messageId' => 'ATXid-MULTI',
                'Recipients' => [
                    ['number' => '+254712345678', 'status' => 'Success', 'cost' => 'KES 1.00'],
                    ['number' => '+254798765432', 'status' => 'Success', 'cost' => 'KES 1.00'],
                ],
            ],
        ], 200),
    ]);

    $provider = new AfricasTalkingProvider([
        'api_key' => 'test_api_key',
        'username' => 'test_user',
        'sender_id' => 'TESTSMS',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678', '+254798765432'],
        text: 'Hello everyone from AT!',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('africas-talking');
});

/*
|--------------------------------------------------------------------------
| Unicode Support
|--------------------------------------------------------------------------
*/

it('africas talking supports unicode characters', function (): void {
    Http::fake([
        'https://api.africastalking.com/*' => Http::response([
            'SMSMessageData' => [
                'messageId' => 'ATXid-UNI',
                'Recipients' => [
                    ['number' => '+254712345678', 'status' => 'Success', 'cost' => 'KES 1.00'],
                ],
            ],
        ], 200),
    ]);

    $provider = new AfricasTalkingProvider([
        'api_key' => 'test_api_key',
        'username' => 'test_user',
        'sender_id' => 'TESTSMS',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678'],
        text: 'Hello in Arabic: مرحبا بالعالم',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('africas-talking');
});

/*
|--------------------------------------------------------------------------
| HTTP Request Verification
|--------------------------------------------------------------------------
*/

it('africas talking sends correct http request', function (): void {
    Http::fake();

    $provider = new AfricasTalkingProvider([
        'api_key' => 'test_api_key',
        'username' => 'test_user',
        'sender_id' => 'TESTSMS',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678'],
        text: 'Verify AT HTTP request.',
    );

    $provider->send($message);

    Http::assertSent(function (Request $request): bool {
        return str_contains($request->url(), 'api.africastalking.com')
            && $request->method() === 'POST'
            && $request['username'] === 'test_user'
            && $request['to'] === '+254712345678'
            && $request['message'] === 'Verify AT HTTP request.'
            && $request['from'] === 'TESTSMS';
    });
});

it('africas talking sends correct authentication headers', function (): void {
    Http::fake();

    $provider = new AfricasTalkingProvider([
        'api_key' => 'test_api_key_123',
        'username' => 'test_user',
        'sender_id' => 'TESTSMS',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678'],
        text: 'Verify auth headers.',
    );

    $provider->send($message);

    Http::assertSent(function (Request $request): bool {
        return $request->hasHeader('apiKey')
            && $request->header('apiKey')[0] === 'test_api_key_123'
            && $request->hasHeader('Accept')
            && $request->header('Accept')[0] === 'application/json';
    });
});
