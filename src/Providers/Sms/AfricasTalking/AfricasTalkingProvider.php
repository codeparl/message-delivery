<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\Sms\AfricasTalking;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;

/**
 * SMS provider that sends messages through Africa's Talking API.
 *
 * This provider implements the MessageProvider contract and communicates
 * with the Africa's Talking SMS API using Laravel's HTTP client.
 *
 * Flow:
 * 1. Provider receives Message with recipients and text content.
 * 2. Provider validates configuration (api_key, username, sender_id).
 * 3. Provider sends POST request to Africa's Talking SMS endpoint.
 * 4. Provider returns DeliveryResult based on API response.
 *
 * Who uses it:
 * - Created by AfricasTalkingFactory
 * - Called by SmsChannel::send()
 * - Used indirectly by MessageDelivery via DeliveryManager
 *
 * What it should NOT do:
 * - NOT resolve tenant configuration
 * - NOT access database
 * - NOT modify the Message object
 * - NOT handle multiple providers (single adapter)
 *
 * @see https://developers.africastalking.com/docs/sms/overview
 */
final class AfricasTalkingProvider implements MessageProvider
{
    /**
     * The default API endpoint for Africa's Talking SMS.
     *
     * @var string
     */
    protected const DEFAULT_API_URL = 'https://api.africastalking.com/version1/messaging';

    /**
     * Create a new AfricasTalkingProvider instance.
     *
     * @param  array<string, mixed>  $configuration
     *
     * Expected configuration:
     *
     * [
     *     'api_key' => 'xxxxxxxx',
     *     'username' => 'my-username',
     *     'sender_id' => 'SCHOOL',
     *     'api_url' => 'https://api.africastalking.com/version1/messaging',
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
        return 'africas-talking';
    }


    /**
     * Get the channel supported by this provider.
     */
    public function channel(): string
    {
        return 'sms';
    }


    /**
     * Send an SMS message through Africa's Talking API.
     *
     * Builds the request payload with recipients, sender ID,
     * and message content, then sends via the Africa's Talking
     * SMS messaging endpoint.
     *
     * Supports:
     * - Single recipient
     * - Multiple recipients (comma-separated in one API call)
     * - Bulk sending
     * - Unicode
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
            | - API Key authentication via apiKey header
            | - Timeout from configuration or default of 30 seconds
            | - Retry mechanism (3 attempts with 100ms delay)
            |
            */

            $timeout = $this->configuration['timeout']
                ?? 30;

            $apiUrl = $this->configuration['api_url']
                ?? self::DEFAULT_API_URL;

            $http = Http::timeout($timeout)
                ->retry(3, 100)
                ->withHeaders([
                    'apiKey' => $this->configuration['api_key'],
                    'Accept' => 'application/json',
                ]);


            /*
            |--------------------------------------------------------------------------
            | Format Recipients
            |--------------------------------------------------------------------------
            |
            | Africa's Talking accepts recipients as a comma-separated
            | string of phone numbers in international format.
            |
            */

            $recipients = [];

            foreach ($message->recipients as $recipient) {

                $phone = is_string($recipient)
                    ? $recipient
                    : ($recipient['phone'] ?? $recipient['number'] ?? '');

                if ($phone !== '') {
                    $recipients[] = $phone;
                }
            }


            if (empty($recipients)) {

                return DeliveryResult::failure(
                    error: 'No valid recipients provided for Africa\'s Talking SMS.',

                    provider: $this->name(),
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Build Payload
            |--------------------------------------------------------------------------
            |
            | Africa's Talking SMS API parameters:
            |
            | - username: Your Africa's Talking username
            | - to: Comma-separated recipient phone numbers
            | - message: The SMS text content
            | - from: (Optional) Sender ID/alphanumeric
            | - bulkSMSMode: Set to 1 for bulk messaging
            |
            */

            $payload = [
                'username' => $this->configuration['username'],
                'to' => implode(',', $recipients),
                'message' => $message->text ?? '',
            ];


            /*
            |--------------------------------------------------------------------------
            | Sender ID
            |--------------------------------------------------------------------------
            |
            | If a sender_id is configured, include it in the request.
            | Note: Sender ID must be approved by Africa's Talking.
            |
            */

            if (! empty($this->configuration['sender_id'])) {

                $payload['from'] = $this->configuration['sender_id'];
            }


            /*
            |--------------------------------------------------------------------------
            | Bulk SMS Mode
            |--------------------------------------------------------------------------
            |
            | Enable bulkSMSMode when sending to multiple recipients.
            | This ensures each recipient receives the message individually.
            |
            */

            if (count($recipients) > 1) {

                $payload['bulkSMSMode'] = 1;
            }


            /*
            |--------------------------------------------------------------------------
            | Send Request
            |--------------------------------------------------------------------------
            |
            | POST to: https://api.africastalking.com/version1/messaging
            |
            */

            $response = $http->post(
                $apiUrl,
                $payload
            );


            /*
            |--------------------------------------------------------------------------
            | Handle Response
            |--------------------------------------------------------------------------
            */

            if (! $response->successful()) {

                return DeliveryResult::failure(
                    error: 'Africa\'s Talking request failed with status '
                        . $response->status() . ': ' . $response->body(),

                    provider: $this->name(),

                    metadata: [
                        'recipient_count' => count($recipients),
                        'http_status' => $response->status(),
                    ]
                );
            }


            $data = $response->json();

            $sendingData = $data['SMSMessageData'] ?? [];

            $recipients_data = $sendingData['Recipients'] ?? [];


            /*
            |--------------------------------------------------------------------------
            | Check for delivery failures
            |--------------------------------------------------------------------------
            */

            $failedRecipients = [];

            foreach ($recipients_data as $recipientData) {

                $status = $recipientData['status']
                    ?? 'Unknown';

                if ($status !== 'Success') {

                    $failedRecipients[] = $recipientData;
                }
            }


            if (! empty($failedRecipients)) {

                $errorMessages = array_map(
                    fn(array $r): string => sprintf(
                        '%s: %s',
                        $r['number'] ?? 'unknown',
                        $r['status'] ?? 'Unknown'
                    ),
                    $failedRecipients
                );

                return DeliveryResult::failure(
                    error: 'Africa\'s Talking reported delivery failures: '
                        . implode('; ', $errorMessages),

                    provider: $this->name(),

                    metadata: [
                        'recipient_count' => count($recipients),
                        'failed_recipients' => $failedRecipients,
                        'response' => $data,
                    ]
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Success
            |--------------------------------------------------------------------------
            */

            return DeliveryResult::success(
                provider: $this->name(),

                providerMessageId: $sendingData['messageId']
                    ?? $recipients_data[0]['messageId']
                    ?? null,

                metadata: [
                    'recipient_count' => count($recipients),

                    'cost' => $sendingData['cost']
                        ?? null,

                    'response' => $data,
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
     * - api_key
     * - username
     *
     * Optional but recommended:
     * - sender_id
     *
     * This allows ProviderManager and administration tools
     * to verify that the provider is ready to send.
     */
    public function configured(): bool
    {
        $apiKey = $this->configuration['api_key']
            ?? null;

        $username = $this->configuration['username']
            ?? null;

        return $apiKey !== null
            && $apiKey !== ''
            && $username !== null
            && $username !== '';
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

            'label' => 'Africa\'s Talking',

            'channel' => $this->channel(),

            'capabilities' => [
                'plain-text',
                'bulk',
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
                'Missing Africa\'s Talking configuration. '
                    . 'The "api_key" and "username" settings are required.'
            );
        }
    }
}

