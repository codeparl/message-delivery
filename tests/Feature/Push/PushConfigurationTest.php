<?php

declare(strict_types=1);

use SchoolPalm\MessageDelivery\Providers\Push\Firebase\FirebasePushProvider;

/*
|--------------------------------------------------------------------------
| Firebase Push Configuration Validation
|--------------------------------------------------------------------------
*/

it('firebase push returns not configured when required fields are missing', function (): void {
    $provider = new FirebasePushProvider([]);
    expect($provider->configured())->toBeFalse();
});

it('firebase push returns not configured when credentials_json is missing', function (): void {
    $provider = new FirebasePushProvider([
        'project_id' => 'test-project',
    ]);
    expect($provider->configured())->toBeFalse();
});

it('firebase push returns not configured when project_id is missing', function (): void {
    $provider = new FirebasePushProvider([
        'credentials_json' => '{"client_email":"test@test.iam.gserviceaccount.com"}',
    ]);
    expect($provider->configured())->toBeFalse();
});

it('firebase push returns not configured when credentials_json is empty', function (): void {
    $provider = new FirebasePushProvider([
        'credentials_json' => '',
        'project_id' => 'test-project',
    ]);
    expect($provider->configured())->toBeFalse();
});

it('firebase push returns configured when all required fields are present', function (): void {
    $provider = new FirebasePushProvider([
        'credentials_json' => '{"client_email":"test@test.iam.gserviceaccount.com"}',
        'project_id' => 'test-project',
    ]);
    expect($provider->configured())->toBeTrue();
});
