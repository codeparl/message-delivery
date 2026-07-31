<?php

declare(strict_types=1);

use SchoolPalm\MessageDelivery\Providers\Sms\AfricasTalking\AfricasTalkingFactory;
use SchoolPalm\MessageDelivery\Providers\Sms\AfricasTalking\AfricasTalkingProvider;
use SchoolPalm\MessageDelivery\Providers\Sms\EgoSms\EgoSmsFactory;
use SchoolPalm\MessageDelivery\Providers\Sms\EgoSms\EgoSmsProvider;
use SchoolPalm\MessageDelivery\Providers\Sms\Twilio\TwilioFactory;
use SchoolPalm\MessageDelivery\Providers\Sms\Twilio\TwilioProvider;

/*
|--------------------------------------------------------------------------
| Provider Resolution
|--------------------------------------------------------------------------
*/

it('egosms resolves provider from factory', function (): void {
    $factory = new EgoSmsFactory();
    $configuration = [
        'api_url' => 'https://api.egosms.co.ke/api/v1/send',
        'username' => 'test_user',
        'password' => 'test_pass',
        'sender_id' => 'TESTSMS',
    ];
    $provider = $factory->create($configuration);

    expect($provider)->toBeInstanceOf(EgoSmsProvider::class);
    expect($provider->name())->toBe('egosms');
    expect($provider->channel())->toBe('sms');
});

it('twilio resolves provider from factory', function (): void {
    $factory = new TwilioFactory();
    $configuration = [
        'sid' => 'AC' . str_repeat('x', 32),
        'token' => str_repeat('x', 32),
        'from' => '+1234567890',
    ];
    $provider = $factory->create($configuration);

    expect($provider)->toBeInstanceOf(TwilioProvider::class);
    expect($provider->name())->toBe('twilio-sms');
    expect($provider->channel())->toBe('sms');
});

it('africas talking resolves provider from factory', function (): void {
    $factory = new AfricasTalkingFactory();
    $configuration = [
        'api_key' => 'test_api_key',
        'username' => 'test_user',
        'sender_id' => 'TESTSMS',
    ];
    $provider = $factory->create($configuration);

    expect($provider)->toBeInstanceOf(AfricasTalkingProvider::class);
    expect($provider->name())->toBe('africas-talking');
    expect($provider->channel())->toBe('sms');
});
