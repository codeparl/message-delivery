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
         * Execution context supplied by the host application.
         *
         * The MessageDelivery package does not interpret the
         * individual context values.
         *
         * Example:
         *
         * [
         *     'tenant_id' => 'emma',
         *     'school_id' => '1',
         *     'context_id' => '1',
         *     'user_id' => '1',
         *     'module' => 'schoolpalm.common.student',
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
     * Get a context value.
     */
    public function context(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->context[$key] ?? $default;
    }


    /**
     * Get the context identifier used for notification persistence.
     *
     * The generic context_id is preferred when supplied by the
     * host application through ModuleBridge.
     *
     * school_id is retained as a fallback for SchoolPalm
     * compatibility.
     *
     * Returns null when the message has no notification context.
     */
    public function notificationContext(): string|int|null
    {
        return $this->context('context_id')
            ?? $this->context('school_id');
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