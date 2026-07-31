<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\WhatsApp\Meta;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;

/**
 * SMS provider that sends messages through Meta WhatsApp Cloud API.
 *
 * This provider implements the MessageProvider contract and communicates
 * with the Meta WhatsApp Graph API using Laravel's HTTP client.
 *
 * Flow:
 * 1. Provider receives Message with recipients and text content.
 * 2. Provider validates configuration (access_token, phone_number_id).
 * 3. Provider sends POST request to Meta WhatsApp API endpoint.
 * 4. Provider returns DeliveryResult based on API response.
 *
 * Who uses it:
 * - Created by MetaWhatsAppFactory
 * - Called by WhatsAppChannel::send()
 * - Used indirectly by MessageDelivery via DeliveryManager
 *
 * What it should NOT do:
 * - NOT resolve tenant configuration
 * - NOT access database
 * - NOT modify the Message object
 * - NOT handle multiple providers (single adapter)
 *
 * @see https://developers.facebook.com/docs/whatsapp/cloud-api
 */
final class MetaWhatsAppProvider implements MessageProvider
{
    /**
     * The default API version for Meta WhatsApp.
     */
    protected const DEFAULT_VERSION = 'v23.0';

    /**
     * Create a new MetaWhatsAppProvider instance.
     *
     * @param  array<string, mixed>  $configuration
     *
     * Expected configuration:
     *
     * [
     *     'access_token' => 'EAAx...',
     *     'phone_number_id' => '123456789',
     *     'version' => 'v23.0',
     *     'verify_ssl' => true,
     * ]
     */
    public function __construct(
        protected readonly array $configuration
    ) {}


    /**
     * Get the provider identifier.
     */
    public function name(): string
    {
        return 'meta-whatsapp';
    }


    /**
     * Get the channel supported by this provider.
     */
    public function channel(): string
    {
        return 'whatsapp';
    }


    /**
     * Send a WhatsApp message through Meta WhatsApp Cloud API.
     *
     * Builds the request payload with recipient, message content,
     * then sends via the Meta WhatsApp Graph API.
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
            | - Bearer token authentication
            | - JSON content type
            | - SSL verification (configurable)
            |
            */

            $version = $this->configuration['version']
                ?? self::DEFAULT_VERSION;

            $phoneNumberId = $this->configuration['phone_number_id'];

            $url = "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";

            $verifySsl = $this->configuration['verify_ssl']
                ?? true;

            $http = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->configuration['access_token'],
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->withOptions([
                'verify' => $verifySsl,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Build Payload
            |--------------------------------------------------------------------------
            |
            | Meta WhatsApp Cloud API payload structure:
            |
            | - messaging_product: whatsapp
            | - recipient_type: individual
            | - to: recipient phone number
            | - type: text
            | - text: { body: message text }
            |
            */

            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $message->recipients[0] ?? '',
                'type' => 'text',
                'text' => [
                    'body' => $message->text ?? '',
                ],
            ];


            /*
            |--------------------------------------------------------------------------
            | Send Request
            |--------------------------------------------------------------------------
            |
            | POST to: https://graph.facebook.com/{version}/{phone_number_id}/messages
            |
            */

            $response = $http->post(
                $url,
                $payload
            );


            /*
            |--------------------------------------------------------------------------
            | Handle Response
            |--------------------------------------------------------------------------
            */

            if (! $response->successful()) {

                throw new RuntimeException(
                    'Meta WhatsApp request failed with status '
                        . $response->status() . ': ' . $response->body()
                );
            }


            $data = $response->json();

            $messageId = $data['messages'][0]['id'] ?? null;


            return DeliveryResult::success(
                provider: $this->name(),

                providerMessageId: $messageId,

                metadata: [
                    'response' => $data,
                ]
            );

        } catch (RequestException | RuntimeException | \Throwable $exception) {

            return DeliveryResult::failure(
                error: $exception->getMessage(),

                provider: $this->name(),

                metadata: [
                    'provider' => $this->name(),
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
     * - access_token
     * - phone_number_id
     */
    public function configured(): bool
    {
        $accessToken = $this->configuration['access_token']
            ?? null;

        $phoneNumberId = $this->configuration['phone_number_id']
            ?? null;

        return $accessToken !== null
            && $accessToken !== ''
            && $phoneNumberId !== null
            && $phoneNumberId !== '';
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

            'label' => 'Meta WhatsApp Cloud API',

            'channel' => $this->channel(),

            'capabilities' => [
                'text',
                'template',
                'media',
                'reply',
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
                'Missing Meta WhatsApp configuration. '
                    . 'The "access_token" and "phone_number_id" settings are required.'
            );
        }
    }
}
