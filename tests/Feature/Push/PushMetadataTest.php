<?php

declare(strict_types=1);

use SchoolPalm\MessageDelivery\Providers\Push\Firebase\FirebasePushProvider;

/*
|--------------------------------------------------------------------------
| Provider Metadata
|--------------------------------------------------------------------------
*/

it('firebase push returns correct metadata', function (): void {
    $provider = new FirebasePushProvider([
        'credentials_json' => '{"client_email":"test@test.iam.gserviceaccount.com"}',
        'project_id' => 'test-project',
    ]);

    $metadata = $provider->metadata();

    expect($metadata)->toBeArray()
        ->and($metadata['name'])->toBe('firebase-push')
        ->and($metadata['label'])->toBe('Firebase Cloud Messaging')
        ->and($metadata['channel'])->toBe('push')
        ->and($metadata['capabilities'])->toContain('notification')
        ->and($metadata['capabilities'])->toContain('data')
        ->and($metadata['capabilities'])->toContain('topics')
        ->and($metadata['capabilities'])->toContain('images')
        ->and($metadata['capabilities'])->toContain('actions')
        ->and($metadata['capabilities'])->toContain('delivery_status');
});
