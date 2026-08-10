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
     * Set payload/view data variables.
     *
     * @param  array<string, mixed>|string  $key
     * @param  mixed  $value
     */
    public function with(array|string $key, mixed $value = null): static
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * Use a Laravel view template with optional view data.
     *
     * @param  string  $view  View template name or namespace
     * @param  array<string, mixed>  $data  View data variables
     */
    public function view(string $view, array $data = []): static
    {
        $this->view = $view;

        if (!empty($data)) {
            $this->with($data);
        }

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
     * Set message title.
     *
     * The title is stored in the message data array
     * under the 'title' key. This is used by channels
     * that support a title concept (e.g., in-app
     * notifications, push notifications).
     */
    public function title(
        string $title
    ): static {

        $this->data['title'] = $title;

        return $this;
    }


    /**
     * Set the email subject.
     *
     * The subject is stored in the message data array
     * under the 'subject' key. This is used by email
     * providers (e.g. Laravel Mail) to set the email
     * subject line.
     */
    public function subject(
        string $subject
    ): static {

        $this->data['subject'] = $subject;

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
     * Send message.
     *
     * Automatically queues if queue/delay options are attached to the message,
     * unless explicitly executed via sync().
     */
    public function send()
    {
        $message = $this->build();

        return app(MessageManager::class)
            ->send(
                $message,
                queued: $this->shouldQueue($message->queueOptions)
            );
    }

    /**
     * Send synchronously without using the queue, overriding any attached queue options.
     */
    public function sync()
    {
        return app(MessageManager::class)
            ->send(
                $this->build(),
                queued: false
            );
    }

    /**
     * Send through queue explicitly.
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
     * Send through queue explicitly.
     */
    public function dispatch()
    {
        return app(MessageManager::class)
            ->send(
                $this->build(),
                queued: true
            );
    }

    /**
     * Check if the resolved QueueOptions demand queued execution.
     */
    protected function shouldQueue(?QueueOptions $options): bool
    {
        if ($options === null) {
            return false;
        }

        return $options->hasDelay()
            || $options->hasQueue()
            || $options->hasConnection();
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
