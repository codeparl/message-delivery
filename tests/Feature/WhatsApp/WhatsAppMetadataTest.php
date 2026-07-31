<?php

declare(strict_types=1);

use SchoolPalm\MessageDelivery\Providers\WhatsApp\Meta\MetaWhatsAppProvider;
use SchoolPalm\MessageDelivery\Providers\WhatsApp\Twilio\TwilioWhatsAppProvider;

/*
|--------------------------------------------------------------------------
| Provider Metadata
|--------------------------------------------------------------------------
*/

it('meta whatsapp returns correct metadata', function (): void {
    $provider = new MetaWhatsAppProvider([
        'access_token' => 'EAAxtesttoken123',
        'phone_number_id' => '123456789',
    ]);

    $metadata = $provider->metadata();

    expect($metadata)->toBeArray()
        ->and($metadata['name'])->toBe('meta-whatsapp')
        ->and($metadata['label'])->toBe('Meta WhatsApp Cloud API')
        ->and($metadata['channel'])->toBe('whatsapp')
        ->and($metadata['capabilities'])->toContain('text')
        ->and($metadata['capabilities'])->toContain('template')
        ->and($metadata['capabilities'])->toContain('media')
        ->and($metadata['capabilities'])->toContain('unicode')
        ->and($metadata['capabilities'])->toContain('delivery_status');
});

it('twilio whatsapp returns correct metadata', function (): void {
    $provider = new TwilioWhatsAppProvider([
        'sid' => 'AC' . str_repeat('x', 32),
        'token' => str_repeat('x', 32),
        'from' => '+1234567890',
    ]);

    $metadata = $provider->metadata();

    expect($metadata)->toBeArray()
        ->and($metadata['name'])->toBe('twilio-whatsapp')
        ->and($metadata['label'])->toBe('Twilio WhatsApp')
        ->and($metadata['channel'])->toBe('whatsapp')
        ->and($metadata['capabilities'])->toContain('text')
        ->and($metadata['capabilities'])->toContain('media')
        ->and($metadata['capabilities'])->toContain('unicode')
        ->and($metadata['capabilities'])->toContain('delivery_status');
});
