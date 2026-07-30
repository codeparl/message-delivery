<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\Sms\EgoSms;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;

final class EgoSmsProvider implements MessageProvider
{
    /**
     * Create EgoSMS provider.
     *
     * Configuration comes from TenantProviderSettings.
     *
     * Expected:
     *
     * [
     *     'api_url' => '',
     *     'username' => '',
     *     'password' => '',
     *     'sender_id' => ''
     * ]
     */
    public function __construct(
        protected readonly array $configuration
    ) {}


    /**
     * Send SMS message.
     */
    public function send(
        Message $message
    ): DeliveryResult {

        $this->validateConfiguration();


        try {

            $response = Http::post(
                $this->configuration['api_url'],
                [
                    'username' => $this->configuration['username'],

                    'password' => $this->configuration['password'],

                    'senderid' => $this->configuration['sender_id'],

                    'message' => $message->text,

                    'recipients' => $message->recipients,
                ]
            );


            if (! $response->successful()) {

                throw new RuntimeException(
                    'EgoSMS request failed with status '
                        . $response->status()
                );
            }


            $data = $response->json();


            return DeliveryResult::success(
                provider: $this->name(),

                providerMessageId: $data['message_id']
                    ?? null,

                metadata: [
                    'response' => $data,
                ]
            );
        } catch (\Throwable $exception) {


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
     * Get provider identifier.
     */
    public function name(): string
    {
        return 'egosms';
    }


    /**
     * Get supported channel.
     */
    public function channel(): string
    {
        return 'sms';
    }


    /**
     * Check whether provider is configured.
     *
     * This allows ProviderManager or admin tools
     * to verify tenant configuration.
     */
    public function configured(): bool
    {
        $required = [

            'api_url',

            'username',

            'password',

            'sender_id',

        ];


        foreach ($required as $field) {

            if (empty($this->configuration[$field])) {
                return false;
            }
        }


        return true;
    }


    /**
     * Get provider metadata.
     *
     * Used for:
     *
     * - provider discovery
     * - dashboard display
     * - logging
     */
    public function metadata(): array
    {
        return [

            'name' => $this->name(),

            'label' => 'EgoSMS',

            'channel' => $this->channel(),

            'capabilities' => [

                'unicode',

                'delivery_reports',

            ],

        ];
    }


    /**
     * Validate provider configuration.
     */
    protected function validateConfiguration(): void
    {
        if (! $this->configured()) {

            throw new RuntimeException(
                'Missing EgoSMS configuration.'
            );
        }
    }
}
