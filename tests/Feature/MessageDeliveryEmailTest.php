<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use SchoolPalm\MessageDelivery\Contracts\TenantProviderSettings;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\MessageDelivery;
use SchoolPalm\MessageDelivery\Providers\Email\Laravel\LaravelMailMessage;

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
| TEST 1: SEND EMAIL THROUGH PUBLIC API
|--------------------------------------------------------------------------
*/

it('sends email through public api', function (): void {
    $result = MessageDelivery::withContext([])
        ->email()
        ->to('student@example.com')
        ->text('Hello student')
        ->send();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->status->toBe('sent')
        ->provider->toBe('laravel-mail');

    Mail::assertSent(LaravelMailMessage::class, function (LaravelMailMessage $mail): bool {
        return $mail->hasTo('student@example.com');
    });
});

/*
|--------------------------------------------------------------------------
| TEST 2: EMAIL WITH SUBJECT
|--------------------------------------------------------------------------
*/

it('sends email with subject via data array', function (): void {
    MessageDelivery::withContext([])
        ->email()
        ->to('parent@school.com')
        ->with(['subject' => 'Fee Reminder'])
        ->text('Your school fees are due.')
        ->send();

    Mail::assertSent(LaravelMailMessage::class, function (LaravelMailMessage $mail): bool {
        return $mail->hasTo('parent@school.com') && $mail->subject === 'Fee Reminder';
    });
});

/*
|--------------------------------------------------------------------------
| TEST 3: EMAIL WITH HTML CONTENT
|--------------------------------------------------------------------------
*/

it('sends email with html content via text method', function (): void {
    MessageDelivery::withContext([])
        ->email()
        ->to('user@example.com')
        ->with(['subject' => 'Welcome'])
        ->text('<h1>Welcome</h1><p>Thank you for joining.</p>')
        ->send();

    Mail::assertSent(LaravelMailMessage::class, function (LaravelMailMessage $mail): bool {
        return $mail->hasTo('user@example.com') && $mail->subject === 'Welcome';
    });
});

/*
|--------------------------------------------------------------------------
| TEST 4: EMAIL USING VIEW TEMPLATE
|--------------------------------------------------------------------------
*/

it('sends email using view template', function (): void {
    $this->app['view']->addNamespace('test', dirname(__DIR__) . '/resources/views');

    $viewPath = dirname(__DIR__) . '/resources/views/emails';
    if (! is_dir($viewPath)) {
        mkdir($viewPath, 0755, true);
    }

    file_put_contents($viewPath . '/greeting.blade.php', '<h1>Hello {{ $name }}</h1><p>{{ $message }}</p>');

    MessageDelivery::withContext([])
        ->email()
        ->to('teacher@school.com')
        ->with(['subject' => 'Staff Meeting'])
        ->view('test::emails.greeting')
        ->with(['name' => 'John', 'message' => 'Staff meeting at 3pm.'])
        ->send();

    Mail::assertSent(LaravelMailMessage::class, function (LaravelMailMessage $mail): bool {
        return $mail->hasTo('teacher@school.com') && $mail->subject === 'Staff Meeting';
    });

    unlink($viewPath . '/greeting.blade.php');
    rmdir($viewPath);
});

/*
|--------------------------------------------------------------------------
| TEST 5: MULTIPLE RECIPIENTS THROUGH API
|--------------------------------------------------------------------------
*/

it('sends email to multiple recipients through public api', function (): void {
    MessageDelivery::withContext([])
        ->email()
        ->to(['one@example.com', 'two@example.com'])
        ->with(['subject' => 'Multiple'])
        ->text('Hello everyone.')
        ->send();

    Mail::assertSent(LaravelMailMessage::class, function (LaravelMailMessage $mail): bool {
        return $mail->hasTo('one@example.com') && $mail->hasTo('two@example.com');
    });
});

/*
|--------------------------------------------------------------------------
| TEST 6: EMAIL QUEUE INTEGRATION
|--------------------------------------------------------------------------
*/

it('queues email and returns queued status', function (): void {
    $result = MessageDelivery::withContext([])
        ->email()
        ->to('student@example.com')
        ->with(['subject' => 'Queued'])
        ->text('This email is queued.')
        ->queue();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->status->toBe('queued')
        ->isQueued()->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| TEST 7: QUEUE OPTIONS
|--------------------------------------------------------------------------
*/

it('passes queue options through the public api', function (): void {
    $result = MessageDelivery::withContext([])
        ->email()
        ->to('student@example.com')
        ->with(['subject' => 'Queue Options'])
        ->text('Email with queue options.')
        ->delay(60)
        ->onQueue('emails')
        ->tries(5)
        ->queue();

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->status->toBe('queued')
        ->isQueued()->toBeTrue();
});
