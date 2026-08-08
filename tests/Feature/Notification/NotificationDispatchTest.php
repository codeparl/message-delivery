<?php

declare(strict_types=1);

use SchoolPalm\MessageDelivery\Models\DatabaseNotification;
use SchoolPalm\MessageDelivery\Contracts\TenantProviderSettings;
use SchoolPalm\MessageDelivery\Notification\Contracts\ChannelResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\RecipientResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\TemplateResolver;
use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;
use SchoolPalm\MessageDelivery\Notification\Support\NotificationCollection;
use SchoolPalm\MessageDelivery\Notification\Support\NotificationResult;
use SchoolPalm\MessageDelivery\Templates\Template;
use SchoolPalm\MessageDelivery\Facades\Notification;

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
                    'in_app' => 'database-notifications',
                    'email' => 'laravel-mail',
                    'sms' => 'egosms',
                    'whatsapp' => 'twilio-whatsapp',
                    'push' => 'firebase',
                    default => null,
                };
            }

            public function configurationFor(string $channel, string $provider): array
            {
                return match ($provider) {
                    'database-notifications' => [],
                    'laravel-mail' => ['mailer' => 'mailpit'],
                    default => [],
                };
            }

            public function enabled(string $channel, string $provider): bool
            {
                return true;
            }
        }
    );

    // Resolve a recipient so dispatch proceeds through delivery.
    // Recipients carry notifiable references AND email addresses so
    // both in-app and email providers can genuinely process the
    // same mock recipient.
    $this->app->bind(
        RecipientResolver::class,
        fn(): RecipientResolver => new class implements RecipientResolver
        {
            public function resolve(NotificationEvent $event): NotificationCollection
            {
                return new NotificationCollection([
                    [
                        'notifiable_type' => 'App\Models\User',
                        'notifiable_id' => 1,
                        'email' => 'notification-dispatch@example.com',
                        'name' => 'Dispatch Tester',
                    ],
                ]);
            }
        }
    );

    // Route channels from the requested hints, otherwise fall back
    // to a realistic default channel.
    $this->app->bind(
        ChannelResolver::class,
        fn(): ChannelResolver => new class implements ChannelResolver
        {
            public function resolve(NotificationEvent $event, array $preferences = []): array
            {
                return $event->requestedChannels ?: ['in_app'];
            }
        }
    );

    // Resolve a template so the engine renders real message body/subject
    // content for both email and in-app providers.
    $this->app->bind(
        TemplateResolver::class,
        fn(): TemplateResolver => new class implements TemplateResolver
        {
            public function resolve(NotificationEvent $event, array $channels = [], ?string $language = null): ?Template
            {
                return new Template(
                    name: $event->requestedTemplate ?? $event->event,
                    channel: $channels[0] ?? 'in_app',
                    content: 'Hello {{ name }}, you have a new notification: {{ title }}.',
                    subject: $event->data['subject'] ?? $event->data['title'] ?? 'Notification',
                );
            }
        }
    );
});

/*
|--------------------------------------------------------------------------
| TEST 1: Notification::dispatch reaches the engine
|--------------------------------------------------------------------------
*/

it('dispatches a notification event and returns a result', function (): void {
    $result = Notification::dispatch(
        event: 'fee.payment_received',
        data: ['amount' => 5000],
    );

    expect($result)
        ->toBeInstanceOf(NotificationResult::class)
        ->wasDispatched()->toBeTrue()
        ->status->toBe('dispatched');

    expect($result->event)
        ->toBeInstanceOf(NotificationEvent::class)
        ->event->toBe('fee.payment_received');

    expect($result->delivery)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| TEST 2: Fluent API dispatch
|--------------------------------------------------------------------------
*/

it('supports fluent event dispatch', function (): void {
    $result = Notification::event('student.admitted')
        ->data(['name' => 'John Doe'])
        ->context(['tenant_id' => 'tenant-1'])
        ->priority('high')
        ->dispatch();

    expect($result)
        ->toBeInstanceOf(NotificationResult::class)
        ->wasDispatched()->toBeTrue();

    expect($result->event->event)->toBe('student.admitted')
        ->and($result->event->data)->toHaveKey('name')
        ->and($result->event->context)->toHaveKey('tenant_id')
        ->and($result->event->requestedPriority)->toBe('high');
});

/*
|--------------------------------------------------------------------------
| TEST 3: Fluent hints are carried into the event
|--------------------------------------------------------------------------
*/

it('carries fluent hints into the notification event', function (): void {
    $result = Notification::event('exam.results_published')
        ->channels(['in_app', 'email'])
        ->language('fr')
        ->template('exam-results')
        ->metadata(['batch_id' => 42])
        ->dispatch();

    expect($result)
        ->toBeInstanceOf(NotificationResult::class);

    expect($result->event->requestedChannels)->toBe(['in_app', 'email'])
        ->and($result->event->requestedLanguage)->toBe('fr')
        ->and($result->event->requestedTemplate)->toBe('exam-results')
        ->and($result->event->metadata)->toHaveKey('batch_id');
});

/*
|--------------------------------------------------------------------------
| TEST 4: Real email delivery through Mailpit (SMTP)
|--------------------------------------------------------------------------
*/

it('delivers a real email through the laravel-mail provider via mailpit', function (): void {
    // Guard: Mailpit must be running on 127.0.0.1:1025.
    $sock = @fsockopen('127.0.0.1', 1025, $errno, $errstr, 2);

    if ($sock === false) {
        throw new RuntimeException(
            'Mailpit SMTP server is not available on 127.0.0.1:1025. '
                . 'Start Mailpit before running integration tests:'
                . PHP_EOL . '  mailpit'
        );
    }

    fclose($sock);

    $result = Notification::event('fee.payment_received')
        ->channels(['email'])
        ->data([
            'subject' => 'Fee Payment Received',
            'amount' => 5000,
        ])
        ->dispatch();

    expect($result)
        ->toBeInstanceOf(NotificationResult::class)
        ->wasDispatched()->toBeTrue();

    // The correct channel must be selected.
    expect($result->decision->channels)->toBe(['email']);

    // The email channel must have successfully delivered (sync, not queued).
    $emailResult = $result->delivery->get('email');

    expect($emailResult)->not->toBeNull()
        ->and($emailResult->status)->toBe('sent')
        ->and($emailResult->provider)->toBe('laravel-mail')
        ->and($emailResult->isSuccessful())->toBeTrue()
        ->and($emailResult->metadata['mailer'])->toBe('mailpit');
});

/*
|--------------------------------------------------------------------------
| TEST 5: Real in-app delivery through database-notifications
|--------------------------------------------------------------------------
*/

it('delivers a real in-app notification through the database-notifications provider', function (): void {
    $result = Notification::event('student.admitted')
        ->channels(['in_app'])
        ->data(['title' => 'Admission Confirmed'])
        ->dispatch();

    expect($result)
        ->toBeInstanceOf(NotificationResult::class)
        ->wasDispatched()->toBeTrue();

    // The correct channel must be selected.
    expect($result->decision->channels)->toBe(['in_app']);

    // The in-app channel must have successfully delivered (sync, not queued).
    $inAppResult = $result->delivery->get('in_app');

    expect($inAppResult)->not->toBeNull()
        ->and($inAppResult->status)->toBe('sent')
        ->and($inAppResult->provider)->toBe('database-notifications')
        ->and($inAppResult->isSuccessful())->toBeTrue();

    // Verify the provider actually persisted a notification row.
    $notification = DatabaseNotification::first();

    expect($notification)->not->toBeNull()
        ->and($notification->title)->toBe('Admission Confirmed')
        ->and($notification->channel)->toBe('in_app')
        ->and($notification->provider)->toBe('database-notifications');
});

/*
|--------------------------------------------------------------------------
| TEST 6: Correct channels are selected and delivered per channel
|--------------------------------------------------------------------------
*/

it('selects the requested channels and delivers through each real provider', function (): void {
    $result = Notification::event('exam.results_published')
        ->channels(['in_app', 'email'])
        ->data([
            'title' => 'Results Published',
            'subject' => 'Your results are out',
        ])
        ->dispatch();

    expect($result)
        ->toBeInstanceOf(NotificationResult::class)
        ->wasDispatched()->toBeTrue();

    // Both requested channels must be selected.
    expect($result->decision->channels)->toBe(['in_app', 'email']);

    // Each channel must have a successful, sync (non-queued) delivery result.
    $inAppResult = $result->delivery->get('in_app');
    $emailResult = $result->delivery->get('email');

    expect($inAppResult)->not->toBeNull()
        ->and($inAppResult->status)->toBe('sent')
        ->and($inAppResult->provider)->toBe('database-notifications');

    expect($emailResult)->not->toBeNull()
        ->and($emailResult->status)->toBe('sent')
        ->and($emailResult->provider)->toBe('laravel-mail')
        ->and($emailResult->metadata['mailer'])->toBe('mailpit');

    // One in-app notification row should have been persisted.
    expect(DatabaseNotification::count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| TEST 7: Sync dispatch runs jobs immediately (results observable)
|--------------------------------------------------------------------------
*/

it('runs notification dispatch synchronously so results are immediately observable', function (): void {
    $result = Notification::event('sync.test')
        ->channels(['in_app'])
        ->data(['title' => 'Sync'])
        ->dispatch();

    expect($result)->wasDispatched()->toBeTrue();

    // A sync dispatch must NOT be queued — the delivery result is
    // immediately available in the current process.
    $inAppResult = $result->delivery->get('in_app');

    expect($inAppResult)->not->toBeNull()
        ->and($inAppResult->isQueued())->toBeFalse()
        ->and($inAppResult->status)->toBe('sent');

    // The in-app provider effect is immediately observable.
    expect(DatabaseNotification::where('title', 'Sync')->exists())->toBeTrue();
});
