<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\Sms\Twilio;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;

/**
 * SMS provider that sends messages through Twilio Programmable Messaging API.
 *
 * This provider implements the MessageProvider contract and communicates
 * with the Twilio API using Laravel's HTTP client.
 *
 * Flow:
 * 1. Provider receives Message with recipients and text content.
 * 2. Provider validates configuration (sid, token, from).
 * 3. Provider sends POST request to Twilio's Message resource endpoint.
 * 4. Provider returns DeliveryResult based on API response.
 *
 * Who uses it:
 * - Created by TwilioFactory
 * - Called by SmsChannel::send()
 * - Used indirectly by MessageDelivery via DeliveryManager
 *
 * What it should NOT do:
 * - NOT resolve tenant configuration
 * - NOT access database
 * - NOT modify the Message object
 * - NOT handle multiple providers (single adapter)
 *
 * @see https://www.twilio.com/docs/sms
 */
final class TwilioProvider implements MessageProvider
{
    /**
     * Create a new TwilioProvider instance.
     *
     * @param  array<string, mixed>  $configuration
     *
     * Expected configuration:
     *
     * [
     *     'sid' => 'ACxxxxxxxxxx',
     *     'token' => 'xxxxxxxxxxxx',
     *     'from' => '+1234567890',
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
        return 'twilio-sms';
    }


    /**
     * Get the channel supported by this provider.
     */
    public function channel(): string
    {
        return 'sms';
    }


    /**
     * Send an SMS message through Twilio API.
     *
     * Builds the request payload with recipients, sender ID,
     * and message content, then sends via Twilio's Messages
     * resource endpoint.
     *
     * Supports:
     * - Single recipient
     * - Multiple recipients (one API call per recipient)
     * - Unicode (via auto-detection of non-GSM characters)
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
            | Build Request Configuration
            |--------------------------------------------------------------------------
            |
            | Configure HTTP client with:
            | - Basic auth using Account SID and Auth Token
            | - Timeout from configuration or default of 30 seconds
            | - Retry mechanism (3 attempts with 100ms delay)
            |
            */

            $timeout = $this->configuration['timeout']
                ?? 30;

            $http = Http::timeout($timeout)
                ->retry(3, 100)
                ->withBasicAuth(
                    $this->configuration['sid'],
                    $this->configuration['token']
                );


            /*
            |--------------------------------------------------------------------------
            | Send to each recipient
            |--------------------------------------------------------------------------
            |
            | Twilio's API accepts a single recipient per request.
            | For multiple recipients, we send one request per recipient
            | and track the results.
            |
            */

            $successCount = 0;
            $failureCount = 0;
            $providerMessageIds = [];
            $errors = [];

            foreach ($message->recipients as $recipient) {

                $phone = is_string($recipient)
                    ? $recipient
                    : ($recipient['phone'] ?? $recipient['number'] ?? '');


                /*
                |--------------------------------------------------------------------------
                | Determine Message Encoding
                |--------------------------------------------------------------------------
                |
                | If the message contains non-GSM characters,
                | set the messaging service to use Unicode.
                |
                */

                $payload = [
                    'To' => $phone,
                    'From' => $this->configuration['from'],
                    'Body' => $message->text ?? '',
                ];

                if ($this->isUnicode($message->text ?? '')) {

                    $payload['StatusCallback'] = 'sent';
                }


                /*
                |--------------------------------------------------------------------------
                | Send Request
                |--------------------------------------------------------------------------
                |
                | POST to: https://api.twilio.com/2010-04-01/Accounts/{Sid}/Messages.json
                |
                */

                $accountSid = $this->configuration['sid'];

                $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";

                $response = $http->asForm()->post(
                    $url,
                    $payload
                );


                if ($response->successful()) {

                    $successCount++;

                    $data = $response->json();

                    $providerMessageIds[] = $data['sid']
                        ?? null;

                } else {

                    $failureCount++;

                    $errors[] = sprintf(
                        'Twilio request failed for %s: HTTP %d - %s',
                        $phone,
                        $response->status(),
                        $response->body()
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Build Delivery Result
            |--------------------------------------------------------------------------
            */

            if ($failureCount === 0) {

                return DeliveryResult::success(
                    provider: $this->name(),

                    providerMessageId: $providerMessageIds[0]
                        ?? null,

                    metadata: [
                        'recipient_count' => count($message->recipients),

                        'success_count' => $successCount,

                        'provider_message_ids' => $providerMessageIds,
                    ]
                );
            }


            if ($successCount === 0) {

                return DeliveryResult::failure(
                    error: implode('; ', $errors),

                    provider: $this->name(),

                    metadata: [
                        'recipient_count' => count($message->recipients),
                    ]
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Partial Success
            |--------------------------------------------------------------------------
            |
            | Some messages succeeded, some failed.
            | Return as sent with partial failure metadata.
            |
            */

            return DeliveryResult::success(
                provider: $this->name(),

                metadata: [
                    'recipient_count' => count($message->recipients),

                    'success_count' => $successCount,

                    'failure_count' => $failureCount,

                    'errors' => $errors,

                    'provider_message_ids' => $providerMessageIds,
                ]
            );

        } catch (\Throwable $exception) {

            return DeliveryResult::failure(
                error: $exception->getMessage(),

                provider: $this->name(),

                metadata: [
                    'recipient_count' => count($message->recipients),
                ]
            );
        }
    }


    /**
     * Check whether the provider has valid configuration.
     *
     * Returns true only when all required configuration
     * keys are present and non-empty.
     *
     * Required fields:
     * - sid (Account SID)
     * - token (Auth Token)
     * - from (Sender phone number)
     *
     * This allows ProviderManager and administration tools
     * to verify that the provider is ready to send.
     */
    public function configured(): bool
    {
        $sid = $this->configuration['sid']
            ?? null;

        $token = $this->configuration['token']
            ?? null;

        $from = $this->configuration['from']
            ?? null;

        return $sid !== null
            && $sid !== ''
            && $token !== null
            && $token !== ''
            && $from !== null
            && $from !== '';
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

            'label' => 'Twilio SMS',

            'channel' => $this->channel(),

            'capabilities' => [
                'plain-text',
                'unicode',
                'delivery_status',
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
                'Missing Twilio SMS configuration. '
                    . 'The "sid", "token", and "from" settings are required.'
            );
        }
    }


    /**
     * Check whether the message text contains non-GSM characters
     * that require Unicode/UTF-8 encoding.
     *
     * @param  string  $text
     * @return bool
     */
    protected function isUnicode(string $text): bool
    {
        // GSM 03.38 character set basic Latin characters
        // If the string contains characters outside this set, it's Unicode
        return preg_match('/[^\x20-\x7E\x0A\x0D]/u', $text) === 1;
    }
}

