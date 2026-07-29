<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Messages;

use DateTimeInterface;
use SchoolPalm\MessageDelivery\Queue\QueueOptions;

final class Message
{
    /**
     * Create message instance.
     */
    public function __construct(

        /**
         * Delivery channel.
         *
         * Example:
         *
         * sms
         * email
         * whatsapp
         * push
         */
        public readonly string $channel,


        /**
         * Message recipients.
         */
        public readonly array $recipients,


        /**
         * Laravel view name.
         */
        public readonly ?string $view = null,


        /**
         * Database template name.
         */
        public readonly ?string $template = null,


        /**
         * Raw message content.
         */
        public readonly ?string $text = null,


        /**
         * Template variables.
         */
        public readonly array $data = [],


        /**
         * Specific provider override.
         */
        public readonly ?string $provider = null,


        /**
         * Message priority.
         */
        public readonly ?string $priority = null,


        /**
         * Execution context.
         *
         * Example:
         *
         * [
         *   'tenant_id'=>1,
         *   'school_id'=>10,
         *   'module'=>'fees'
         * ]
         */
        public readonly array $context = [],


        /**
         * Queue execution options.
         */
        public readonly ?QueueOptions $queueOptions = null,

    ) {}


    /**
     * Check whether message uses a view.
     */
    public function hasView(): bool
    {
        return $this->view !== null;
    }


    /**
     * Check whether message uses database template.
     */
    public function hasTemplate(): bool
    {
        return $this->template !== null;
    }


    /**
     * Check whether message contains raw text.
     */
    public function hasText(): bool
    {
        return $this->text !== null;
    }


    /**
     * Check whether message has queue options.
     */
    public function isQueued(): bool
    {
        return $this->queueOptions !== null;
    }


    /**
     * Check whether a specific provider was selected.
     */
    public function hasProvider(): bool
    {
        return $this->provider !== null;
    }


    /**
     * Get context value.
     */
    public function context(
        string $key,
        mixed $default = null
    ): mixed {

        return $this->context[$key] ?? $default;
    }


    /**
     * Convert message to array.
     */
    public function toArray(): array
    {
        return [
            'channel' => $this->channel,

            'recipients' => $this->recipients,

            'view' => $this->view,

            'template' => $this->template,

            'text' => $this->text,

            'data' => $this->data,

            'provider' => $this->provider,

            'priority' => $this->priority,

            'context' => $this->context,

            'queue_options' =>
            $this->queueOptions?->toArray(),
        ];
    }
}
