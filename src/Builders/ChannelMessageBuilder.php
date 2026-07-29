<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Builders;

use DateInterval;
use DateTimeInterface;
use SchoolPalm\MessageDelivery\Managers\MessageManager;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Queue\QueueOptions;

final class ChannelMessageBuilder
{
    protected array $recipients = [];

    protected ?string $view = null;

    protected ?string $template = null;

    protected ?string $text = null;

    protected array $data = [];

    protected ?string $provider = null;

    protected ?string $priority = null;


    protected QueueOptionsBuilder $queueOptions;


    public function __construct(
        protected readonly string $channel,

        protected readonly array $context = [],
    ) {

        $this->queueOptions = new QueueOptionsBuilder();
    }


    /**
     * Set message recipients.
     */
    public function to(
        string|array $recipients
    ): static {

        $this->recipients = is_array($recipients)
            ? $recipients
            : [$recipients];

        return $this;
    }


    /**
     * Use a Laravel view template.
     */
    public function view(
        string $view
    ): static {

        $this->view = $view;

        return $this;
    }


    /**
     * Use stored message template.
     */
    public function template(
        string $template
    ): static {

        $this->template = $template;

        return $this;
    }


    /**
     * Set raw message text.
     */
    public function text(
        string $text
    ): static {

        $this->text = $text;

        return $this;
    }


    /**
     * Add template variables.
     */
    public function with(
        array $data
    ): static {

        $this->data = array_merge(
            $this->data,
            $data
        );

        return $this;
    }


    /**
     * Select specific provider.
     */
    public function provider(
        string $provider
    ): static {

        $this->provider = $provider;

        return $this;
    }


    /**
     * Set message priority.
     */
    public function priority(
        string $priority
    ): static {

        $this->priority = $priority;

        return $this;
    }


    /*
    |--------------------------------------------------------------------------
    | Queue Options
    |--------------------------------------------------------------------------
    */


    public function delay(
        DateTimeInterface|DateInterval|int $delay
    ): static {

        $this->queueOptions->delay($delay);

        return $this;
    }


    public function onQueue(
        string $queue
    ): static {

        $this->queueOptions->onQueue($queue);

        return $this;
    }


    public function onConnection(
        string $connection
    ): static {

        $this->queueOptions->onConnection($connection);

        return $this;
    }


    public function tries(
        int $tries
    ): static {

        $this->queueOptions->tries($tries);

        return $this;
    }


    public function timeout(
        int $seconds
    ): static {

        $this->queueOptions->timeout($seconds);

        return $this;
    }


    public function backoff(
        int|array $backoff
    ): static {

        $this->queueOptions->backoff($backoff);

        return $this;
    }


    public function afterCommit(
        bool $value = true
    ): static {

        $this->queueOptions->afterCommit($value);

        return $this;
    }


    /**
     * Advanced queue configuration.
     */
    public function queueOptions(
        callable $callback
    ): static {

        $callback($this->queueOptions);

        return $this;
    }


    /*
    |--------------------------------------------------------------------------
    | Sending
    |--------------------------------------------------------------------------
    */


    /**
     * Send immediately.
     */
    public function send()
    {
        return app(MessageManager::class)
            ->send(
                $this->build()
            );
    }


    /**
     * Send through queue.
     */
    public function queue()
    {
        return app(MessageManager::class)
            ->send(
                $this->build(),
                queued: true
            );
    }


    /**
     * Build message object.
     */
    protected function build(): Message
    {
        return new Message(
            channel: $this->channel,

            recipients: $this->recipients,

            view: $this->view,

            template: $this->template,

            text: $this->text,

            data: $this->data,

            provider: $this->provider,

            priority: $this->priority,

            context: $this->context,

            queueOptions: $this->queueOptions->build(),
        );
    }
}
