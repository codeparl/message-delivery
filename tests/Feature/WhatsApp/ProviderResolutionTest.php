<?php

declare(strict_types=1);

use SchoolPalm\MessageDelivery\Providers\WhatsApp\Meta\MetaWhatsAppFactory;
use SchoolPalm\MessageDelivery\Providers\WhatsApp\Meta\MetaWhatsAppProvider;
use SchoolPalm\MessageDelivery\Providers\WhatsApp\Twilio\TwilioWhatsAppFactory;
use SchoolPalm\MessageDelivery\Providers\WhatsApp\Twilio\TwilioWhatsAppProvider;

/*
|--------------------------------------------------------------------------
| Provider Resolution
|--------------------------------------------------------------------------
*/

it('meta whatsapp resolves provider from factory', function (): void {
    $factory = new MetaWhatsAppFactory();
    $configuration = [
        'access_token' => 'EAAxtesttoken123',
        'phone_number_id' => '123456789',
    ];
    $provider = $factory->create($configuration);

    expect($provider)->toBeInstanceOf(MetaWhatsAppProvider::class);
    expect($provider->name())->toBe('meta-whatsapp');
    expect($provider->channel())->toBe('whatsapp');
});

it('twilio whatsapp resolves provider from factory', function (): void {
    $factory = new TwilioWhatsAppFactory();
    $configuration = [
        'sid' => 'AC' . str_repeat('x', 32),
        'token' => str_repeat('x', 32),
        'from' => '+1234567890',
    ];
    $provider = $factory->create($configuration);

    expect($provider)->toBeInstanceOf(TwilioWhatsAppProvider::class);
    expect($provider->name())->toBe('twilio-whatsapp');
    expect($provider->channel())->toBe('whatsapp');
});
