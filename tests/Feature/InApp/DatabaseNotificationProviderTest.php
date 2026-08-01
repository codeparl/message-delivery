<?php

declare(strict_types=1);

use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Models\DatabaseNotification;
use SchoolPalm\MessageDelivery\Providers\InApp\Database\DatabaseNotificationFactory;
use SchoolPalm\MessageDelivery\Providers\InApp\Database\DatabaseNotificationProvider;

/*
|--------------------------------------------------------------------------
| Provider Resolution
|--------------------------------------------------------------------------
*/

it('database notification resolves provider from factory', function (): void {
    $factory = new DatabaseNotificationFactory();
    $configuration = [
        'default_notifiable' => 'App\Models\User',
    ];
    $provider = $factory->create($configuration);

    expect($provider)->toBeInstanceOf(DatabaseNotificationProvider::class);
    expect($provider->name())->toBe('database-notifications');
    expect($provider->channel())->toBe('in_app');
});

/*
|--------------------------------------------------------------------------
| Successful Notification Storage
|--------------------------------------------------------------------------
*/

it('stores notification and returns success delivery result', function (): void {
    $provider = new DatabaseNotificationProvider([]);
    $message = new Message(
        channel: 'in_app',
        recipients: [
            ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 1],
        ],
        text: 'Hello, this is a test notification.',
        data: ['title' => 'Test Notification'],
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->status->toBe('sent')
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('database-notifications')
        ->and($result->providerMessageId)->not->toBeNull();

    $notification = DatabaseNotification::first();

    expect($notification)->not->toBeNull()
        ->and($notification->title)->toBe('Test Notification')
        ->and($notification->body)->toBe('Hello, this is a test notification.')
        ->and($notification->notifiable_type)->toBe('App\Models\User')
        ->and($notification->notifiable_id)->toBe('1')
        ->and($notification->channel)->toBe('in_app')
        ->and($notification->provider)->toBe('database-notifications');
});

/*
|--------------------------------------------------------------------------
| Multiple Recipients
|--------------------------------------------------------------------------
*/

it('stores notifications for multiple recipients', function (): void {
    $provider = new DatabaseNotificationProvider([]);
    $message = new Message(
        channel: 'in_app',
        recipients: [
            ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 1],
            ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 2],
        ],
        text: 'Hello everyone!',
        data: ['title' => 'Broadcast'],
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('database-notifications');

    $notifications = DatabaseNotification::all();

    expect($notifications)->toHaveCount(2);
});

/*
|--------------------------------------------------------------------------
| Notification with Context
|--------------------------------------------------------------------------
*/

it('stores notification with context and priority', function (): void {
    $provider = new DatabaseNotificationProvider([]);
    $message = new Message(
        channel: 'in_app',
        recipients: [
            ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 1],
        ],
        text: 'Context test.',
        data: ['title' => 'Context Test'],
        priority: 'high',
        context: ['tenant_id' => 'tenant-1', 'school_id' => 'school-1'],
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue();

    $notification = DatabaseNotification::first();

    expect($notification)->not->toBeNull()
        ->and($notification->data)->toHaveKey('priority')
        ->and($notification->data['priority'])->toBe('high')
        ->and($notification->data)->toHaveKey('context')
        ->and($notification->data['context'])->toHaveKey('tenant_id')
        ->and($notification->data['context']['tenant_id'])->toBe('tenant-1');
});

/*
|--------------------------------------------------------------------------
| Mark as Read
|--------------------------------------------------------------------------
*/

it('can mark notification as read', function (): void {
    $provider = new DatabaseNotificationProvider([]);
    $message = new Message(
        channel: 'in_app',
        recipients: [
            ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 1],
        ],
        text: 'Read test.',
        data: ['title' => 'Read Test'],
    );

    $provider->send($message);

    $notification = DatabaseNotification::first();

    expect($notification->isRead())->toBeFalse();

    $notification->markAsRead();

    expect($notification->fresh()->isRead())->toBeTrue();

    $notification->markAsUnread();

    expect($notification->fresh()->isRead())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Provider Metadata
|--------------------------------------------------------------------------
*/

it('database notification returns correct metadata', function (): void {
    $provider = new DatabaseNotificationProvider([]);

    $metadata = $provider->metadata();

    expect($metadata)->toBeArray()
        ->and($metadata['name'])->toBe('database-notifications')
        ->and($metadata['label'])->toBe('Database Notifications')
        ->and($metadata['channel'])->toBe('in_app')
        ->and($metadata['capabilities'])->toContain('read_status')
        ->and($metadata['capabilities'])->toContain('unread_count')
        ->and($metadata['capabilities'])->toContain('metadata');
});

/*
|--------------------------------------------------------------------------
| Configuration
|--------------------------------------------------------------------------
*/

it('database notification is always configured', function (): void {
    $provider = new DatabaseNotificationProvider([]);
    expect($provider->configured())->toBeTrue();
});
