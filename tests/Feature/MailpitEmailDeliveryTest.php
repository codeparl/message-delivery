<?php

declare(strict_types=1);

/**
 * Mailpit SMTP Integration Tests
 *
 * These integration tests verify that the LaravelMailProvider
 * can send real emails through a local Mailpit SMTP server.
 *
 * Requirements:
 * - Mailpit must be running on 127.0.0.1:1025
 *   (Install: https://github.com/axllent/mailpit)
 *
 * What this test validates:
 * - The real SMTP pipeline:
 *   MessageDelivery → Laravel Mail → SMTP → Mailpit
 *
 * What it does NOT use:
 * - Mail::fake() — this test uses the real SMTP connection
 *
 * If Mailpit is not running, the test will fail with a
 * helpful message explaining how to start it.
 */

use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Providers\Email\Laravel\LaravelMailProvider;

/*
|--------------------------------------------------------------------------
| Mailpit Availability Check
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    // Check if Mailpit SMTP is reachable before running tests
    $sock = @fsockopen(
        '127.0.0.1',
        1025,
        $errno,
        $errstr,
        2
    );

    if ($sock === false) {
        throw new RuntimeException(
            'Mailpit SMTP server is not available on 127.0.0.1:1025. '
            . 'Start Mailpit before running integration tests:'
            . PHP_EOL . '  mailpit'
            . PHP_EOL . '  # or'
            . PHP_EOL . '  docker run --rm -p 1025:1025 -p 8025:8025 axllent/mailpit'
        );
    }

    fclose($sock);
});

/*
|--------------------------------------------------------------------------
| TEST 1: Send email through Mailpit SMTP server
|--------------------------------------------------------------------------
*/

it('sends email successfully through mailpit SMTP server', function (): void {
    $provider = new LaravelMailProvider([
        'mailer' => 'mailpit',
    ]);

    $message = new Message(
        channel: 'email',
        recipients: ['mailpit-test@example.com'],
        data: [
            'subject' => 'Mailpit Integration Test',
        ],
        text: '<h1>Mailpit Test</h1><p>This email was sent through the real SMTP pipeline to verify Mailpit integration.</p>',
    );

    $result = $provider->send($message);

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->and($result->status)->toBe('sent')
        ->and($result->provider)->toBe('laravel-mail')
        ->and($result->isSuccessful())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| TEST 2: Send HTML email viewable in Mailpit
|--------------------------------------------------------------------------
*/

it('sends html email that can be viewed in mailpit', function (): void {
    $provider = new LaravelMailProvider([
        'mailer' => 'mailpit',
    ]);

    $htmlContent = '<!DOCTYPE html>'
        . '<html><head><title>HTML Test</title></head>'
        . '<body>'
        . '<h1>HTML Email Test</h1>'
        . '<p>This is an <strong>HTML</strong> email sent to Mailpit.</p>'
        . '<ul><li>Item 1</li><li>Item 2</li></ul>'
        . '</body></html>';

    $message = new Message(
        channel: 'email',
        recipients: ['html-test@example.com'],
        data: [
            'subject' => 'HTML Mailpit Test',
        ],
        text: $htmlContent,
    );

    $result = $provider->send($message);

    expect($result)
        ->toBeInstanceOf(DeliveryResult::class)
        ->isSuccessful()->toBeTrue()
        ->and($result->status)->toBe('sent')
        ->and($result->provider)->toBe('laravel-mail');

    // Verify the message contains expected content via metadata
    expect($result->metadata)
        ->toHaveKey('mailer')
        ->and($result->metadata['mailer'])->toBe('mailpit');
});
