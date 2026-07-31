<?php

declare(strict_types=1);

use SchoolPalm\MessageDelivery\Providers\WhatsApp\Meta\MetaWhatsAppProvider;
use SchoolPalm\MessageDelivery\Providers\WhatsApp\Twilio\TwilioWhatsAppProvider;

/*
|--------------------------------------------------------------------------
| Meta WhatsApp Configuration Validation
|--------------------------------------------------------------------------
*/

it('meta whatsapp returns not configured when required fields are missing', function (): void {
    $provider = new MetaWhatsAppProvider([]);
    expect($provider->configured())->toBeFalse();
});

it('meta whatsapp returns not configured when access_token is missing', function (): void {
    $provider = new MetaWhatsAppProvider([
        'phone_number_id' => '123456789',
    ]);
    expect($provider->configured())->toBeFalse();
});

it('meta whatsapp returns not configured when phone_number_id is missing', function (): void {
    $provider = new MetaWhatsAppProvider([
        'access_token' => 'EAAxtesttoken123',
    ]);
    expect($provider->configured())->toBeFalse();
});

it('meta whatsapp returns configured when all fields are present', function (): void {
    $provider = new MetaWhatsAppProvider([
        'access_token' => 'EAAxtesttoken123',
        'phone_number_id' => '123456789',
    ]);
    expect($provider->configured())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Twilio WhatsApp Configuration Validation
|--------------------------------------------------------------------------
*/

it('twilio whatsapp returns not configured when sid is missing', function (): void {
    $provider = new TwilioWhatsAppProvider([]);
    expect($provider->configured())->toBeFalse();
});

it('twilio whatsapp returns not configured when token is missing', function (): void {
    $provider = new TwilioWhatsAppProvider([
        'sid' => 'AC' . str_repeat('x', 32),
        'from' => '+1234567890',
    ]);
    expect($provider->configured())->toBeFalse();
});

it('twilio whatsapp returns configured when all fields are present', function (): void {
    $provider = new TwilioWhatsAppProvider([
        'sid' => 'AC' . str_repeat('x', 32),
        'token' => str_repeat('x', 32),
        'from' => '+1234567890',
    ]);
    expect($provider->configured())->toBeTrue();
});

it('twilio whatsapp returns not configured when from is empty string', function (): void {
    $provider = new TwilioWhatsAppProvider([
        'sid' => 'AC' . str_repeat('x', 32),
        'token' => str_repeat('x', 32),
        'from' => '',
    ]);
    expect($provider->configured())->toBeFalse();
});
