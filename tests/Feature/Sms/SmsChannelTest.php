<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use SchoolPalm\MessageDelivery\Channels\SmsChannel;
use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Providers\Sms\AfricasTalking\AfricasTalkingProvider;
use SchoolPalm\MessageDelivery\Providers\Sms\EgoSms\EgoSmsProvider;
use SchoolPalm\MessageDelivery\Providers\Sms\Twilio\TwilioProvider;

/*
|--------------------------------------------------------------------------
| Channel Delegation
|--------------------------------------------------------------------------
*/

it('sms channel delegates to egosms provider', function (): void {
    Http::fake([
        '*' => Http::response([
            'message_id' => 'MSG-123',
            'status' => 'success',
        ], 200),
    ]);

    $channel = new SmsChannel();
    $provider = new EgoSmsProvider([
        'api_url' => 'https://api.egosms.co.ke/api/v1/send',
        'username' => 'test_user',
        'password' => 'test_pass',
        'sender_id' => 'TESTSMS',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678'],
        text: 'Test SMS channel delegation.',
    );

    $result = $channel->send(message: $message, provider: $provider);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('egosms');
});

it('sms channel delegates to twilio provider', function (): void {
    Http::fake([
        'https://api.twilio.com/*' => Http::response([
            'sid' => 'SM' . str_repeat('x', 32),
            'status' => 'sent',
        ], 200),
    ]);

    $channel = new SmsChannel();
    $provider = new TwilioProvider([
        'sid' => 'AC' . str_repeat('x', 32),
        'token' => str_repeat('x', 32),
        'from' => '+1234567890',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678'],
        text: 'Test SMS via Twilio.',
    );

    $result = $channel->send(message: $message, provider: $provider);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('twilio-sms');
});

it('sms channel delegates to africas talking provider', function (): void {
    Http::fake([
        'https://api.africastalking.com/*' => Http::response([
            'SMSMessageData' => [
                'messageId' => 'ATXid-123',
                'Recipients' => [
                    [
                        'number' => '+254712345678',
                        'status' => 'Success',
                        'cost' => 'KES 1.00',
                    ],
                ],
            ],
        ], 200),
    ]);

    $channel = new SmsChannel();
    $provider = new AfricasTalkingProvider([
        'api_key' => 'test_api_key',
        'username' => 'test_user',
        'sender_id' => 'TESTSMS',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678'],
        text: 'Test SMS via Africa\'s Talking.',
    );

    $result = $channel->send(message: $message, provider: $provider);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('africas-talking');
});

/*
|--------------------------------------------------------------------------
| Provider Override
|--------------------------------------------------------------------------
*/

it('sms channel supports provider override', function (): void {
    Http::fake([
        'https://api.twilio.com/*' => Http::response([
            'sid' => 'SM' . str_repeat('x', 32),
            'status' => 'sent',
        ], 200),
    ]);

    $channel = new SmsChannel();
    $provider = new TwilioProvider([
        'sid' => 'AC' . str_repeat('x', 32),
        'token' => str_repeat('x', 32),
        'from' => '+1234567890',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678'],
        text: 'Provider override test.',
        provider: 'twilio-sms',
    );

    $result = $channel->send(message: $message, provider: $provider);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('twilio-sms');
});

/*
|--------------------------------------------------------------------------
| Priority
|--------------------------------------------------------------------------
*/

it('sms channel supports priority', function (): void {
    Http::fake([
        '*' => Http::response([
            'message_id' => 'MSG-PRIO',
            'status' => 'success',
        ], 200),
    ]);

    $channel = new SmsChannel();
    $provider = new EgoSmsProvider([
        'api_url' => 'https://api.egosms.co.ke/api/v1/send',
        'username' => 'test_user',
        'password' => 'test_pass',
        'sender_id' => 'TESTSMS',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678'],
        text: 'Priority SMS test.',
        priority: 'high',
    );

    $result = $channel->send(message: $message, provider: $provider);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Metadata
|--------------------------------------------------------------------------
*/

it('sms channel supports metadata', function (): void {
    Http::fake([
        '*' => Http::response([
            'message_id' => 'MSG-META',
            'status' => 'success',
        ], 200),
    ]);

    $channel = new SmsChannel();
    $provider = new EgoSmsProvider([
        'api_url' => 'https://api.egosms.co.ke/api/v1/send',
        'username' => 'test_user',
        'password' => 'test_pass',
        'sender_id' => 'TESTSMS',
    ]);
    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678'],
        text: 'Metadata test.',
        priority: 'high',
        context: ['tenant_id' => 'tenant-1', 'school_id' => 'school-1'],
    );

    $result = $channel->send(message: $message, provider: $provider);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Channel Validation
|--------------------------------------------------------------------------
*/

it('sms channel throws exception for incompatible provider', function (): void {
    $channel = new SmsChannel();
    $provider = new class implements MessageProvider {
        public function name(): string
        {
            return 'fake-provider';
        }
        public function channel(): string
        {
            return 'email';
        }
        public function send(Message $message): DeliveryResult
        {
            return DeliveryResult::success('fake');
        }
        public function configured(): bool
        {
            return true;
        }
        public function metadata(): array
        {
            return [];
        }
    };

    $message = new Message(
        channel: 'sms',
        recipients: ['+254712345678'],
        text: 'Test.',
    );

    expect(fn() => $channel->send($message, $provider))
        ->toThrow(\InvalidArgumentException::class);
});
