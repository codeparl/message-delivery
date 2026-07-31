<?php

declare(strict_types=1);

use SchoolPalm\MessageDelivery\Providers\Sms\AfricasTalking\AfricasTalkingProvider;
use SchoolPalm\MessageDelivery\Providers\Sms\EgoSms\EgoSmsProvider;
use SchoolPalm\MessageDelivery\Providers\Sms\Twilio\TwilioProvider;

/*
|--------------------------------------------------------------------------
| EgoSMS Configuration Validation
|--------------------------------------------------------------------------
*/

it('egosms returns not configured when required fields are missing', function (): void {
    $provider = new EgoSmsProvider([]);
    expect($provider->configured())->toBeFalse();
});

it('egosms returns not configured when api_url is missing', function (): void {
    $provider = new EgoSmsProvider([
        'username' => 'test',
        'password' => 'test',
        'sender_id' => 'TEST',
    ]);
    expect($provider->configured())->toBeFalse();
});

it('egosms returns configured when all fields are present', function (): void {
    $provider = new EgoSmsProvider([
        'api_url' => 'https://api.egosms.co.ke/api/v1/send',
        'username' => 'test_user',
        'password' => 'test_pass',
        'sender_id' => 'TESTSMS',
    ]);
    expect($provider->configured())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Twilio Configuration Validation
|--------------------------------------------------------------------------
*/

it('twilio returns not configured when sid is missing', function (): void {
    $provider = new TwilioProvider([]);
    expect($provider->configured())->toBeFalse();
});

it('twilio returns not configured when token is missing', function (): void {
    $provider = new TwilioProvider([
        'sid' => 'AC' . str_repeat('x', 32),
        'from' => '+1234567890',
    ]);
    expect($provider->configured())->toBeFalse();
});

it('twilio returns configured when all fields are present', function (): void {
    $provider = new TwilioProvider([
        'sid' => 'AC' . str_repeat('x', 32),
        'token' => str_repeat('x', 32),
        'from' => '+1234567890',
    ]);
    expect($provider->configured())->toBeTrue();
});

it('twilio returns not configured when from is empty string', function (): void {
    $provider = new TwilioProvider([
        'sid' => 'AC' . str_repeat('x', 32),
        'token' => str_repeat('x', 32),
        'from' => '',
    ]);
    expect($provider->configured())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Africa's Talking Configuration Validation
|--------------------------------------------------------------------------
*/

it('africas talking returns not configured when api_key is missing', function (): void {
    $provider = new AfricasTalkingProvider([]);
    expect($provider->configured())->toBeFalse();
});

it('africas talking returns not configured when username is missing', function (): void {
    $provider = new AfricasTalkingProvider([
        'api_key' => 'test_key',
    ]);
    expect($provider->configured())->toBeFalse();
});

it('africas talking returns configured when all required fields are present', function (): void {
    $provider = new AfricasTalkingProvider([
        'api_key' => 'test_api_key',
        'username' => 'test_user',
    ]);
    expect($provider->configured())->toBeTrue();
});
