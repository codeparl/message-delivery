<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Builders;

use DateInterval;
use DateTimeInterface;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\MultiChannelResult;
use Throwable;

/**
 * Allows sending the same communication through multiple channels.
 */
final class MultiChannelMessageBuilder
{
    /**
     * @var array<int, string>
     */
    protected array $channels = [];

    /**
     * @var array<int, mixed>
     */
    protected array $recipients = [];

    /**
     * Property/channel route mappings for DTOs, models, or plain arrays.
     * e.g. ['email' => 'emailAddress', 'sms' => 'mobile', 'in_app' => 'parentId']
     *
     * @var array<string, mixed>
     */
    protected array $routes = [];

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
     * @param array<int, string> $channels List of channel names (e.g. ['email', 'sms'])
     * @param array $context Execution context from MessageDelivery::withContext()
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
     * @param array $context
     * @return static
     */
    public function context(array $context): static
    {
        $this->context = array_merge($this->context, $context);

        return $this;
    }

    /**
     * Set the channels to send through.
     *
     * @param array<int, string> $channels List of channel identifiers
     * @return static
     */
    public function channels(array $channels): static
    {
        $this->channels = $channels;

        return $this;
    }

    /**
     * Set message recipients (accepts models, DTO objects, strings, or arrays).
     *
     * @param mixed $recipients
     * @return static
     */
    public function to(mixed $recipients): static
    {
        if (is_array($recipients)) {
            $this->recipients = array_is_list($recipients) ? $recipients : [$recipients];
        } else {
            $this->recipients = [$recipients];
        }

        return $this;
    }

    /**
     * Define channel or property routes mapping.
     *
     * @param array<string, mixed> $routes E.g. ['email' => 'emailAddress', 'sms' => 'mobile']
     * @return static
     */
    public function routes(array $routes): static
    {
        $this->routes = array_merge($this->routes, $routes);

        return $this;
    }

    /**
     * Convenience method to define a single channel route mapping or target.
     *
     * @param string $channel E.g. 'email', 'sms', 'in_app'
     * @param mixed $target Property name on recipient object, Closure, or raw target value
     * @return static
     */
    public function route(string $channel, mixed $target): static
    {
        $this->routes[$channel] = $target;

        return $this;
    }

    /**
     * Set raw message text.
     *
     * @param string $text
     * @return static
     */
    public function text(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Set payload/view data variables.
     *
     * @param array<string, mixed>|string $key
     * @param mixed $value
     * @return static
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
     * @param string $view View template name or namespace
     * @param array<string, mixed> $data View data variables
     * @return static
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
     * @param string $template
     * @return static
     */
    public function template(string $template): static
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Set the email subject.
     *
     * @param string $subject
     * @return static
     */
    public function subject(string $subject): static
    {
        $this->data['subject'] = $subject;

        return $this;
    }

    /**
     * Set the title.
     *
     * @param string $title
     * @return static
     */
    public function title(string $title): static
    {
        $this->data['title'] = $title;

        return $this;
    }

    /**
     * Select specific provider.
     *
     * @param string $provider
     * @return static
     */
    public function provider(string $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Set message priority.
     *
     * @param string $priority
     * @return static
     */
    public function priority(string $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Set queue delay.
     *
     * @param DateInterval|DateTimeInterface|int $delay
     * @return static
     */
    public function delay(DateInterval|DateTimeInterface|int $delay): static
    {
        $this->queueOptions->delay($delay);

        return $this;
    }

    /**
     * Set queue name.
     *
     * @param string $queue
     * @return static
     */
    public function onQueue(string $queue): static
    {
        $this->queueOptions->onQueue($queue);

        return $this;
    }

    /**
     * Set queue connection.
     *
     * @param string $connection
     * @return static
     */
    public function onConnection(string $connection): static
    {
        $this->queueOptions->onConnection($connection);

        return $this;
    }

    /**
     * Set maximum retry attempts.
     *
     * @param int $tries
     * @return static
     */
    public function tries(int $tries): static
    {
        $this->queueOptions->tries($tries);

        return $this;
    }

    /**
     * Set job timeout.
     *
     * @param int $seconds
     * @return static
     */
    public function timeout(int $seconds): static
    {
        $this->queueOptions->timeout($seconds);

        return $this;
    }

    /**
     * Set retry backoff.
     *
     * @param int|array $backoff
     * @return static
     */
    public function backoff(int|array $backoff): static
    {
        $this->queueOptions->backoff($backoff);

        return $this;
    }

    /**
     * Dispatch after database commit.
     *
     * @param bool $value
     * @return static
     */
    public function afterCommit(bool $value = true): static
    {
        $this->queueOptions->afterCommit($value);

        return $this;
    }

    /**
     * Send through all channels.
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
     * @return MultiChannelResult
     */
    public function sync(): MultiChannelResult
    {
        return $this->dispatchToChannels('sync');
    }

    /**
     * Send through queue for all channels.
     *
     * @return MultiChannelResult
     */
    public function queue(): MultiChannelResult
    {
        return $this->dispatchToChannels('queue');
    }

    /**
     * Alias for queue().
     *
     * @return MultiChannelResult
     */
    public function dispatch(): MultiChannelResult
    {
        return $this->queue();
    }

    /**
     * Execute the given dispatch method across all channels.
     *
     * @param string $method
     * @return MultiChannelResult
     */
    protected function dispatchToChannels(string $method): MultiChannelResult
    {
        $multiResult = new MultiChannelResult();

        foreach ($this->channels as $channel) {
            try {
                $builder = $this->createChannelBuilder($channel);
                $result = $builder->{$method}();
            } catch (Throwable $e) {
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
     * Determine if queue options require queued delivery.
     *
     * @return bool
     */
    protected function shouldQueue(): bool
    {
        if (!$this->queueOptions->hasConfig()) {
            return false;
        }

        $options = $this->queueOptions->build();

        return $options->hasDelay()
            || $options->hasQueue()
            || $options->hasConnection();
    }

    /**
     * Create a ChannelMessageBuilder for a given channel with all shared values applied.
     *
     * @param string $channel
     * @return ChannelMessageBuilder
     */
    private function createChannelBuilder(string $channel): ChannelMessageBuilder
    {
        $builder = new ChannelMessageBuilder(
            channel: $channel,
            context: $this->context,
        );

        if (!empty($this->recipients)) {
            $builder->to($this->recipients);
        }

        if (!empty($this->routes)) {
            $builder->routes($this->routes);
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

        if (!empty($this->data)) {
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

            if ($options->tries !== null) {
                $builder->tries($options->tries);
            }

            if ($options->timeout !== null) {
                $builder->timeout($options->timeout);
            }

            if ($options->backoff !== null) {
                $builder->backoff($options->backoff);
            }

            if ($options->afterCommit !== null) {
                $builder->afterCommit($options->afterCommit);
            }
        }

        return $builder;
    }
}
