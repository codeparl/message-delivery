<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\Email\Laravel;

use Illuminate\Support\Facades\Mail;
use RuntimeException;
use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;

/**
 * Email provider that sends messages through Laravel Mail.
 *
 * This provider implements the MessageProvider contract and
 * acts as an adapter over Laravel's mail system. It uses
 * Mail::mailer() to send email through any configured Laravel
 * mailer transport (SES, SMTP, Mailgun, Postmark, Log, etc.).
 *
 * The provider receives a configuration array with a 'mailer'
 * key that specifies which Laravel mailer to use.
 *
 * Flow:
 * 1. Provider receives Message with recipients, content, etc.
 * 2. Provider creates a LaravelMailMessage (Mailable).
 * 3. Provider calls Mail::mailer($mailer)->send($mailable).
 *
 * Who uses it:
 * - Created by LaravelMailFactory
 * - Called by EmailChannel::send()
 * - Used indirectly by MessageDelivery via DeliveryManager
 *
 * What it should NOT do:
 * - NOT resolve tenant configuration
 * - NOT access database
 * - NOT modify the Message object
 * - NOT handle multiple mailer transports (single adapter)
 */
final class LaravelMailProvider implements MessageProvider
{
    /**
     * Create a new LaravelMailProvider instance.
     *
     * @param  array<string, mixed>  $configuration
     *
     * Expected configuration:
     *
     * [
     *     'mailer' => 'ses',  // The Laravel mailer to use
     * ]
     */
    public function __construct(
        protected readonly array $configuration
    ) {}


    /**
     * Get the provider identifier.
     *
     * Used by ProviderRegistry and ProviderManager.
     */
    public function name(): string
    {
        return 'laravel-mail';
    }


    /**
     * Get the channel supported by this provider.
     */
    public function channel(): string
    {
        return 'email';
    }


    /**
     * Send an email message through Laravel Mail.
     *
     * Creates a LaravelMailMessage mailable from the given
     * Message object and sends it using the configured
     * Laravel mailer.
     *
     * All recipient configuration (to, cc, bcc, reply_to)
     * is handled by the LaravelMailMessage::build() method.
     *
     * @param  Message  $message  The message to send
     * @return DeliveryResult
     */
    public function send(
        Message $message
    ): DeliveryResult {

        try {

            $this->validateConfiguration();


            /*
            |--------------------------------------------------------------------------
            | Create and Build Mailable
            |--------------------------------------------------------------------------
            |
            | The LaravelMailMessage handles all content mapping
            | in its build() method: recipients, subject, HTML,
            | text, CC, BCC, reply-to, and attachments.
            |
            | build() is called explicitly so the mailable is
            | fully configured before being passed to the mailer.
            | This is required for Mail::fake() compatibility since
            | the fake does not call build() automatically.
            |
            */

            $mailable = new LaravelMailMessage(
                message: $message
            );

            $mailable->build();


            /*
            |--------------------------------------------------------------------------
            | Resolve Mailer
            |--------------------------------------------------------------------------
            |
            | Use the configured Laravel mailer.
            | Example: 'ses', 'mailgun', 'postmark', 'smtp'
            |
            */

            $mailer = $this->configuration['mailer'];


            /*
            |--------------------------------------------------------------------------
            | Send via Laravel Mail
            |--------------------------------------------------------------------------
            |
            | Delegates to Laravel's mail system which handles
            | the actual transport (SES, SMTP, etc.).
            |
            */

            Mail::mailer($mailer)->send(
                $mailable
            );


            return DeliveryResult::success(
                provider: $this->name(),

                metadata: [
                    'mailer' => $mailer,
                ]
            );
        } catch (\Throwable $exception) {

            return DeliveryResult::failure(
                error: $exception->getMessage(),

                provider: $this->name(),

                metadata: [
                    'mailer' => $this->configuration['mailer'] ?? 'unknown',
                ]
            );
        }
    }


    /**
     * Check whether the provider has valid configuration.
     *
     * Returns true only when the 'mailer' configuration
     * key is present and non-empty.
     *
     * This allows ProviderManager and administration tools
     * to verify that the provider is ready to send.
     */
    public function configured(): bool
    {
        $mailer = $this->configuration['mailer']
            ?? null;

        return $mailer !== null
            && $mailer !== ''
            && is_string($mailer);
    }


    /**
     * Get provider metadata.
     *
     * Used for:
     * - Provider discovery
     * - Dashboard display
     * - Logging
     * - Diagnostics
     *
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return [
            'name' => $this->name(),

            'label' => 'Laravel Mail',

            'channel' => $this->channel(),

            'capabilities' => [
                'html',
                'text',
                'blade',
                'attachments',
                'cc',
                'bcc',
                'reply_to',
                'priority',
                'headers',
                'queue',
            ],
        ];
    }


    /**
     * Validate provider configuration before sending.
     *
     * @throws RuntimeException When configuration is invalid
     */
    protected function validateConfiguration(): void
    {
        if (! $this->configured()) {

            throw new RuntimeException(
                'Missing Laravel Mail configuration. '
                    . 'The "mailer" setting is required.'
            );
        }
    }
}
