<?php

declare(strict_types=1);

use SchoolPalm\MessageDelivery\Channels\InAppChannel;
use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Models\DatabaseNotification;
use SchoolPalm\MessageDelivery\Providers\InApp\Database\DatabaseNotificationProvider;

/*
|--------------------------------------------------------------------------
| Channel Delegation
|--------------------------------------------------------------------------
*/

it('in-app channel delegates to database notification provider', function (): void {
    $channel = new InAppChannel();
    $provider = new DatabaseNotificationProvider([]);
    $message = new Message(
        channel: 'in_app',
        recipients: [
            ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 1],
        ],
        text: 'Test in-app channel delegation.',
        data: ['title' => 'Channel Test'],
    );

    $result = $channel->send(message: $message, provider: $provider);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('database-notifications');

    $notification = DatabaseNotification::first();

    expect($notification)->not->toBeNull()
        ->and($notification->title)->toBe('Channel Test')
        ->and($notification->body)->toBe('Test in-app channel delegation.');
});

/*
|--------------------------------------------------------------------------
| Priority
|--------------------------------------------------------------------------
*/

it('in-app channel supports priority', function (): void {
    $channel = new InAppChannel();
    $provider = new DatabaseNotificationProvider([]);
    $message = new Message(
        channel: 'in_app',
        recipients: [
            ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 1],
        ],
        text: 'Priority in-app test.',
        data: ['title' => 'Priority Test'],
        priority: 'high',
    );

    $result = $channel->send(message: $message, provider: $provider);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue();

    $notification = DatabaseNotification::first();

    expect($notification->data['priority'])->toBe('high');
});

/*
|--------------------------------------------------------------------------
| Metadata / Context
|--------------------------------------------------------------------------
*/

it('in-app channel supports metadata', function (): void {
    $channel = new InAppChannel();
    $provider = new DatabaseNotificationProvider([]);
    $message = new Message(
        channel: 'in_app',
        recipients: [
            ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 1],
        ],
        text: 'Metadata test.',
        data: ['title' => 'Metadata Test'],
        priority: 'high',
        context: ['tenant_id' => 'tenant-1', 'school_id' => 'school-1'],
    );

    $result = $channel->send(message: $message, provider: $provider);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue();

    $notification = DatabaseNotification::first();

    expect($notification->data)->toHaveKey('context')
        ->and($notification->data['context']['tenant_id'])->toBe('tenant-1');
});

/*
|--------------------------------------------------------------------------
| Provider Override
|--------------------------------------------------------------------------
*/

it('in-app channel supports provider override', function (): void {
    $channel = new InAppChannel();
    $provider = new DatabaseNotificationProvider([]);
    $message = new Message(
        channel: 'in_app',
        recipients: [
            ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 1],
        ],
        text: 'Provider override test.',
        data: ['title' => 'Override Test'],
        provider: 'database-notifications',
    );

    $result = $channel->send(message: $message, provider: $provider);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('database-notifications');
});

/*
|--------------------------------------------------------------------------
| Channel Validation
|--------------------------------------------------------------------------
*/

it('in-app channel throws exception for incompatible provider', function (): void {
    $channel = new InAppChannel();
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
        channel: 'in_app',
        recipients: ['user-1'],
        text: 'Test.',
    );

    expect(fn() => $channel->send($message, $provider))
        ->toThrow(\InvalidArgumentException::class);
});
