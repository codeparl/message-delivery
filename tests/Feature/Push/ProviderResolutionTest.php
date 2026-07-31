<?php

declare(strict_types=1);

use SchoolPalm\MessageDelivery\Providers\Push\Firebase\FirebasePushFactory;
use SchoolPalm\MessageDelivery\Providers\Push\Firebase\FirebasePushProvider;

/*
|--------------------------------------------------------------------------
| Provider Resolution
|--------------------------------------------------------------------------
*/

it('firebase push resolves provider from factory', function (): void {
    $factory = new FirebasePushFactory();
    $configuration = [
        'credentials_json' => '{"type":"service_account","client_email":"test@project.iam.gserviceaccount.com","private_key":"-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCy\n-----END PRIVATE KEY-----\n"}',
        'project_id' => 'test-project-123',
    ];
    $provider = $factory->create($configuration);

    expect($provider)->toBeInstanceOf(FirebasePushProvider::class);
    expect($provider->name())->toBe('firebase-push');
    expect($provider->channel())->toBe('push');
});
