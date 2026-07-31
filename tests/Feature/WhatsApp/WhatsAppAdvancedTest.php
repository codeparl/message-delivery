<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Providers\WhatsApp\Meta\MetaWhatsAppProvider;

/*
|--------------------------------------------------------------------------
| Delivery Result Behaviour
|--------------------------------------------------------------------------
*/

it('whatsapp returns delivery result with correct status', function (): void {
    Http::fake([
        'https://graph.facebook.com/*' => Http::response([
            'messages' => [
                ['id' => 'wamid.META-DR'],
            ],
        ], 200),
    ]);

    $provider = new MetaWhatsAppProvider([
        'access_token' => 'EAAxtesttoken123',
        'phone_number_id' => '123456789',
    ]);
    $message = new Message(
        channel: 'whatsapp',
        recipients: ['+254712345678'],
        text: 'Delivery result test.',
    );

    $result = $provider->send($message);

    expect($result->status)->toBe('sent')
        ->and($result->isSuccessful())->toBeTrue()
        ->and($result->isFailed())->toBeFalse()
        ->and($result->isQueued())->toBeFalse();
});
