<?php

declare(strict_types=1);

use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;
use SchoolPalm\MessageDelivery\Notification\Resolvers\NullChannelResolver;
use SchoolPalm\MessageDelivery\Notification\Resolvers\NullEventResolver;
use SchoolPalm\MessageDelivery\Notification\Resolvers\NullLanguageResolver;
use SchoolPalm\MessageDelivery\Notification\Resolvers\NullPreferenceResolver;
use SchoolPalm\MessageDelivery\Notification\Resolvers\NullPriorityResolver;
use SchoolPalm\MessageDelivery\Notification\Resolvers\NullRecipientResolver;
use SchoolPalm\MessageDelivery\Notification\Resolvers\NullRetryResolver;
use SchoolPalm\MessageDelivery\Notification\Resolvers\NullScheduleResolver;
use SchoolPalm\MessageDelivery\Notification\Resolvers\NullTemplateResolver;

/*
|--------------------------------------------------------------------------
| TEST 1: Null event resolver returns empty metadata
|--------------------------------------------------------------------------
*/

it('null event resolver returns empty metadata', function (): void {
    $resolver = new NullEventResolver();

    $result = $resolver->resolve(
        new NotificationEvent(event: 'test.event')
    );

    expect($result)->toBe([]);
});

/*
|--------------------------------------------------------------------------
| TEST 2: Null recipient resolver returns empty collection
|--------------------------------------------------------------------------
*/

it('null recipient resolver returns an empty collection', function (): void {
    $resolver = new NullRecipientResolver();

    $result = $resolver->resolve(
        new NotificationEvent(event: 'test.event')
    );

    expect($result)->isEmpty()->toBeTrue()
        ->and($result->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| TEST 3: Null preference resolver returns empty preferences
|--------------------------------------------------------------------------
*/

it('null preference resolver returns empty preferences', function (): void {
    $resolver = new NullPreferenceResolver();

    $result = $resolver->resolve(
        new NotificationEvent(event: 'test.event')
    );

    expect($result)->toBe([]);
});

/*
|--------------------------------------------------------------------------
| TEST 4: Null channel resolver uses requested channels
|--------------------------------------------------------------------------
*/

it('null channel resolver uses requested channels', function (): void {
    $resolver = new NullChannelResolver(
        defaultChannel: 'email'
    );

    $result = $resolver->resolve(
        new NotificationEvent(
            event: 'test.event',
            requestedChannels: ['sms', 'email'],
        )
    );

    expect($result)->toBe(['sms', 'email']);
});

/*
|--------------------------------------------------------------------------
| TEST 5: Null channel resolver falls back to default channel
|--------------------------------------------------------------------------
*/

it('null channel resolver falls back to the default channel', function (): void {
    $resolver = new NullChannelResolver(
        defaultChannel: 'email'
    );

    $result = $resolver->resolve(
        new NotificationEvent(event: 'test.event')
    );

    expect($result)->toBe(['email']);
});

/*
|--------------------------------------------------------------------------
| TEST 6: Null language resolver uses requested language
|--------------------------------------------------------------------------
*/

it('null language resolver uses the requested language', function (): void {
    $resolver = new NullLanguageResolver();

    $result = $resolver->resolve(
        new NotificationEvent(
            event: 'test.event',
            requestedLanguage: 'fr',
        )
    );

    expect($result)->toBe('fr');
});

/*
|--------------------------------------------------------------------------
| TEST 7: Null template resolver returns null
|--------------------------------------------------------------------------
*/

it('null template resolver returns null', function (): void {
    $resolver = new NullTemplateResolver();

    $result = $resolver->resolve(
        new NotificationEvent(event: 'test.event')
    );

    expect($result)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| TEST 8: Null priority resolver uses requested priority
|--------------------------------------------------------------------------
*/

it('null priority resolver uses the requested priority', function (): void {
    $resolver = new NullPriorityResolver();

    $result = $resolver->resolve(
        new NotificationEvent(
            event: 'test.event',
            requestedPriority: 'high',
        )
    );

    expect($result)->toBe('high');
});

/*
|--------------------------------------------------------------------------
| TEST 9: Null schedule resolver returns null
|--------------------------------------------------------------------------
*/

it('null schedule resolver returns null', function (): void {
    $resolver = new NullScheduleResolver();

    $result = $resolver->resolve(
        new NotificationEvent(event: 'test.event')
    );

    expect($result)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| TEST 10: Null retry resolver returns null
|--------------------------------------------------------------------------
*/

it('null retry resolver returns null', function (): void {
    $resolver = new NullRetryResolver();

    $result = $resolver->resolve(
        new NotificationEvent(event: 'test.event')
    );

    expect($result)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| TEST 11: Resolver replacement works through the container
|--------------------------------------------------------------------------
*/

it('replaces a default resolver through the container', function (): void {
    $this->app->bind(
        \SchoolPalm\MessageDelivery\Notification\Contracts\RecipientResolver::class,
        fn() => new class implements \SchoolPalm\MessageDelivery\Notification\Contracts\RecipientResolver
        {
            public function resolve(NotificationEvent $event): \SchoolPalm\MessageDelivery\Notification\Support\NotificationCollection
            {
                return new \SchoolPalm\MessageDelivery\Notification\Support\NotificationCollection(['custom-user']);
            }
        }
    );

    $resolver = app(\SchoolPalm\MessageDelivery\Notification\Contracts\RecipientResolver::class);

    expect($resolver)->not->toBeInstanceOf(NullRecipientResolver::class);

    $result = $resolver->resolve(
        new NotificationEvent(event: 'test.event')
    );

    expect($result->all())->toBe(['custom-user']);
});
