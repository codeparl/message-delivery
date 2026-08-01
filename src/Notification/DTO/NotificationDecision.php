<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\DTO;

use SchoolPalm\MessageDelivery\Templates\Template;

/**
 * Immutable notification decision.
 *
 * Represents everything resolved by the engine before delivery:
 * channels, template, language, priority, retry policy, schedule
 * and recipients.
 */
final class NotificationDecision
{
    /**
     * Create a notification decision.
     *
     * @param  array<int, string>       $channels   Channels to deliver through
     * @param  array<int, mixed>        $recipients Resolved recipients
     * @param  array<string, mixed>     $data       Payload data
     * @param  Template|null            $template   Resolved template
     * @param  string|null              $language   Resolved language
     * @param  string|null              $priority   Resolved priority
     * @param  RetryPolicy|null         $retryPolicy Resolved retry policy
     * @param  \DateInterval|\DateTimeInterface|int|null $schedule Resolved schedule
     * @param  array<string, mixed>     $preferences Resolved preferences
     */
    public function __construct(
        public readonly array $channels = [],

        public readonly array $recipients = [],

        public readonly array $data = [],

        public readonly ?Template $template = null,

        public readonly ?string $language = null,

        public readonly ?string $priority = null,

        public readonly ?RetryPolicy $retryPolicy = null,

        public readonly \DateInterval|\DateTimeInterface|int|null $schedule = null,

        public readonly array $preferences = [],
    ) {}


    /**
     * Check whether the decision has recipients.
     */
    public function hasRecipients(): bool
    {
        return ! empty($this->recipients);
    }


    /**
     * Check whether the decision has channels.
     */
    public function hasChannels(): bool
    {
        return ! empty($this->channels);
    }


    /**
     * Check whether the decision has a template.
     */
    public function hasTemplate(): bool
    {
        return $this->template !== null;
    }


    /**
     * Convert the decision to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'channels' => $this->channels,
            'recipients' => $this->recipients,
            'data' => $this->data,
            'template' => $this->template?->toArray(),
            'language' => $this->language,
            'priority' => $this->priority,
            'retry_policy' => $this->retryPolicy?->toArray(),
            'schedule' => $this->schedule,
            'preferences' => $this->preferences,
        ];
    }
}

