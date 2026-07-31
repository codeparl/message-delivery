<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\Push\Firebase;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;

/**
 * Push notification provider that sends messages through Firebase Cloud Messaging.
 *
 * This provider implements the MessageProvider contract and communicates
 * with the FCM HTTP v1 API using Laravel's HTTP client.
 *
 * Flow:
 * 1. Provider receives Message with recipients (device tokens) and text content.
 * 2. Provider validates configuration (credentials_json, project_id).
 * 3. Provider generates an OAuth2 access token from the service account JSON.
 * 4. Provider sends POST request to FCM HTTP v1 API endpoint.
 * 5. Provider returns DeliveryResult based on API response.
 *
 * Who uses it:
 * - Created by FirebasePushFactory
 * - Called by PushChannel::send()
 * - Used indirectly by MessageDelivery via DeliveryManager
 *
 * What it should NOT do:
 * - NOT resolve tenant configuration
 * - NOT access database
 * - NOT modify the Message object
 * - NOT handle multiple providers (single adapter)
 *
 * @see https://firebase.google.com/docs/cloud-messaging/send-message
 */
final class FirebasePushProvider implements MessageProvider
{
    /**
     * The FCM HTTP v1 endpoint template.
     */
    protected const FCM_SEND_URL = 'https://fcm.googleapis.com/v1/projects/%s/messages:send';

    /**
     * The OAuth2 token endpoint.
     */
    protected const OAUTH_TOKEN_URL = 'https://oauth2.googleapis.com/token';

    /**
     * Create a new FirebasePushProvider instance.
     *
     * @param  array<string, mixed>  $configuration
     *
     * Expected configuration:
     *
     * [
     *     'credentials_json' => '{ "type": "service_account", ... }',
     *     'project_id' => 'my-project-id',
     *     'server_key' => 'optional-legacy-key',
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
        return 'firebase-push';
    }


    /**
     * Get the channel supported by this provider.
     */
    public function channel(): string
    {
        return 'push';
    }


    /**
     * Send a push notification through Firebase Cloud Messaging.
     *
     * Builds the FCM HTTP v1 request payload with device tokens,
     * notification title, body, and optional data payload.
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
            | Obtain OAuth2 Access Token
            |--------------------------------------------------------------------------
            |
            | Generate a Bearer token from the service account credentials JSON
            | using the OAuth2 JWT bearer grant flow.
            |
            | An optional 'access_token' configuration value can be supplied to
            | bypass token generation (useful in testing or when a long-lived
            | token is already available).
            |
            */

            $accessToken = $this->configuration['access_token']
                ?? $this->getAccessToken();


            /*
            |--------------------------------------------------------------------------
            | Build Request Configuration
            |--------------------------------------------------------------------------
            |
            | Configure HTTP client with:
            | - Bearer token authentication
            | - JSON content type
            |
            */

            $http = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json; UTF-8',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Send to each recipient
            |--------------------------------------------------------------------------
            |
            | FCM accepts a single device token per request.
            | For multiple tokens, we send one request per token.
            |
            */

            $projectId = $this->configuration['project_id'];

            $url = sprintf(self::FCM_SEND_URL, $projectId);

            $successCount = 0;
            $failureCount = 0;
            $providerMessageIds = [];
            $errors = [];


            /*
            |--------------------------------------------------------------------------
            | Build Notification Payload
            |--------------------------------------------------------------------------
            |
            | Extract title from $message->data['title'].
            | Extract custom data from $message->data['data'].
            | Fall back to provider name for title if not provided.
            |
            */

            $title = $message->data['title']
                ?? $message->context['title']
                ?? $this->metadata()['label'];

            $dataPayload = $message->data['data']
                ?? [];

            foreach ($message->recipients as $token) {

                $deviceToken = is_string($token)
                    ? $token
                    : ($token['token'] ?? $token['device'] ?? '');


                if ($deviceToken === '') {
                    $failureCount++;
                    $errors[] = 'Empty device token provided.';
                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Build FCM v1 Payload
                |--------------------------------------------------------------------------
                |
                | {
                |     "message": {
                |         "token": "DEVICE_TOKEN",
                |         "notification": {
                |             "title": "...",
                |             "body": "..."
                |         },
                |         "data": {
                |             "key": "value"
                |         }
                |     }
                | }
                |
                */

                $payload = [
                    'message' => [
                        'token' => $deviceToken,
                        'notification' => [
                            'title' => $title,
                            'body' => $message->text ?? '',
                        ],
                    ],
                ];

                if (! empty($dataPayload)) {
                    // FCM data payload values must be strings
                    $payload['message']['data'] = array_map(
                        fn(mixed $value): string => (string) $value,
                        $dataPayload
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Send Request
                |--------------------------------------------------------------------------
                |
                | POST to: https://fcm.googleapis.com/v1/projects/{project}/messages:send
                |
                */

                $response = $http->post(
                    $url,
                    $payload
                );


                if ($response->successful()) {

                    $successCount++;

                    $data = $response->json();

                    $providerMessageIds[] = $data['name']
                        ?? null;
                } else {

                    $failureCount++;

                    $errors[] = sprintf(
                        'FCM request failed for %s: HTTP %d - %s',
                        $deviceToken,
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
        } catch (ConnectionException $exception) {

            return DeliveryResult::failure(
                error: 'FCM connection failed: ' . $exception->getMessage(),

                provider: $this->name(),

                metadata: [
                    'recipient_count' => count($message->recipients),
                ]
            );
        } catch (RequestException $exception) {

            return DeliveryResult::failure(
                error: 'FCM request failed: ' . $exception->getMessage(),

                provider: $this->name(),

                metadata: [
                    'recipient_count' => count($message->recipients),
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
     * - credentials_json
     * - project_id
     */
    public function configured(): bool
    {
        $credentials = $this->configuration['credentials_json']
            ?? null;

        $projectId = $this->configuration['project_id']
            ?? null;

        return $credentials !== null
            && $credentials !== ''
            && $projectId !== null
            && $projectId !== '';
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

            'label' => 'Firebase Cloud Messaging',

            'channel' => $this->channel(),

            'capabilities' => [
                'notification',
                'data',
                'topics',
                'images',
                'actions',
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
                'Missing Firebase configuration. '
                    . 'The "credentials_json" and "project_id" settings are required.'
            );
        }
    }


    /**
     * Obtain an OAuth2 access token from the service account credentials.
     *
     * Uses the JWT bearer grant flow to exchange a signed assertion
     * for a short-lived access token.
     *
     * @return string
     *
     * @throws RuntimeException When token generation fails
     */
    protected function getAccessToken(): string
    {
        $credentialsJson = $this->configuration['credentials_json'];

        $credentials = is_string($credentialsJson)
            ? json_decode($credentialsJson, true)
            : $credentialsJson;

        if (! is_array($credentials) || empty($credentials['client_email'])) {

            throw new RuntimeException(
                'Invalid Firebase credentials JSON: missing client_email.'
            );
        }

        $now = time();

        $jwtHeader = $this->base64UrlEncode(
            json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_UNESCAPED_SLASHES)
        );

        $jwtClaimSet = $this->base64UrlEncode(
            json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => self::OAUTH_TOKEN_URL,
                'exp' => $now + 3600,
                'iat' => $now,
            ], JSON_UNESCAPED_SLASHES)
        );

        $signature = '';

        $signingInput = $jwtHeader . '.' . $jwtClaimSet;

        $privateKey = $credentials['private_key'] ?? '';

        openssl_sign(
            $signingInput,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256
        );

        $jwt = $signingInput . '.' . $this->base64UrlEncode($signature);

        /*
        |--------------------------------------------------------------------------
        | Exchange JWT for Access Token
        |--------------------------------------------------------------------------
        */

        $response = Http::asForm()->post(
            self::OAUTH_TOKEN_URL,
            [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]
        );

        if (! $response->successful()) {

            throw new RuntimeException(
                'Failed to obtain Firebase access token: '
                    . $response->body()
            );
        }

        $data = $response->json();

        return $data['access_token'] ?? '';
    }


    /**
     * Base64 URL-safe encode without padding.
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(
            strtr(
                base64_encode($data),
                '+/',
                '-_'
            ),
            '='
        );
    }
}
