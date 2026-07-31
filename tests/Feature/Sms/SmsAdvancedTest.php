<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Providers\Sms\EgoSms\EgoSmsProvider;

/*
|--------------------------------------------------------------------------
| Delivery Result Behaviour
|--------------------------------------------------------------------------
*/

it('returns delivery result with correct status', function (): void {
    Http::fake([
        '*' => Http::response([
            'message_id' => 'MSG-DR',
            'status' => 'success',
        ], 200),
    ]);

    $provider = new EgoSmsProvider([
        'api_url' => 'https://api.egosms.co.ke/api/v1/send',
        'username' => 'test_user',
        'password' => 'test_pass',
        'sender_id' => 'TESTSMS',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678'],
        text: 'Delivery result test.',
    );

    $result = $provider->send($message);

    expect($result->status)->toBe('sent')
        ->and($result->isSuccessful())->toBeTrue()
        ->and($result->isFailed())->toBeFalse()
        ->and($result->isQueued())->toBeFalse();
});
