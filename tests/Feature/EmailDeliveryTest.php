<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use SchoolPalm\MessageDelivery\Channels\EmailChannel;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Providers\Email\Laravel\LaravelMailFactory;
use SchoolPalm\MessageDelivery\Providers\Email\Laravel\LaravelMailMessage;
use SchoolPalm\MessageDelivery\Providers\Email\Laravel\LaravelMailProvider;

/*
|--------------------------------------------------------------------------
| Provider Resolution
|--------------------------------------------------------------------------
*/

it('can resolve provider from factory', function (): void {
    $factory = new LaravelMailFactory();
    $configuration = ['mailer' => 'array'];
    $provider = $factory->create($configuration);

    expect($provider)->toBeInstanceOf(LaravelMailProvider::class);
    expect($provider->name())->toBe('laravel-mail');
    expect($provider->channel())->toBe('email');
});

/*
|--------------------------------------------------------------------------
| Channel Delegation
|--------------------------------------------------------------------------
*/

it('email channel delegates to provider', function (): void {
    $channel = new EmailChannel();
    $provider = new LaravelMailProvider(['mailer' => 'array']);
    $message = new Message(
        channel: 'email',
        recipients: ['test@example.com'],
        data: ['subject' => 'Test Subject'],
        text: '<h1>Test</h1><p>Email channel delegation test.</p>',
    );

    $result = $channel->send(message: $message, provider: $provider);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('laravel-mail');
});

/*
|--------------------------------------------------------------------------
| Successful Email Delivery
|--------------------------------------------------------------------------
*/

it('sends email and returns success delivery result', function (): void {
    $provider = new LaravelMailProvider(['mailer' => 'array']);
    $message = new Message(
        channel: 'email',
        recipients: ['user@example.com'],
        data: ['subject' => 'Welcome'],
        text: '<h1>Welcome</h1><p>Thank you for joining.</p>',
    );

    $result = $provider->send($message);

    expect($result)->toBeInstanceOf(DeliveryResult::class)
        ->status->toBe('sent')
        ->isSuccessful()->toBeTrue()
        ->and($result->provider)->toBe('laravel-mail');
});

/*
|--------------------------------------------------------------------------
| Mail Fake Assertions
|--------------------------------------------------------------------------
*/

it('laravel mail receives the mailable with correct data', function (): void {
    Mail::fake();
    $provider = new LaravelMailProvider(['mailer' => 'array']);
    $message = new Message(
        channel: 'email',
        recipients: ['student@school.com'],
        data: ['subject' => 'Fee Reminder', 'plain_text' => 'Your school fees are due.'],
        text: '<h1>Fee Reminder</h1><p>Your school fees are due.</p>',
    );

    $provider->send($message);

    Mail::assertSent(LaravelMailMessage::class, function (LaravelMailMessage $mail): bool {
        return $mail->hasTo('student@school.com') && $mail->subject === 'Fee Reminder';
    });
});

/*
|--------------------------------------------------------------------------
| Configuration Validation
|--------------------------------------------------------------------------
*/

it('returns not configured when mailer is missing', function (): void {
    $provider = new LaravelMailProvider([]);
    expect($provider->configured())->toBeFalse();
});

it('returns not configured when mailer key is missing', function (): void {
    $provider = new LaravelMailProvider(['api_key' => 'xxxx']);
    expect($provider->configured())->toBeFalse();
});

it('returns not configured when mailer is empty string', function (): void {
    $provider = new LaravelMailProvider(['mailer' => '']);
    expect($provider->configured())->toBeFalse();
});

it('returns configured when valid mailer is provided', function (): void {
    $provider = new LaravelMailProvider(['mailer' => 'ses']);
    expect($provider->configured())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| TEST 1: HTML EMAIL
|--------------------------------------------------------------------------
*/

it('sends email with html content', function (): void {
    Mail::fake();
    $provider = new LaravelMailProvider(['mailer' => 'array']);
    $message = new Message(
        channel: 'email',
        recipients: ['user@example.com'],
        data: ['subject' => 'HTML Email Test'],
        text: '<h1>Welcome</h1><p>This is an HTML email.</p>',
    );

    $provider->send($message);

    Mail::assertSent(LaravelMailMessage::class, function (LaravelMailMessage $mail): bool {
        return $mail->hasTo('user@example.com') && $mail->subject === 'HTML Email Test';
    });
});

/*
|--------------------------------------------------------------------------
| TEST 2: MULTIPLE RECIPIENTS
|--------------------------------------------------------------------------
*/

it('sends email to multiple recipients', function (): void {
    Mail::fake();
    $provider = new LaravelMailProvider(['mailer' => 'array']);
    $recipients = ['user1@example.com', 'user2@example.com'];
    $message = new Message(
        channel: 'email',
        recipients: $recipients,
        data: ['subject' => 'Multiple Recipients'],
        text: 'Hello everyone!',
    );

    $provider->send($message);

    Mail::assertSent(LaravelMailMessage::class, function (LaravelMailMessage $mail) use ($recipients): bool {
        foreach ($recipients as $recipient) {
            if (! $mail->hasTo($recipient)) {
                return false;
            }
        }

        return true;
    });
});

/*
|--------------------------------------------------------------------------
| TEST 3: CC SUPPORT
|--------------------------------------------------------------------------
*/

it('supports cc recipients', function (): void {
    Mail::fake();
    $provider = new LaravelMailProvider(['mailer' => 'array']);
    $message = new Message(
        channel: 'email',
        recipients: ['to@example.com'],
        data: ['subject' => 'CC Test', 'cc' => ['manager@example.com']],
        text: 'This email has a CC recipient.',
    );

    $provider->send($message);

    Mail::assertSent(LaravelMailMessage::class, function (LaravelMailMessage $mail): bool {
        return $mail->hasTo('to@example.com') && $mail->hasCc('manager@example.com');
    });
});

/*
|--------------------------------------------------------------------------
| TEST 4: BCC SUPPORT
|--------------------------------------------------------------------------
*/

it('supports bcc recipients', function (): void {
    Mail::fake();
    $provider = new LaravelMailProvider(['mailer' => 'array']);
    $message = new Message(
        channel: 'email',
        recipients: ['to@example.com'],
        data: ['subject' => 'BCC Test', 'bcc' => ['admin@example.com']],
        text: 'This email has a BCC recipient.',
    );

    $provider->send($message);

    Mail::assertSent(LaravelMailMessage::class, function (LaravelMailMessage $mail): bool {
        return $mail->hasTo('to@example.com') && $mail->hasBcc('admin@example.com');
    });
});

/*
|--------------------------------------------------------------------------
| TEST 5: REPLY TO
|--------------------------------------------------------------------------
*/

it('supports reply to address', function (): void {
    Mail::fake();
    $provider = new LaravelMailProvider(['mailer' => 'array']);
    $message = new Message(
        channel: 'email',
        recipients: ['to@example.com'],
        data: ['subject' => 'Reply-To Test', 'reply_to' => 'support@example.com'],
        text: 'Please reply to support.',
    );

    $provider->send($message);

    Mail::assertSent(LaravelMailMessage::class, function (LaravelMailMessage $mail): bool {
        return $mail->hasReplyTo('support@example.com');
    });
});

/*
|--------------------------------------------------------------------------
| TEST 6: ATTACHMENTS
|--------------------------------------------------------------------------
*/

it('supports file attachments', function (): void {
    $tempPath = tempnam(sys_get_temp_dir(), 'test_');
    file_put_contents($tempPath, 'Test file content for attachment.');

    $message = new Message(
        channel: 'email',
        recipients: ['to@example.com'],
        data: [
            'subject' => 'Attachment Test',
            'attachments' => [
                [
                    'file' => $tempPath,
                    'options' => [
                        'as' => 'report.pdf',
                        'mime' => 'application/pdf',
                    ],
                ],
            ],
        ],
        text: 'This email has an attachment.',
    );

    $mailable = new LaravelMailMessage($message);
    $mailable->build();

    $reflection = new ReflectionClass($mailable);
    $prop = $reflection->getProperty('attachments');
    $prop->setAccessible(true);
    $attachments = $prop->getValue($mailable);

    expect($attachments)->not->toBeEmpty();

    $found = false;
    foreach ($attachments as $a) {
        $name = $a['options']['as'] ?? null;
        if ($name === 'report.pdf') {
            $found = true;
            break;
        }
    }

    expect($found)->toBeTrue();

    Mail::fake();
    $provider = new LaravelMailProvider(['mailer' => 'array']);
    $provider->send($message);

    unlink($tempPath);

    Mail::assertSent(LaravelMailMessage::class, function (LaravelMailMessage $mail): bool {
        return $mail->hasTo('to@example.com') && $mail->subject === 'Attachment Test';
    });
});

/*
|--------------------------------------------------------------------------
| TEST 7: CUSTOM HEADERS
|--------------------------------------------------------------------------
*/

it('supports custom headers', function (): void {
    Mail::fake();
    $provider = new LaravelMailProvider(['mailer' => 'array']);
    $message = new Message(
        channel: 'email',
        recipients: ['to@example.com'],
        data: [
            'subject' => 'Headers Test',
            'headers' => [
                'X-School-ID' => 'SCHOOL-123',
                'X-Message-ID' => 'MSG-456',
            ],
        ],
        text: 'This email has custom headers.',
    );

    $provider->send($message);

    Mail::assertSent(LaravelMailMessage::class, function (LaravelMailMessage $mail): bool {
        return $mail->hasTo('to@example.com');
    });
});

/*
|--------------------------------------------------------------------------
| TEST 8: PRIORITY
|--------------------------------------------------------------------------
*/

it('sets priority header from message priority', function (): void {
    Mail::fake();
    $provider = new LaravelMailProvider(['mailer' => 'array']);
    $message = new Message(
        channel: 'email',
        recipients: ['to@example.com'],
        data: ['subject' => 'Priority Test'],
        text: 'High priority email.',
        priority: 'high',
    );

    $provider->send($message);

    Mail::assertSent(LaravelMailMessage::class, function (LaravelMailMessage $mail): bool {
        return $mail->hasTo('to@example.com') && $mail->subject === 'Priority Test';
    });
});

it('does not set priority when null', function (): void {
    $message = new Message(
        channel: 'email',
        recipients: ['to@example.com'],
        data: ['subject' => 'No Priority'],
        text: 'Normal priority email.',
        priority: null,
    );

    $mailable = new LaravelMailMessage($message);
    $mailable->build();

    expect($mailable->hasTo('to@example.com'))->toBeTrue();
    expect($mailable->subject)->toBe('No Priority');
});
