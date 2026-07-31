<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Providers\WhatsApp\Meta\MetaWhatsAppProvider;
use SchoolPalm\MessageDelivery\Providers\WhatsApp\Twilio\TwilioWhatsAppProvider;

/*
|--------------------------------------------------------------------------
| API Failure Response
|--------------------------------------------------------------------------
*/

it('meta whatsapp returns failure when api returns error', function (): void {
    Http::fake([
        'https://graph.facebook.com/*' => Http::response(null, 500),
    ]);

    $provider = new MetaWhatsAppProvider([
        'access_token' => 'EAAxtesttoken123',
        'phone_number_id' => '123456789',
    ]);
    $message = new Message(
        channel: 'whatsapp',
        recipients: ['+254712345678'],
        text: 'This will fail.',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isFailed()->toBeTrue()
        ->and($result->provider)->toBe('meta-whatsapp')
        ->and($result->error)->not->toBeNull();
});

it('twilio whatsapp returns failure when api returns error', function (): void {
    Http::fake([
        'https://api.twilio.com/*' => Http::response(null, 401),
    ]);

    $provider = new TwilioWhatsAppProvider([
        'sid' => 'AC' . str_repeat('x', 32),
        'token' => str_repeat('x', 32),
        'from' => '+1234567890',
    ]);
    $message = new Message(
        channel: 'whatsapp',
        recipients: ['+254712345678'],
        text: 'This will fail.',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isFailed()->toBeTrue()
        ->and($result->provider)->toBe('twilio-whatsapp')
        ->and($result->error)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Invalid Credentials
|--------------------------------------------------------------------------
*/

it('meta whatsapp returns failure for invalid access token', function (): void {
    Http::fake([
        'https://graph.facebook.com/*' => Http::response([
            'error' => [
                'message' => 'Invalid OAuth access token',
                'type' => 'OAuthException',
            ],
        ], 401),
    ]);

    $provider = new MetaWhatsAppProvider([
        'access_token' => 'EAAxinvalidtoken',
        'phone_number_id' => '123456789',
    ]);
    $message = new Message(
        channel: 'whatsapp',
        recipients: ['+254712345678'],
        text: 'Test with invalid token.',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isFailed()->toBeTrue()
        ->and($result->error)->toContain('401');
});

it('twilio whatsapp returns failure for invalid auth token', function (): void {
    Http::fake([
        'https://api.twilio.com/*' => Http::response([
            'code' => 20003,
            'message' => 'Authentication Error',
        ], 401),
    ]);

    $provider = new TwilioWhatsAppProvider([
        'sid' => 'AC' . str_repeat('x', 32),
        'token' => 'wrong_token',
        'from' => '+1234567890',
    ]);
    $message = new Message(
        channel: 'whatsapp',
        recipients: ['+254712345678'],
        text: 'Test with invalid token.',
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

it('meta whatsapp handles timeout gracefully', function (): void {
    Http::fake([
        'https://graph.facebook.com/*' => function () {
            throw new ConnectionException('Timeout');
        },
    ]);

    $provider = new MetaWhatsAppProvider([
        'access_token' => 'EAAxtesttoken123',
        'phone_number_id' => '123456789',
    ]);
    $message = new Message(
        channel: 'whatsapp',
        recipients: ['+254712345678'],
        text: 'Timeout test.',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isFailed()->toBeTrue()
        ->and($result->error)->not->toBeNull();
});

it('twilio whatsapp handles timeout gracefully', function (): void {
    Http::fake([
        'https://api.twilio.com/*' => function () {
            throw new ConnectionException('Timeout');
        },
    ]);

    $provider = new TwilioWhatsAppProvider([
        'sid' => 'AC' . str_repeat('x', 32),
        'token' => str_repeat('x', 32),
        'from' => '+1234567890',
    ]);
    $message = new Message(
        channel: 'whatsapp',
        recipients: ['+254712345678'],
        text: 'Timeout test.',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isFailed()->toBeTrue()
        ->and($result->error)->not->toBeNull();
});
