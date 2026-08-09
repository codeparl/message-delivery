<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Support;

use Illuminate\Support\Str;

/**
 * Immutable collection of notification recipients.
 *
 * Wraps an iterable recipient list and provides count/isEmpty
 * helpers used by the engine to short-circuit delivery.
 */
final class NotificationCollection
{
    /**
     * Recipients.
     *
     * @var array<int, mixed>
     */
    protected readonly array $items;

    /**
     * Create a notification collection.
     *
     * @param  iterable<int, mixed> $items
     */
    public function __construct(
        iterable $items = []
    ) {
        $this->items = is_array($items)
            ? array_values($items)
            : array_values(iterator_to_array($items));
    }


    /**
     * Get all recipients.
     *
     * @return array<int, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }


    /**
     * Get the first recipient.
     */
    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }


    /**
     * Get the number of recipients.
     */
    public function count(): int
    {
        return count($this->items);
    }


    /**
     * Check whether the collection is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }


    /**
     * Check whether the collection is not empty.
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }


    /**
     * Maps the raw recipients to specific endpoints keyed by channel.
     * 
     * Extracts endpoints using Laravel's routeNotificationFor{Channel} convention,
     * or falls back to common property names and string validation.
     *
     * @param  array<int, string> $channels The requested channels (e.g., ['email', 'sms', 'whatsapp'])
     * @return array<string, array<int, mixed>>
     */
    public function mapToChannels(array $channels): array
    {
        $keyedRecipients = [];

        // Initialize empty arrays for requested channels
        foreach ($channels as $channel) {
            $keyedRecipients[$channel] = [];
        }

        foreach ($this->items as $recipient) {
            foreach ($channels as $channel) {

                if (is_object($recipient)) {
                    $method = 'routeNotificationFor' . Str::studly($channel);

                    if (method_exists($recipient, $method)) {
                        $endpoint = $recipient->{$method}();
                        if (! empty($endpoint)) {
                            $keyedRecipients[$channel][] = $endpoint;
                        }
                    }
                    // Fallbacks for common model properties based on channel type
                    else {
                        switch ($channel) {
                            case 'email':
                                if (isset($recipient->email)) {
                                    $keyedRecipients['email'][] = $recipient->email;
                                }
                                break;
                            case 'sms':
                                if (isset($recipient->phone_number)) {
                                    $keyedRecipients['sms'][] = $recipient->phone_number;
                                } elseif (isset($recipient->phone)) {
                                    $keyedRecipients['sms'][] = $recipient->phone;
                                }
                                break;
                            case 'whatsapp':
                                if (isset($recipient->whatsapp_number)) {
                                    $keyedRecipients['whatsapp'][] = $recipient->whatsapp_number;
                                } elseif (isset($recipient->whatsapp)) {
                                    $keyedRecipients['whatsapp'][] = $recipient->whatsapp;
                                } elseif (isset($recipient->phone_number)) {
                                    $keyedRecipients['whatsapp'][] = $recipient->phone_number;
                                }
                                break;
                            case 'push':
                                if (isset($recipient->fcm_token)) {
                                    $keyedRecipients['push'][] = $recipient->fcm_token;
                                } elseif (isset($recipient->device_token)) {
                                    $keyedRecipients['push'][] = $recipient->device_token;
                                }
                                break;
                            case 'in_app':
                                // Default to the model instance itself for database/in-app notifications
                                $keyedRecipients['in_app'][] = $recipient;
                                break;
                            case 'slack':
                                if (isset($recipient->slack_webhook_url)) {
                                    $keyedRecipients['slack'][] = $recipient->slack_webhook_url;
                                } elseif (isset($recipient->slack_channel)) {
                                    $keyedRecipients['slack'][] = $recipient->slack_channel;
                                }
                                break;
                            case 'discord':
                                if (isset($recipient->discord_webhook_url)) {
                                    $keyedRecipients['discord'][] = $recipient->discord_webhook_url;
                                }
                                break;
                            case 'telegram':
                                if (isset($recipient->telegram_chat_id)) {
                                    $keyedRecipients['telegram'][] = $recipient->telegram_chat_id;
                                }
                                break;
                            case 'webhook':
                                if (isset($recipient->webhook_url)) {
                                    $keyedRecipients['webhook'][] = $recipient->webhook_url;
                                }
                                break;
                        }
                    }
                } elseif (is_string($recipient)) {
                    // Route raw strings based on format guessing or specific channel target
                    if ($channel === 'email' && filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                        $keyedRecipients['email'][] = $recipient;
                    } elseif ($channel === 'sms' && preg_match('/^\+?[0-9]{7,15}$/', $recipient)) {
                        $keyedRecipients['sms'][] = $recipient;
                    } elseif ($channel === 'whatsapp' && preg_match('/^\+?[0-9]{7,15}$/', $recipient)) {
                        $keyedRecipients['whatsapp'][] = $recipient;
                    } elseif (in_array($channel, ['slack', 'discord', 'webhook']) && filter_var($recipient, FILTER_VALIDATE_URL)) {
                        $keyedRecipients[$channel][] = $recipient;
                    } else {
                        // General fallback for raw strings if they map directly to the channel queue
                        $keyedRecipients[$channel][] = $recipient;
                    }
                }
            }
        }

        // Clean up duplicates and re-index
        foreach ($keyedRecipients as $channel => $endpoints) {
            $keyedRecipients[$channel] = array_values(array_unique($endpoints, SORT_REGULAR));
        }

        return $keyedRecipients;
    }
}
