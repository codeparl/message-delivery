<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Providers\WhatsApp\Meta\MetaWhatsAppProvider;

/*
|--------------------------------------------------------------------------
| Successful WhatsApp Delivery
|--------------------------------------------------------------------------
*/

it('meta whatsapp sends message and returns success delivery result', function (): void {
    Http::fake([
        'https://graph.facebook.com/*' => Http::response([
            'messages' => [
                ['id' => 'wamid.META-456'],
            ],
        ], 200),
    ]);

    $provider = new MetaWhatsAppProvider([
        'access_token' => 'EAAxtesttoken123',
        'phone_number_id' => '123456789',
    ]);
    $message = new Message(
        channel: 'whatsapp',
        recipients: ['+254712345678'],
        text: 'Hello, this is a test WhatsApp message.',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->status->toBe('sent')
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('meta-whatsapp')
        ->and($result->providerMessageId)->toBe('wamid.META-456');
});

/*
|--------------------------------------------------------------------------
| Multiple Recipients
|--------------------------------------------------------------------------
*/

it('meta whatsapp sends to multiple recipients', function (): void {
    Http::fake([
        'https://graph.facebook.com/*' => Http::response([
            'messages' => [
                ['id' => 'wamid.META-MULTI'],
            ],
        ], 200),
    ]);

    $provider = new MetaWhatsAppProvider([
        'access_token' => 'EAAxtesttoken123',
        'phone_number_id' => '123456789',
    ]);
    $message = new Message(
        channel: 'whatsapp',
        recipients: ['+254712345678', '+254798765432'],
        text: 'Hello everyone!',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('meta-whatsapp');
});

/*
|--------------------------------------------------------------------------
| Unicode Support
|--------------------------------------------------------------------------
*/

it('meta whatsapp supports unicode characters', function (): void {
    Http::fake([
        'https://graph.facebook.com/*' => Http::response([
            'messages' => [
                ['id' => 'wamid.META-UNI'],
            ],
        ], 200),
    ]);

    $provider = new MetaWhatsAppProvider([
        'access_token' => 'EAAxtesttoken123',
        'phone_number_id' => '123456789',
    ]);
    $message = new Message(
        channel: 'whatsapp',
        recipients: ['+254712345678'],
        text: 'Hello in Swahili: Habari! Mambo vipi? 😊',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('meta-whatsapp');
});

/*
|--------------------------------------------------------------------------
| HTTP Request Verification
|--------------------------------------------------------------------------
*/

it('meta whatsapp sends correct http request', function (): void {
    Http::fake();

    $provider = new MetaWhatsAppProvider([
        'access_token' => 'EAAxtesttoken123',
        'phone_number_id' => '123456789',
    ]);
    $message = new Message(
        channel: 'whatsapp',
        recipients: ['+254712345678'],
        text: 'Verify HTTP request.',
    );

    $provider->send($message);

    Http::assertSent(function (Request $request): bool {
        return str_contains($request->url(), 'graph.facebook.com')
            && str_contains($request->url(), '/123456789/messages')
            && $request->method() === 'POST'
            && $request['messaging_product'] === 'whatsapp'
            && $request['recipient_type'] === 'individual'
            && $request['to'] === '+254712345678'
            && $request['type'] === 'text'
            && $request['text']['body'] === 'Verify HTTP request.';
    });
});

it('meta whatsapp sends correct authentication headers', function (): void {
    Http::fake();

    $provider = new MetaWhatsAppProvider([
        'access_token' => 'EAAxsecrettoken456',
        'phone_number_id' => '123456789',
    ]);
    $message = new Message(
        channel: 'whatsapp',
        recipients: ['+254712345678'],
        text: 'Verify auth headers.',
    );

    $provider->send($message);

    Http::assertSent(function (Request $request): bool {
        return $request->hasHeader('Authorization')
            && $request->header('Authorization')[0] === 'Bearer EAAxsecrettoken456'
            && $request->hasHeader('Accept')
            && $request->header('Accept')[0] === 'application/json'
            && $request->hasHeader('Content-Type')
            && $request->header('Content-Type')[0] === 'application/json';
    });
});
