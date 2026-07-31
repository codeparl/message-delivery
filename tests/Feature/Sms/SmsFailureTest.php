<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Providers\Sms\AfricasTalking\AfricasTalkingProvider;
use SchoolPalm\MessageDelivery\Providers\Sms\EgoSms\EgoSmsProvider;
use SchoolPalm\MessageDelivery\Providers\Sms\Twilio\TwilioProvider;

/*
|--------------------------------------------------------------------------
| Failure Response
|--------------------------------------------------------------------------
*/

it('egosms returns failure when api returns error', function (): void {
    Http::fake([
        '*' => Http::response(null, 500),
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
        text: 'This will fail.',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isFailed()->toBeTrue()
        ->and($result->provider)->toBe('egosms')
        ->and($result->error)->not->toBeNull();
});

it('twilio returns failure when api returns error', function (): void {
    Http::fake([
        'https://api.twilio.com/*' => Http::response(null, 401),
    ]);

    $provider = new TwilioProvider([
        'sid' => 'AC' . str_repeat('x', 32),
        'token' => str_repeat('x', 32),
        'from' => '+1234567890',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678'],
        text: 'This will fail.',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isFailed()->toBeTrue()
        ->and($result->provider)->toBe('twilio-sms')
        ->and($result->error)->not->toBeNull();
});

it('africas talking returns failure when api returns error', function (): void {
    Http::fake([
        'https://api.africastalking.com/*' => Http::response(null, 403),
    ]);

    $provider = new AfricasTalkingProvider([
        'api_key' => 'test_api_key',
        'username' => 'test_user',
        'sender_id' => 'TESTSMS',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678'],
        text: 'This will fail.',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isFailed()->toBeTrue()
        ->and($result->provider)->toBe('africas-talking')
        ->and($result->error)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Invalid Credentials
|--------------------------------------------------------------------------
*/

it('egosms returns failure for invalid credentials', function (): void {
    Http::fake([
        '*' => Http::response([
            'error' => 'Invalid credentials',
        ], 401),
    ]);

    $provider = new EgoSmsProvider([
        'api_url' => 'https://api.egosms.co.ke/api/v1/send',
        'username' => 'wrong_user',
        'password' => 'wrong_pass',
        'sender_id' => 'TESTSMS',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678'],
        text: 'Test with invalid credentials.',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isFailed()->toBeTrue()
        ->and($result->error)->toContain('401');
});

it('twilio returns failure for invalid auth token', function (): void {
    Http::fake([
        'https://api.twilio.com/*' => Http::response([
            'code' => 20003,
            'message' => 'Authentication Error',
        ], 401),
    ]);

    $provider = new TwilioProvider([
        'sid' => 'AC' . str_repeat('x', 32),
        'token' => 'wrong_token',
        'from' => '+1234567890',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678'],
        text: 'Test with invalid token.',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isFailed()->toBeTrue()
        ->and($result->error)->toContain('401');
});

it('africas talking returns failure for invalid api key', function (): void {
    Http::fake([
        'https://api.africastalking.com/*' => Http::response([
            'error' => 'Invalid API Key',
        ], 401),
    ]);

    $provider = new AfricasTalkingProvider([
        'api_key' => 'wrong_key',
        'username' => 'test_user',
        'sender_id' => 'TESTSMS',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678'],
        text: 'Test with invalid API key.',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isFailed()->toBeTrue()
        ->and($result->error)->toContain('401');
});

/*
|--------------------------------------------------------------------------
| Timeout Handling
|--------------------------------------------------------------------------
*/

it('handles timeout gracefully', function (): void {
    // Simulate a timeout by faking a gateway timeout response.
    Http::fake([
        '*' => Http::response(null, 408),
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
        text: 'Timeout test.',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isFailed()->toBeTrue()
        ->and($result->error)->not->toBeNull();
});
