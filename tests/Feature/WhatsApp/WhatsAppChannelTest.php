<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use SchoolPalm\MessageDelivery\Channels\WhatsAppChannel;
use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Providers\WhatsApp\Meta\MetaWhatsAppProvider;
use SchoolPalm\MessageDelivery\Providers\WhatsApp\Twilio\TwilioWhatsAppProvider;

/*
|--------------------------------------------------------------------------
| Channel Delegation
|--------------------------------------------------------------------------
*/

it('whatsapp channel delegates to meta provider', function (): void {
    Http::fake([
        'https://graph.facebook.com/*' => Http::response([
            'messages' => [
                ['id' => 'wamid.META-123'],
            ],
        ], 200),
    ]);

    $channel = new WhatsAppChannel();
    $provider = new MetaWhatsAppProvider([
        'access_token' => 'EAAxtesttoken123',
        'phone_number_id' => '123456789',
    ]);
    $message = new Message(
        channel: 'whatsapp',
        recipients: ['+254712345678'],
        text: 'Test WhatsApp channel delegation.',
    );

    $result = $channel->send(message: $message, provider: $provider);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('meta-whatsapp');
});

it('whatsapp channel delegates to twilio provider', function (): void {
    Http::fake([
        'https://api.twilio.com/*' => Http::response([
            'sid' => 'SM' . str_repeat('x', 32),
            'status' => 'sent',
        ], 200),
    ]);

    $channel = new WhatsAppChannel();
    $provider = new TwilioWhatsAppProvider([
        'sid' => 'AC' . str_repeat('x', 32),
        'token' => str_repeat('x', 32),
        'from' => '+1234567890',
    ]);
    $message = new Message(
        channel: 'whatsapp',
        recipients: ['+254712345678'],
        text: 'Test WhatsApp via Twilio.',
    );

    $result = $channel->send(message: $message, provider: $provider);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('twilio-whatsapp');
});

/*
|--------------------------------------------------------------------------
| Provider Override
|--------------------------------------------------------------------------
*/

it('whatsapp channel supports provider override', function (): void {
    Http::fake([
        'https://graph.facebook.com/*' => Http::response([
            'messages' => [
                ['id' => 'wamid.META-OVERRIDE'],
            ],
        ], 200),
    ]);

    $channel = new WhatsAppChannel();
    $provider = new MetaWhatsAppProvider([
        'access_token' => 'EAAxtesttoken123',
        'phone_number_id' => '123456789',
    ]);
    $message = new Message(
        channel: 'whatsapp',
        recipients: ['+254712345678'],
        text: 'Provider override test.',
        provider: 'meta-whatsapp',
    );

    $result = $channel->send(message: $message, provider: $provider);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('meta-whatsapp');
});

/*
|--------------------------------------------------------------------------
| Priority
|--------------------------------------------------------------------------
*/

it('whatsapp channel supports priority', function (): void {
    Http::fake([
        'https://graph.facebook.com/*' => Http::response([
            'messages' => [
                ['id' => 'wamid.META-PRIO'],
            ],
        ], 200),
    ]);

    $channel = new WhatsAppChannel();
    $provider = new MetaWhatsAppProvider([
        'access_token' => 'EAAxtesttoken123',
        'phone_number_id' => '123456789',
    ]);
    $message = new Message(
        channel: 'whatsapp',
        recipients: ['+254712345678'],
        text: 'Priority WhatsApp test.',
        priority: 'high',
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

it('whatsapp channel supports metadata', function (): void {
    Http::fake([
        'https://graph.facebook.com/*' => Http::response([
            'messages' => [
                ['id' => 'wamid.META-META'],
            ],
        ], 200),
    ]);

    $channel = new WhatsAppChannel();
    $provider = new MetaWhatsAppProvider([
        'access_token' => 'EAAxtesttoken123',
        'phone_number_id' => '123456789',
    ]);
    $message = new Message(
        channel: 'whatsapp',
        recipients: ['+254712345678'],
        text: 'Metadata test.',
        priority: 'high',
        context: ['tenant_id' => 'tenant-1', 'school_id' => 'school-1'],
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

it('whatsapp channel throws exception for incompatible provider', function (): void {
    $channel = new WhatsAppChannel();
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
        channel: 'whatsapp',
        recipients: ['+254712345678'],
        text: 'Test.',
    );

    expect(fn() => $channel->send($message, $provider))
        ->toThrow(\InvalidArgumentException::class);
});
