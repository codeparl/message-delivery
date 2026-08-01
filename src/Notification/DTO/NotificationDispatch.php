<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\DTO;

use SchoolPalm\MessageDelivery\Notification\Contracts\NotificationEngine;
use SchoolPalm\MessageDelivery\Notification\Support\NotificationResult;

/**
 * Fluent notification dispatch builder.
 *
 * Provides a chainable API for constructing a NotificationEvent
 * and delegating dispatch to the NotificationEngine.
 *
 * Example:
 *
 * Notification::event('student.admitted')
 *     ->data([...])
 *     ->channels(['email', 'sms'])
 *     ->priority('high')
 *     ->dispatch();
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
     * @var array<int, string>
     */
    protected array $requestedChannels = [];

    protected ?string $requestedLanguage = null;

    protected ?string $requestedPriority = null;

    protected ?string $requestedTemplate = null;

    /**
     * Create a notification dispatch builder.
     *
     * @param  string                    $event  Event name
     * @param  NotificationEngine|null   $engine Engine to delegate to
     */
    public function __construct(
        protected string $event,

        protected ?NotificationEngine $engine = null,
    ) {}


    /**
     * Attach the engine if not already set.
     */
    public function using(
        NotificationEngine $engine
    ): static {
        $this->engine = $engine;

        return $this;
    }


    /**
     * Set payload data.
     *
     * @param  array<string, mixed> $data
     */
    public function data(
        array $data
    ): static {
        $this->data = $data;

        return $this;
    }


    /**
     * Set execution context.
     *
     * @param  array<string, mixed> $context
     */
    public function context(
        array $context
    ): static {
        $this->context = $context;

        return $this;
    }


    /**
     * Set metadata.
     *
     * @param  array<string, mixed> $metadata
     */
    public function metadata(
        array $metadata
    ): static {
        $this->metadata = $metadata;

        return $this;
    }


    /**
     * Request specific channels.
     *
     * @param  array<int, string> $channels
     */
    public function channels(
        array $channels
    ): static {
        $this->requestedChannels = $channels;

        return $this;
    }


    /**
     * Request a language.
     */
    public function language(
        ?string $language
    ): static {
        if ($language !== null) {
            $this->requestedLanguage = $language;
        }

        return $this;
    }


    /**
     * Request a priority.
     */
    public function priority(
        ?string $priority
    ): static {
        if ($priority !== null) {
            $this->requestedPriority = $priority;
        }

        return $this;
    }


    /**
     * Request a template.
     */
    public function template(
        ?string $template
    ): static {
        if ($template !== null) {
            $this->requestedTemplate = $template;
        }

        return $this;
    }


    /**
     * Dispatch the notification to the engine.
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
     * Build an immutable NotificationEvent.
     */
    public function buildEvent(): NotificationEvent
    {
        return new NotificationEvent(
            event: $this->event,

            data: $this->data,

            context: $this->context,

            metadata: $this->metadata,

            requestedChannels: $this->requestedChannels,

            requestedLanguage: $this->requestedLanguage,

            requestedPriority: $this->requestedPriority,

            requestedTemplate: $this->requestedTemplate,
        );
    }
}
