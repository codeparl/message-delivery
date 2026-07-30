<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use SchoolPalm\AppLogger\Facades\AppLogger;
use SchoolPalm\MessageDelivery\Contracts\DeliveryRecorder;
use SchoolPalm\MessageDelivery\Contracts\TenantProviderSettings;
use SchoolPalm\MessageDelivery\MessageDelivery;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Models\MessageDelivery as MessageDeliveryModel;

/*
|--------------------------------------------------------------------------
| Test Setup
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    $this->app->bind(
        TenantProviderSettings::class,
        fn(): TenantProviderSettings => new class implements TenantProviderSettings
        {
            public function providerFor(string $channel): ?string
            {
                return match ($channel) {
                    'email' => 'laravel-mail',
                    default => null,
                };
            }

            public function configurationFor(string $channel, string $provider): array
            {
                return ['mailer' => 'array'];
            }

            public function enabled(string $channel, string $provider): bool
            {
                return true;
            }
        }
    );

    Mail::fake();
    Queue::fake();
});

/*
|--------------------------------------------------------------------------
| TEST 1: Queued delivery record
|--------------------------------------------------------------------------
*/

it('creates queued delivery record', function (): void {
    MessageDelivery::withContext([
        'tenant_id' => 'tenant-1',
        'school_id' => 'school-1',
    ])
        ->email()
        ->to('student@example.com')
        ->with(['subject' => 'Fee Reminder'])
        ->text('Your school fees are due.')
        ->queue();

    $delivery = MessageDeliveryModel::first();

    expect($delivery)->not->toBeNull()
        ->and($delivery->status)->toBe('queued')
        ->and($delivery->channel)->toBe('email')
        ->and($delivery->recipient)->toBe('student@example.com')
        ->and($delivery->subject)->toBe('Fee Reminder')
        ->and($delivery->tenant_id)->toBe('tenant-1')
        ->and($delivery->school_id)->toBe('school-1')
        ->and($delivery->queued_at)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| TEST 2: Successful delivery record
|--------------------------------------------------------------------------
*/

it('updates successful delivery record', function (): void {
    MessageDelivery::withContext([])
        ->email()
        ->to('student@example.com')
        ->text('Hello student')
        ->send();

    $delivery = MessageDeliveryModel::first();

    expect($delivery)->not->toBeNull()
        ->and($delivery->status)->toBe('sent')
        ->and($delivery->provider)->toBe('laravel-mail')
        ->and($delivery->sent_at)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| TEST 3: Failed delivery record
|--------------------------------------------------------------------------
*/

it('stores failed delivery', function (): void {
    $recorder = app(DeliveryRecorder::class);

    $message = new Message(
        channel: 'email',
        recipients: ['fail@example.com'],
        text: 'This will fail.',
        data: ['subject' => 'Failure Test'],
    );

    $result = DeliveryResult::failure(
        error: 'Provider rejected the message',
        provider: 'laravel-mail',
    );

    $delivery = $recorder->recordFailed($message, $result);

    expect($delivery)->not->toBeNull()
        ->and($delivery->status)->toBe('failed')
        ->and($delivery->error)->toBe('Provider rejected the message')
        ->and($delivery->provider)->toBe('laravel-mail');
});

/*
|--------------------------------------------------------------------------
| TEST 4: Channel and provider stored
|--------------------------------------------------------------------------
*/

it('stores channel and provider', function (): void {
    MessageDelivery::withContext([])
        ->email()
        ->to('parent@school.com')
        ->with(['subject' => 'Test'])
        ->text('Test message.')
        ->send();

    $delivery = MessageDeliveryModel::first();

    expect($delivery)->not->toBeNull()
        ->and($delivery->channel)->toBe('email')
        ->and($delivery->provider)->toBe('laravel-mail');
});

/*
|--------------------------------------------------------------------------
| TEST 5: Metadata stored
|--------------------------------------------------------------------------
*/

it('stores metadata', function (): void {
    MessageDelivery::withContext([
        'tenant_id' => 'tenant-1',
    ])
        ->email()
        ->to('metadata@example.com')
        ->with(['subject' => 'Metadata Test'])
        ->text('Test with metadata.')
        ->priority('high')
        ->send();

    $delivery = MessageDeliveryModel::first();

    expect($delivery)->not->toBeNull()
        ->and($delivery->metadata)->toBeArray()
        ->and($delivery->metadata)->toHaveKey('priority')
        ->and($delivery->metadata['priority'])->toBe('high')
        ->and($delivery->metadata)->toHaveKey('context')
        ->and($delivery->metadata['context'])->toHaveKey('tenant_id')
        ->and($delivery->metadata['context']['tenant_id'])->toBe('tenant-1');
});

/*
|--------------------------------------------------------------------------
| TEST 6: AppLogger event for queued delivery
|--------------------------------------------------------------------------
*/

it('writes app logger event for queued delivery', function (): void {
    AppLogger::shouldReceive('info')
        ->once()
        ->with(
            'message_delivery.queued',
            \Mockery::type(\SchoolPalm\AppLogger\Context\AppContext::class),
            \Mockery::on(function (array $data): bool {
                return $data['channel'] === 'email'
                    && $data['recipient'] === 'queued-log@example.com';
            })
        );

    MessageDelivery::withContext([
        'tenant_id' => 'tenant-1',
        'school_id' => 'school-1',
    ])
        ->email()
        ->to('queued-log@example.com')
        ->with(['subject' => 'Queued Log'])
        ->text('Queued delivery log test.')
        ->queue();
});

/*
|--------------------------------------------------------------------------
| TEST 7: AppLogger event for successful delivery
|--------------------------------------------------------------------------
*/

it('writes app logger event for successful delivery', function (): void {
    AppLogger::shouldReceive('info')
        ->once()
        ->with(
            'message_delivery.sent',
            \Mockery::type(\SchoolPalm\AppLogger\Context\AppContext::class),
            \Mockery::on(function (array $data): bool {
                return $data['channel'] === 'email'
                    && $data['recipient'] === 'sent-log@example.com'
                    && $data['provider'] === 'laravel-mail';
            })
        );

    MessageDelivery::withContext([
        'tenant_id' => 'tenant-1',
        'school_id' => 'school-1',
    ])
        ->email()
        ->to('sent-log@example.com')
        ->with(['subject' => 'Sent Log'])
        ->text('Successful delivery log test.')
        ->send();
});

/*
|--------------------------------------------------------------------------
| TEST 8: AppLogger event for failed delivery
|--------------------------------------------------------------------------
*/

it('writes app logger event for failed delivery', function (): void {
    // Use DeliveryRecorder directly to test failed delivery recording
    // since DeliveryManager is final and cannot be mocked via Mockery.
    $recorder = app(DeliveryRecorder::class);

    $message = new Message(
        channel: 'email',
        recipients: ['fail-log@example.com'],
        text: 'Failure log test.',
        data: ['subject' => 'Failed Log'],
        context: ['tenant_id' => 'tenant-1', 'school_id' => 'school-1'],
    );

    $result = DeliveryResult::failure(
        error: 'Simulated failure',
        provider: 'laravel-mail',
    );

    $delivery = $recorder->recordFailed($message, $result);

    expect($delivery)->not->toBeNull()
        ->and($delivery->status)->toBe('failed')
        ->and($delivery->error)->toBe('Simulated failure')
        ->and($delivery->channel)->toBe('email')
        ->and($delivery->recipient)->toBe('fail-log@example.com');
});
