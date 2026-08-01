<?php

declare(strict_types=1);

use SchoolPalm\MessageDelivery\Contracts\TenantProviderSettings;
use SchoolPalm\MessageDelivery\Models\DatabaseNotification;
use SchoolPalm\MessageDelivery\Notification\Contracts\ChannelResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\EventResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\LanguageResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\NotificationEngine;
use SchoolPalm\MessageDelivery\Notification\Contracts\PreferenceResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\PriorityResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\RecipientResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\RetryResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\ScheduleResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\TemplateResolver;
use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;
use SchoolPalm\MessageDelivery\Notification\DTO\RetryPolicy;
use SchoolPalm\MessageDelivery\Notification\Support\NotificationCollection;
use SchoolPalm\MessageDelivery\Templates\Template;

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

    // Default recipient resolver returns a single in-app recipient.
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

    // Default channel resolver routes to in_app so notifications
    // can be asserted against the database.
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
});

/*
|--------------------------------------------------------------------------
| TEST 1: Engine calls resolvers
|--------------------------------------------------------------------------
*/

it('engine calls the resolvers during dispatch', function (): void {
    $flags = (object) [
        'event' => false,
        'preference' => false,
        'channel' => false,
        'language' => false,
        'template' => false,
        'priority' => false,
        'schedule' => false,
        'retry' => false,
    ];

    $this->app->bind(
        EventResolver::class,
        function () use ($flags) {
            return new class($flags) implements EventResolver
            {
                public function __construct(protected object $flags) {}

                public function resolve(NotificationEvent $event): array
                {
                    $this->flags->event = true;

                    return [];
                }
            };
        }
    );

    $this->app->bind(
        PreferenceResolver::class,
        function () use ($flags) {
            return new class($flags) implements PreferenceResolver
            {
                public function __construct(protected object $flags) {}

                public function resolve(NotificationEvent $event): array
                {
                    $this->flags->preference = true;

                    return [];
                }
            };
        }
    );

    $this->app->bind(
        ChannelResolver::class,
        function () use ($flags) {
            return new class($flags) implements ChannelResolver
            {
                public function __construct(protected object $flags) {}

                public function resolve(NotificationEvent $event, array $preferences = []): array
                {
                    $this->flags->channel = true;

                    return ['in_app'];
                }
            };
        }
    );

    $this->app->bind(
        LanguageResolver::class,
        function () use ($flags) {
            return new class($flags) implements LanguageResolver
            {
                public function __construct(protected object $flags) {}

                public function resolve(NotificationEvent $event): ?string
                {
                    $this->flags->language = true;

                    return 'en';
                }
            };
        }
    );

    $this->app->bind(
        TemplateResolver::class,
        function () use ($flags) {
            return new class($flags) implements TemplateResolver
            {
                public function __construct(protected object $flags) {}

                public function resolve(NotificationEvent $event, array $channels = [], ?string $language = null): ?Template
                {
                    $this->flags->template = true;

                    return null;
                }
            };
        }
    );

    $this->app->bind(
        PriorityResolver::class,
        function () use ($flags) {
            return new class($flags) implements PriorityResolver
            {
                public function __construct(protected object $flags) {}

                public function resolve(NotificationEvent $event): ?string
                {
                    $this->flags->priority = true;

                    return 'high';
                }
            };
        }
    );

    $this->app->bind(
        ScheduleResolver::class,
        function () use ($flags) {
            return new class($flags) implements ScheduleResolver
            {
                public function __construct(protected object $flags) {}

                public function resolve(NotificationEvent $event): \DateInterval|\DateTimeInterface|int|null
                {
                    $this->flags->schedule = true;

                    return null;
                }
            };
        }
    );

    $this->app->bind(
        RetryResolver::class,
        function () use ($flags) {
            return new class($flags) implements RetryResolver
            {
                public function __construct(protected object $flags) {}

                public function resolve(NotificationEvent $event): ?RetryPolicy
                {
                    $this->flags->retry = true;

                    return null;
                }
            };
        }
    );

    $engine = app(NotificationEngine::class);

    $engine->dispatch(
        new NotificationEvent(event: 'fee.payment_received')
    );

    expect($flags->event)->toBeTrue()
        ->and($flags->preference)->toBeTrue()
        ->and($flags->channel)->toBeTrue()
        ->and($flags->language)->toBeTrue()
        ->and($flags->template)->toBeTrue()
        ->and($flags->priority)->toBeTrue()
        ->and($flags->schedule)->toBeTrue()
        ->and($flags->retry)->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| TEST 2: Engine builds Message objects and delegates to MessageDelivery
|--------------------------------------------------------------------------
*/

it('builds messages and stores in-app notifications', function (): void {
    $result = app(NotificationEngine::class)->dispatch(
        new NotificationEvent(
            event: 'welcome.message',
            data: ['title' => 'Welcome'],
        )
    );

    expect($result)->wasDispatched()->toBeTrue();

    $notification = DatabaseNotification::first();

    expect($notification)->not->toBeNull()
        ->and($notification->title)->toBe('Welcome')
        ->and($notification->channel)->toBe('in_app')
        ->and($notification->provider)->toBe('database-notifications');
});

/*
|--------------------------------------------------------------------------
| TEST 3: Empty recipients handled gracefully
|--------------------------------------------------------------------------
*/

it('skips dispatch when no recipients are resolved', function (): void {
    $this->app->bind(
        RecipientResolver::class,
        fn(): RecipientResolver => new class implements RecipientResolver
        {
            public function resolve(NotificationEvent $event): NotificationCollection
            {
                return new NotificationCollection();
            }
        }
    );

    $result = app(NotificationEngine::class)->dispatch(
        new NotificationEvent(event: 'fee.payment_received')
    );

    expect($result)
        ->wasSkipped()->toBeTrue()
        ->status->toBe('skipped')
        ->reason->toBe('No recipients resolved.');
});

/*
|--------------------------------------------------------------------------
| TEST 4: Null template handled
|--------------------------------------------------------------------------
*/

it('handles a null template by sending raw data', function (): void {
    $this->app->bind(
        TemplateResolver::class,
        fn(): TemplateResolver => new class implements TemplateResolver
        {
            public function resolve(NotificationEvent $event, array $channels = [], ?string $language = null): ?Template
            {
                return null;
            }
        }
    );

    $result = app(NotificationEngine::class)->dispatch(
        new NotificationEvent(
            event: 'raw.message',
            data: ['title' => 'Raw Title'],
        )
    );

    expect($result)->wasDispatched()->toBeTrue();

    $notification = DatabaseNotification::first();

    expect($notification)->not->toBeNull()
        ->and($notification->title)->toBe('Raw Title');
});

/*
|--------------------------------------------------------------------------
| TEST 5: Default language used
|--------------------------------------------------------------------------
*/

it('uses the configured default language when resolver returns null', function (): void {
    config()->set('message-delivery.notification.default_language', 'en');

    $this->app->bind(
        LanguageResolver::class,
        fn(): LanguageResolver => new class implements LanguageResolver
        {
            public function resolve(NotificationEvent $event): ?string
            {
                return null;
            }
        }
    );

    $this->app->bind(
        RecipientResolver::class,
        fn(): RecipientResolver => new class implements RecipientResolver
        {
            public function resolve(NotificationEvent $event): NotificationCollection
            {
                return new NotificationCollection(['user-1']);
            }
        }
    );

    $this->app->bind(
        ChannelResolver::class,
        fn(): ChannelResolver => new class implements ChannelResolver
        {
            public function resolve(NotificationEvent $event, array $preferences = []): array
            {
                return ['in_app'];
            }
        }
    );

    $result = app(NotificationEngine::class)->dispatch(
        new NotificationEvent(event: 'default.lang')
    );

    expect($result)->wasDispatched()->toBeTrue();

    expect($result->decision->language)->toBe('en');
});

/*
|--------------------------------------------------------------------------
| TEST 6: Default priority used
|--------------------------------------------------------------------------
*/

it('uses the configured default priority when resolver returns null', function (): void {
    config()->set('message-delivery.notification.default_priority', 'normal');

    $this->app->bind(
        PriorityResolver::class,
        fn(): PriorityResolver => new class implements PriorityResolver
        {
            public function resolve(NotificationEvent $event): ?string
            {
                return null;
            }
        }
    );

    $this->app->bind(
        RecipientResolver::class,
        fn(): RecipientResolver => new class implements RecipientResolver
        {
            public function resolve(NotificationEvent $event): NotificationCollection
            {
                return new NotificationCollection(['user-1']);
            }
        }
    );

    $this->app->bind(
        ChannelResolver::class,
        fn(): ChannelResolver => new class implements ChannelResolver
        {
            public function resolve(NotificationEvent $event, array $preferences = []): array
            {
                return ['in_app'];
            }
        }
    );

    $result = app(NotificationEngine::class)->dispatch(
        new NotificationEvent(event: 'default.priority')
    );

    expect($result)->wasDispatched()->toBeTrue();

    expect($result->decision->priority)->toBe('normal');
});

/*
|--------------------------------------------------------------------------
| TEST 7: Requested channels used
|--------------------------------------------------------------------------
*/

it('uses the requested channels when provided', function (): void {
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

    $this->app->bind(
        RecipientResolver::class,
        fn(): RecipientResolver => new class implements RecipientResolver
        {
            public function resolve(NotificationEvent $event): NotificationCollection
            {
                return new NotificationCollection(['user-1']);
            }
        }
    );

    $result = app(NotificationEngine::class)->dispatch(
        new NotificationEvent(
            event: 'requested.channels',
            requestedChannels: ['in_app', 'email'],
        )
    );

    expect($result)->wasDispatched()->toBeTrue();

    expect($result->decision->channels)->toBe(['in_app', 'email']);
});

/*
|--------------------------------------------------------------------------
| TEST 8: Multiple recipients supported
|--------------------------------------------------------------------------
*/

it('delivers to multiple recipients', function (): void {
    $this->app->bind(
        RecipientResolver::class,
        fn(): RecipientResolver => new class implements RecipientResolver
        {
            public function resolve(NotificationEvent $event): NotificationCollection
            {
                return new NotificationCollection([
                    ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 1],
                    ['notifiable_type' => 'App\Models\User', 'notifiable_id' => 2],
                ]);
            }
        }
    );

    $result = app(NotificationEngine::class)->dispatch(
        new NotificationEvent(
            event: 'multi.recipients',
            data: ['title' => 'Broadcast'],
        )
    );

    expect($result)->wasDispatched()->toBeTrue();

    expect(DatabaseNotification::count())->toBe(2);
});

/*
|--------------------------------------------------------------------------
| TEST 9: Multiple channels supported
|--------------------------------------------------------------------------
*/

it('delivers through multiple channels', function (): void {
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

    $this->app->bind(
        ChannelResolver::class,
        fn(): ChannelResolver => new class implements ChannelResolver
        {
            public function resolve(NotificationEvent $event, array $preferences = []): array
            {
                return ['in_app', 'email'];
            }
        }
    );

    $result = app(NotificationEngine::class)->dispatch(
        new NotificationEvent(
            event: 'multi.channels',
            data: ['title' => 'Hello'],
        )
    );

    expect($result)->wasDispatched()->toBeTrue();

    expect($result->decision->channels)->toBe(['in_app', 'email'])
        ->and($result->delivery->get('in_app'))->not->toBeNull()
        ->and($result->delivery->get('email'))->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| TEST 10: Template resolved and rendered into message
|--------------------------------------------------------------------------
*/

it('renders a resolved template into the message body', function (): void {
    $this->app->bind(
        TemplateResolver::class,
        fn(): TemplateResolver => new class implements TemplateResolver
        {
            public function resolve(NotificationEvent $event, array $channels = [], ?string $language = null): ?Template
            {
                return new Template(
                    name: 'welcome',
                    channel: 'in_app',
                    content: 'Hello {{ name }}, welcome!',
                );
            }
        }
    );

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

    $result = app(NotificationEngine::class)->dispatch(
        new NotificationEvent(
            event: 'template.message',
            data: [
                'title' => 'Welcome',
                'name' => 'John',
            ],
        )
    );

    expect($result)->wasDispatched()->toBeTrue();

    $notification = DatabaseNotification::first();

    expect($notification)->not->toBeNull()
        ->and($notification->body)->toBe('Hello John, welcome!');
});
