<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Builders;

use SchoolPalm\MessageDelivery\Messages\MultiChannelResult;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;

/**
 * Allows sending the same communication through multiple channels.
 *
 * Who consumes it:
 * - Module developers across SchoolPalm modules
 *   (Finance, Admissions, Communications, etc.)
 *
 * Example:
 *
 * MessageDelivery::multi()
 *     ->channels(['email', 'sms'])
 *     ->to($parent)
 *     ->text('Your child has been admitted')
 *     ->send();
 *
 * Responsibility:
 * - Manage multiple ChannelMessageBuilder instances
 * - Share common message data (recipients, text, etc.)
 * - Dispatch messages to multiple channels
 * - Aggregate results into MultiChannelResult
 *
 * What it does NOT handle:
 * - Does NOT send messages directly (delegates to ChannelMessageBuilder)
 * - Does NOT know about providers or APIs
 * - Does NOT know email/SMS implementation details
 * - Does NOT handle channel-specific logic
 */
final class MultiChannelMessageBuilder
{
    protected array $channels = [];

    protected array $recipients = [];

    protected ?string $view = null;

    protected ?string $template = null;

    protected ?string $text = null;

    protected array $data = [];

    protected ?string $provider = null;

    protected ?string $priority = null;

    protected array $context = [];

    protected QueueOptionsBuilder $queueOptions;

    /**
     * Create a MultiChannelMessageBuilder instance.
     *
     * @param array $channels  List of channel names (e.g. ['email', 'sms'])
     * @param array $context   Execution context from MessageDelivery::withContext()
     */
    public function __construct(
        array $channels = [],
        array $context = [],
    ) {
        $this->channels = $channels;

        $this->context = $context;

        $this->queueOptions = new QueueOptionsBuilder();
    }

    /**
     * Set execution context.
     *
     * Context is propagated to every channel builder.
     *
     * @param  array $context
     * @return static
     */
    public function context(
        array $context
    ): static {
        $this->context = array_merge(
            $this->context,
            $context
        );

        return $this;
    }

    /**
     * Set the channels to send through.
     *
     * @param  array $channels  List of channel identifiers
     * @return static
     */
    public function channels(
        array $channels
    ): static {
        $this->channels = $channels;

        return $this;
    }

    /**
     * Set message recipients.
     *
     * @param  string|array $recipients
     * @return static
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
     * Set raw message text.
     *
     * @param  string $text
     * @return static
     */
    public function text(
        string $text
    ): static {
        $this->text = $text;

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
     *
     * @param  string $template
     * @return static
     */
    public function template(
        string $template
    ): static {
        $this->template = $template;

        return $this;
    }



    /**
     * Set the email subject.
     *
     * The subject is stored in the message data array
     * under the 'subject' key. This is used by email
     * providers (e.g. Laravel Mail) to set the email
     * subject line.
     *
     * @param  string $subject
     * @return static
     */
    public function subject(
        string $subject
    ): static {
        $this->data['subject'] = $subject;

        return $this;
    }

    /**
     * Set the sms title.
     *
     * The title is stored in the message data array
     * under the 'title' key. This is used by sms
     * providers.
     *
     * @param  string $title
     * @return static
     */
    public function title(
        string $title
    ): static {
        $this->data['title'] = $title;

        return $this;
    }

    /**
     * Select specific provider.
     *
     * @param  string $provider
     * @return static
     */
    public function provider(
        string $provider
    ): static {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Set message priority.
     *
     * @param  string $priority
     * @return static
     */
    public function priority(
        string $priority
    ): static {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Set queue delay.
     *
     * @param  \DateInterval|\DateTimeInterface|int $delay
     * @return static
     */
    public function delay(
        \DateInterval|\DateTimeInterface|int $delay
    ): static {
        $this->queueOptions->delay($delay);

        return $this;
    }

    /**
     * Set queue name.
     *
     * @param  string $queue
     * @return static
     */
    public function onQueue(
        string $queue
    ): static {
        $this->queueOptions->onQueue($queue);

        return $this;
    }

    /**
     * Set queue connection.
     *
     * @param  string $connection
     * @return static
     */
    public function onConnection(
        string $connection
    ): static {
        $this->queueOptions->onConnection($connection);

        return $this;
    }

    /**
     * Set maximum retry attempts.
     *
     * @param  int $tries
     * @return static
     */
    public function tries(
        int $tries
    ): static {
        $this->queueOptions->tries($tries);

        return $this;
    }

    /**
     * Set job timeout.
     *
     * @param  int $seconds
     * @return static
     */
    public function timeout(
        int $seconds
    ): static {
        $this->queueOptions->timeout($seconds);

        return $this;
    }

    /**
     * Set retry backoff.
     *
     * @param  int|array $backoff
     * @return static
     */
    public function backoff(
        int|array $backoff
    ): static {
        $this->queueOptions->backoff($backoff);

        return $this;
    }

    /**
     * Dispatch after database commit.
     *
     * @param  bool $value
     * @return static
     */
    public function afterCommit(
        bool $value = true
    ): static {
        $this->queueOptions->afterCommit($value);

        return $this;
    }

    /**
     * Send through all channels.
     *
     * Automatically routes to queue if queue/delay options
     * are configured on this builder.
     *
     * @return MultiChannelResult
     */
    public function send(): MultiChannelResult
    {
        if ($this->shouldQueue()) {
            return $this->queue();
        }

        return $this->dispatchToChannels('send');
    }

    /**
     * Send synchronously through all channels without queuing.
     *
     * Dispatches to each channel sequentially in the current
     * process, ignoring attached queue options.
     *
     * @return MultiChannelResult
     */
    public function sync(): MultiChannelResult
    {
        return $this->dispatchToChannels('sync');
    }

    /**
     * Send through queue for all channels.
     *
     * Dispatches each channel's message to the queue.
     *
     * @return MultiChannelResult
     */
    public function queue(): MultiChannelResult
    {
        return $this->dispatchToChannels('queue');
    }


    /**
     * Send through queue for all channels.
     *
     * Dispatches each channel's message to the queue.
     *
     * @return MultiChannelResult
     */
    public function dispatch(): MultiChannelResult
    {
        return $this->dispatchToChannels('queue');
    }

    /**
     * Execute the given dispatch method ('send', 'sync', or 'queue') across all channels.
     *
     * @param  string $method
     * @return MultiChannelResult
     */
    protected function dispatchToChannels(string $method): MultiChannelResult
    {
        $multiResult = new MultiChannelResult();

        foreach ($this->channels as $channel) {
            try {
                $builder = $this->createChannelBuilder($channel);
                $result = $builder->{$method}();
            } catch (\Throwable $e) {
                $result = DeliveryResult::failure(
                    error: $e->getMessage(),
                    provider: null,
                    metadata: ['exception' => get_class($e)]
                );
            }

            $multiResult->add($channel, $result);
        }

        return $multiResult;
    }

    /**
     * Determine if the configured queue options require queued delivery.
     *
     * @return bool
     */
    protected function shouldQueue(): bool
    {
        if (! $this->queueOptions->hasConfig()) {
            return false;
        }

        $options = $this->queueOptions->build();

        return $options->hasDelay()
            || $options->hasQueue()
            || $options->hasConnection();
    }

    /**
     * Create a ChannelMessageBuilder for a given channel
     * with all shared values applied.
     *
     * @param  string $channel  Channel name
     * @return ChannelMessageBuilder
     */
    private function createChannelBuilder(
        string $channel
    ): ChannelMessageBuilder {
        $builder = new ChannelMessageBuilder(
            channel: $channel,
            context: $this->context,
        );

        if (! empty($this->recipients)) {
            $builder->to($this->recipients);
        }

        if ($this->text !== null) {
            $builder->text($this->text);
        }

        if ($this->view !== null) {
            $builder->view($this->view);
        }

        if ($this->template !== null) {
            $builder->template($this->template);
        }

        if (! empty($this->data)) {
            $builder->with($this->data);
        }

        if ($this->provider !== null) {
            $builder->provider($this->provider);
        }

        if ($this->priority !== null) {
            $builder->priority($this->priority);
        }

        if ($this->queueOptions->hasConfig()) {
            $options = $this->queueOptions->build();

            if ($options->hasConnection()) {
                $builder->onConnection($options->connection);
            }

            if ($options->hasQueue()) {
                $builder->onQueue($options->queue);
            }

            if ($options->hasDelay()) {
                $builder->delay($options->delay);
            }

            if ($options->hasTries()) {
                $builder->tries($options->tries);
            }

            if ($options->hasTimeout()) {
                $builder->timeout($options->timeout);
            }

            if ($options->backoff !== null) {
                $builder->backoff($options->backoff);
            }

            if ($options->afterCommit) {
                $builder->afterCommit();
            }
        }

        return $builder;
    }
}
