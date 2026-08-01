<?php

declare(strict_types=1);

use SchoolPalm\MessageDelivery\Contracts\TenantProviderSettings;
use SchoolPalm\MessageDelivery\Notification\Contracts\RecipientResolver;
use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;
use SchoolPalm\MessageDelivery\Notification\Support\NotificationCollection;
use SchoolPalm\MessageDelivery\Notification\Support\NotificationResult;
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
                    default => null,
                };
            }

            public function configurationFor(string $channel, string $provider): array
            {
                return match ($provider) {
                    'database-notifications' => [],
                    'laravel-mail' => ['mailer' => 'array'],
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
    $this->app->bind(
        RecipientResolver::class,
        fn(): RecipientResolver => new class implements RecipientResolver
        {
            public function resolve(NotificationEvent $event): NotificationCollection
            {
                return new NotificationCollection([
                    ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 1],
                ]);
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
