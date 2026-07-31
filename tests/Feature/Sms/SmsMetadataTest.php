<?php

declare(strict_types=1);

use SchoolPalm\MessageDelivery\Providers\Sms\AfricasTalking\AfricasTalkingProvider;
use SchoolPalm\MessageDelivery\Providers\Sms\EgoSms\EgoSmsProvider;
use SchoolPalm\MessageDelivery\Providers\Sms\Twilio\TwilioProvider;

/*
|--------------------------------------------------------------------------
| Provider Metadata
|--------------------------------------------------------------------------
*/

it('egosms returns correct metadata', function (): void {
    $provider = new EgoSmsProvider([
        'api_url' => 'https://api.egosms.co.ke/api/v1/send',
        'username' => 'test_user',
        'password' => 'test_pass',
        'sender_id' => 'TESTSMS',
    ]);

    $metadata = $provider->metadata();

    expect($metadata)->toBeArray()
        ->and($metadata['name'])->toBe('egosms')
        ->and($metadata['label'])->toBe('EgoSMS')
        ->and($metadata['channel'])->toBe('sms')
        ->and($metadata['capabilities'])->toContain('unicode')
        ->and($metadata['capabilities'])->toContain('delivery_reports');
});

it('twilio returns correct metadata', function (): void {
    $provider = new TwilioProvider([
        'sid' => 'AC' . str_repeat('x', 32),
        'token' => str_repeat('x', 32),
        'from' => '+1234567890',
    ]);

    $metadata = $provider->metadata();

    expect($metadata)->toBeArray()
        ->and($metadata['name'])->toBe('twilio-sms')
        ->and($metadata['label'])->toBe('Twilio SMS')
        ->and($metadata['channel'])->toBe('sms')
        ->and($metadata['capabilities'])->toContain('unicode')
        ->and($metadata['capabilities'])->toContain('delivery_status');
});

it('africas talking returns correct metadata', function (): void {
    $provider = new AfricasTalkingProvider([
        'api_key' => 'test_api_key',
        'username' => 'test_user',
    ]);

    $metadata = $provider->metadata();

    expect($metadata)->toBeArray()
        ->and($metadata['name'])->toBe('africas-talking')
        ->and($metadata['label'])->toBe('Africa\'s Talking')
        ->and($metadata['channel'])->toBe('sms')
        ->and($metadata['capabilities'])->toContain('bulk')
        ->and($metadata['capabilities'])->toContain('unicode');
});
