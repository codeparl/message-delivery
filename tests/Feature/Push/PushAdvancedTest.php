<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Providers\Push\Firebase\FirebasePushProvider;

/*
|--------------------------------------------------------------------------
| Delivery Result Behaviour
|--------------------------------------------------------------------------
*/

it('push returns delivery result with correct status', function (): void {
    Http::fake([
        'https://fcm.googleapis.com/*' => Http::response([
            'name' => 'projects/test-project/messages/msg-dr',
        ], 200),
    ]);

    $provider = new FirebasePushProvider([
        'credentials_json' => '{"type":"service_account","client_email":"test@project.iam.gserviceaccount.com"}',
        'project_id' => 'test-project',
        'access_token' => 'test-access-token',
    ]);
    $message = new Message(
        channel: 'push',
        recipients: ['device-token-dr'],
        text: 'Delivery result test.',
        data: ['title' => 'DR Test'],
    );

    $result = $provider->send($message);

    expect($result->status)->toBe('sent')
        ->and($result->isSuccessful())->toBeTrue()
        ->and($result->isFailed())->toBeFalse()
        ->and($result->isQueued())->toBeFalse();
});
