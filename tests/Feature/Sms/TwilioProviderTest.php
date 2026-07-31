<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Providers\Sms\Twilio\TwilioProvider;

/*
|--------------------------------------------------------------------------
| Successful SMS Delivery
|--------------------------------------------------------------------------
*/

it('twilio sends sms and returns success delivery result', function (): void {
    Http::fake([
        'https://api.twilio.com/*' => Http::response([
            'sid' => 'SM' . str_repeat('x', 32),
            'status' => 'sent',
            'price' => '-0.05',
            'price_unit' => 'USD',
        ], 200),
    ]);

    $provider = new TwilioProvider([
        'sid' => 'AC' . str_repeat('x', 32),
        'token' => str_repeat('x', 32),
        'from' => '+1234567890',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678'],
        text: 'Hello from Twilio!',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->status->toBe('sent')
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('twilio-sms');
});

/*
|--------------------------------------------------------------------------
| Multiple Recipients
|--------------------------------------------------------------------------
*/

it('twilio sends to multiple recipients', function (): void {
    Http::fake([
        'https://api.twilio.com/*' => Http::sequence()
            ->push(['sid' => 'SM' . str_repeat('a', 32), 'status' => 'sent'], 200)
            ->push(['sid' => 'SM' . str_repeat('b', 32), 'status' => 'sent'], 200),
    ]);

    $provider = new TwilioProvider([
        'sid' => 'AC' . str_repeat('x', 32),
        'token' => str_repeat('x', 32),
        'from' => '+1234567890',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678', '+254798765432'],
        text: 'Hello everyone from Twilio!',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('twilio-sms');
});

/*
|--------------------------------------------------------------------------
| Unicode Support
|--------------------------------------------------------------------------
*/

it('twilio supports unicode characters', function (): void {
    Http::fake([
        'https://api.twilio.com/*' => Http::response([
            'sid' => 'SM' . str_repeat('x', 32),
            'status' => 'sent',
        ], 200),
    ]);

    $provider = new TwilioProvider([
        'sid' => 'AC' . str_repeat('x', 32),
        'token' => str_repeat('x', 32),
        'from' => '+1234567890',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678'],
        text: 'Hello in French: Ça va bien? Merci!',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('twilio-sms');
});

/*
|--------------------------------------------------------------------------
| HTTP Request Verification
|--------------------------------------------------------------------------
*/

it('twilio sends correct http request', function (): void {
    Http::fake();

    $provider = new TwilioProvider([
        'sid' => 'AC' . str_repeat('x', 32),
        'token' => str_repeat('x', 32),
        'from' => '+1234567890',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678'],
        text: 'Verify Twilio HTTP request.',
    );

    $provider->send($message);

    Http::assertSent(function (Request $request): bool {
        return str_contains($request->url(), 'api.twilio.com')
            && $request->method() === 'POST'
            && $request['To'] === '+254712345678'
            && $request['From'] === '+1234567890'
            && $request['Body'] === 'Verify Twilio HTTP request.';
    });
});

it('twilio sends correct authentication', function (): void {
    Http::fake();

    $provider = new TwilioProvider([
        'sid' => 'AC' . str_repeat('x', 32),
        'token' => 'auth_token_123',
        'from' => '+1234567890',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678'],
        text: 'Verify Twilio auth.',
    );

    $provider->send($message);

    Http::assertSent(function (Request $request): bool {
        $authHeader = $request->header('Authorization')[0] ?? '';
        return str_starts_with($authHeader, 'Basic ');
    });
});
