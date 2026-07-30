<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use SchoolPalm\MessageDelivery\Contracts\TenantProviderSettings;
use SchoolPalm\MessageDelivery\MessageDelivery;
use SchoolPalm\MessageDelivery\Messages\MultiChannelResult;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;

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
| TEST 1: Sends message through multiple channels
|--------------------------------------------------------------------------
*/

it('sends message through multiple channels', function (): void {
    $result = MessageDelivery::multi()
        ->channels(['email', 'sms'])
        ->to('student@example.com')
        ->text('Your child has been admitted')
        ->send();

    expect($result)
        ->toBeInstanceOf(MultiChannelResult::class);

    $all = $result->all();
    expect($all)->toHaveKeys(['email', 'sms']);

    // Email has a provider configured and should succeed
    expect($result->get('email'))
        ->isSuccessful()->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| TEST 2: Returns individual channel results
|--------------------------------------------------------------------------
*/

it('returns individual channel results', function (): void {
    $result = MessageDelivery::multi()
        ->channels(['email', 'sms'])
        ->to('parent@example.com')
        ->text('Fee reminder')
        ->send();

    $emailResult = $result->get('email');
    $smsResult = $result->get('sms');

    expect($emailResult)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue();

    expect($smsResult)
        ->toBeInstanceOf(DeliveryResult::class);
});

/*
|--------------------------------------------------------------------------
| TEST 3: One channel failure does not stop others
|--------------------------------------------------------------------------
*/

it('one channel failure does not stop others', function (): void {
    $result = MessageDelivery::multi()
        ->channels(['email', 'whatsapp'])
        ->to('student@example.com')
        ->text('Emergency announcement')
        ->send();

    // Email should succeed
    $emailResult = $result->get('email');
    expect($emailResult)->isSuccessful()->toBeTrue();

    // WhatsApp has no provider configured
    $whatsappResult = $result->get('whatsapp');
    expect($whatsappResult)->not->toBeNull();

    // The overall result should indicate failures
    expect($result->hasFailures())->toBeTrue()
        ->and($result->isSuccessful())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| TEST 4: Queues multiple channel messages
|--------------------------------------------------------------------------
*/

it('queues multiple channel messages', function (): void {
    $result = MessageDelivery::multi()
        ->channels(['email', 'sms'])
        ->to('student@example.com')
        ->text('Queued notification')
        ->queue();

    expect($result)
        ->toBeInstanceOf(MultiChannelResult::class);

    $all = $result->all();
    expect($all)->toHaveKeys(['email', 'sms']);

    // Each channel result should be present
    foreach ($all as $channelResult) {
        expect($channelResult)
            ->toBeInstanceOf(DeliveryResult::class);
    }
});

/*
|--------------------------------------------------------------------------
| TEST 5: Shared data is passed to all channels
|--------------------------------------------------------------------------
*/

it('shared data is passed to all channels', function (): void {
    $result = MessageDelivery::multi()
        ->channels(['email', 'sms'])
        ->to('parent@school.com')
        ->with(['subject' => 'Shared Subject', 'key' => 'value'])
        ->text('Shared content')
        ->priority('high')
        ->send();

    expect($result)
        ->toBeInstanceOf(MultiChannelResult::class);

    // Email should succeed with the shared data
    expect($result->get('email'))
        ->isSuccessful()->toBeTrue();

    // Both channels should have results
    $all = $result->all();
    expect($all)->toHaveKeys(['email', 'sms']);

    foreach ($all as $channelResult) {
        expect($channelResult)->toBeInstanceOf(DeliveryResult::class);
    }
});
