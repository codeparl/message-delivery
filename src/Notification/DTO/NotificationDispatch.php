<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\DTO;

use DateInterval;
use DateTimeInterface;
use SchoolPalm\MessageDelivery\Builders\QueueOptionsBuilder;
use SchoolPalm\MessageDelivery\Notification\Contracts\NotificationEngine;
use SchoolPalm\MessageDelivery\Notification\Support\NotificationResult;
use SchoolPalm\MessageDelivery\Queue\QueueOptions;

/**
 * Fluent notification dispatch builder.
 *
 * Provides a chainable API for constructing a NotificationEvent
 * where explicit configuration overrides internal engine resolvers.
 */
final class NotificationDispatch
{
    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * @var array<string, mixed>
     */
    protected array $metadata = [];

    /**
     * @var array<string, mixed>
     */
    protected array $routes = [];

    /**
     * @var array<int, string>
     */
    protected array $requestedChannels = [];

    protected ?string $requestedLanguage = null;

    protected ?string $requestedPriority = null;

    protected ?string $requestedTemplate = null;

    protected QueueOptionsBuilder $queueOptions;

    /**
     * Create a notification dispatch builder.
     */
    public function __construct(
        protected string $event,
        protected ?NotificationEngine $engine = null,
    ) {
        $this->queueOptions = new QueueOptionsBuilder();
    }

    /**
     * Attach the engine explicitly.
     */
    public function using(NotificationEngine $engine): static
    {
        $this->engine = $engine;

        return $this;
    }

    /**
     * Set a single recipient.
     */
    public function recipient(mixed $recipient): static
    {
        return $this->to($recipient);
    }

    /**
     * Set multiple recipients.
     */
    public function recipients(mixed $recipients): static
    {
        return $this->to($recipients);
    }

    /**
     * Explicitly set notification recipients.
     */
    public function to(mixed $recipients): static
    {
        $recipientsArray = is_array($recipients) ? $recipients : [$recipients];

        $merged = array_merge(
            $this->data['recipients'] ?? [],
            $recipientsArray
        );

        // Deduplicate if items are scalar (IDs, strings, emails)
        $this->data['recipients'] = array_values(array_unique($merged, SORT_REGULAR));

        return $this;
    }

    /**
     * Explicitly define property or channel route mappings.
     *
     * @param array<string, mixed> $routes E.g. ['email' => 'emailAddress', 'sms' => 'mobile']
     */
    public function routes(array $routes): static
    {
        $this->routes = array_merge($this->routes, $routes);
        $this->data['routes'] = $this->routes;
        $this->metadata['routes'] = $this->routes;

        return $this;
    }

    /**
     * Define channel route mappings or explicit targets.
     *
     * Supports:
     * - route('email', 'email_address') -> Channel specific
     * - route('email_address')          -> Global/default target key
     * - route(['email' => 'mail'])      -> Batch array mapping
     *
     * @param mixed $channelOrTarget Channel name, direct target property/closure, or array of routes
     * @param mixed $target Property name on recipient object or direct address
     */
    public function route(mixed $channelOrTarget, mixed $target = null): static
    {
        if (func_num_args() === 1) {
            if (is_array($channelOrTarget)) {
                return $this->routes($channelOrTarget);
            }

            $this->routes['*'] = $channelOrTarget;
        } else {
            $this->routes[(string) $channelOrTarget] = $target;
        }

        $this->data['routes'] = $this->routes;
        $this->metadata['routes'] = $this->routes;

        return $this;
    }

    /**
     * Set payload data variables.
     *
     * @param array<string, mixed> $data
     */
    public function data(array $data): static
    {
        // Extract special keys first before bulk merging payload data
        if (isset($data['recipients'])) {
            $this->to($data['recipients']);
            unset($data['recipients']);
        }

        if (isset($data['routes']) && is_array($data['routes'])) {
            $this->routes($data['routes']);
            unset($data['routes']);
        }

        if (isset($data['title'])) {
            $this->title((string) $data['title']);
            unset($data['title']);
        }

        if (isset($data['text'])) {
            $this->text((string) $data['text']);
            unset($data['text']);
        }

        if (isset($data['view'])) {
            $this->view((string) $data['view']);
            unset($data['view']);
        }

        if (isset($data['scheduled_at'])) {
            $this->schedule($data['scheduled_at']);
            unset($data['scheduled_at']);
        }

        $this->data = array_merge($this->data, $data);

        return $this;
    }

    /**
     * Alias for data().
     *
     * @param array<string, mixed> $data
     */
    public function with(array $data): static
    {
        return $this->data($data);
    }

    /**
     * Explicitly override notification title.
     */
    public function title(string $title): static
    {
        $this->metadata['title'] = $title;
        $this->data['title'] = $title;

        return $this;
    }

    /**
     * Explicitly override notification subject.
     */
    public function subject(string $subject): static
    {
        $this->metadata['subject'] = $subject;
        $this->data['subject'] = $subject;

        return $this;
    }

    /**
     * Explicitly override body text.
     */
    public function text(string $text): static
    {
        $this->metadata['text'] = $text;
        $this->data['text'] = $text;

        return $this;
    }

    /**
     * Explicitly override Blade/template view path with optional view data.
     * Takes precedence over TemplateResolver lookup.
     *
     * @param string $view View template path or namespace
     * @param array<string, mixed> $data View data payload
     */
    public function view(string $view, array $data = []): static
    {
        $this->metadata['view'] = $view;
        $this->data['view'] = $view;

        if (!empty($data)) {
            $this->data($data);
        }

        return $this;
    }

    /**
     * Set execution context.
     *
     * @param array<string, mixed> $context
     */
    public function context(array $context): static
    {
        $this->context = array_merge($this->context, $context);

        return $this;
    }

    /**
     * Set metadata array.
     *
     * @param array<string, mixed> $metadata
     */
    public function metadata(array $metadata): static
    {
        if (isset($metadata['routes']) && is_array($metadata['routes'])) {
            $this->routes($metadata['routes']);
        }

        $this->metadata = array_merge($this->metadata, $metadata);

        return $this;
    }

    /**
     * Explicitly request delivery channels.
     * Takes precedence over ChannelResolver lookup.
     *
     * @param array<int, string>|string $channels
     */
    public function channels(array|string $channels): static
    {
        $this->requestedChannels = is_array($channels) ? (array) $channels : [$channels];

        return $this;
    }

    /**
     * Explicitly request delivery language.
     * Takes precedence over LocaleResolver lookup.
     */
    public function language(?string $language): static
    {
        $this->requestedLanguage = $language;

        return $this;
    }

    /**
     * Explicitly request delivery priority.
     * Takes precedence over PriorityResolver lookup.
     */
    public function priority(?string $priority): static
    {
        $this->requestedPriority = $priority;

        return $this;
    }

    /**
     * Explicitly request dynamic template key or options.
     * Takes precedence over default event template mappings.
     */
    public function template(string|array|null $template): static
    {
        if (is_array($template)) {
            if (isset($template['view'])) {
                $this->view((string) $template['view']);
            }
            $this->requestedTemplate = isset($template['name']) ? (string) $template['name'] : null;
        } else {
            $this->requestedTemplate = $template;
        }

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Queue Options
    |--------------------------------------------------------------------------
    */

    /**
     * Explicitly schedule delivery delay.
     * Takes precedence over ScheduleResolver.
     */
    public function schedule(DateTimeInterface|DateInterval|int $delay): static
    {
        $this->metadata['scheduled_at'] = $delay;
        $this->queueOptions->delay($delay);

        return $this;
    }

    /**
     * Alias for schedule().
     */
    public function delay(DateTimeInterface|DateInterval|int $delay): static
    {
        return $this->schedule($delay);
    }

    /**
     * Specify the target queue connection.
     */
    public function onConnection(string $connection): static
    {
        $this->queueOptions->onConnection($connection);

        return $this;
    }

    /**
     * Specify the target queue name.
     */
    public function onQueue(string $queue): static
    {
        $this->queueOptions->onQueue($queue);

        return $this;
    }

    /**
     * Specify maximum retry attempts.
     */
    public function tries(int $tries): static
    {
        $this->queueOptions->tries($tries);

        return $this;
    }

    /**
     * Specify job execution timeout in seconds.
     */
    public function timeout(int $seconds): static
    {
        $this->queueOptions->timeout($seconds);

        return $this;
    }

    /**
     * Specify backoff delay strategy.
     *
     * @param int|array<int, int> $backoff
     */
    public function backoff(int|array $backoff): static
    {
        $this->queueOptions->backoff($backoff);

        return $this;
    }

    /**
     * Indicate if the job should dispatch after DB transaction commits.
     */
    public function afterCommit(bool $value = true): static
    {
        $this->queueOptions->afterCommit($value);

        return $this;
    }

    /**
     * Configure queue options using a callback closure.
     */
    public function queueOptions(callable $callback): static
    {
        $callback($this->queueOptions);

        return $this;
    }

    /**
     * Build the configured QueueOptions object.
     */
    public function buildQueueOptions(): QueueOptions
    {
        return $this->queueOptions->build();
    }

    /**
     * Dispatch event through the notification engine.
     */
    public function dispatch(): NotificationResult
    {
        if ($this->engine === null) {
            $this->engine = app(NotificationEngine::class);
        }

        return $this->engine->dispatch(
            $this->buildEvent()
        );
    }

    /**
     * Build the immutable NotificationEvent DTO.
     */
    public function buildEvent(): NotificationEvent
    {
        $queueOptions = $this->buildQueueOptions();

        // Priority order: user-defined metadata < queue options < explicit queue instance reference
        $metadata = array_merge(
            $this->metadata,
            $queueOptions->toArray(),
            ['queue_options' => $queueOptions]
        );

        return new NotificationEvent(
            event: $this->event,
            data: $this->data,
            context: $this->context,
            metadata: $metadata,
            requestedChannels: $this->requestedChannels,
            requestedLanguage: $this->requestedLanguage,
            requestedPriority: $this->requestedPriority,
            requestedTemplate: $this->requestedTemplate,
        );
    }
}
