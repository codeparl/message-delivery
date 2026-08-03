<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery;

use SchoolPalm\MessageDelivery\Builders\ChannelMessageBuilder;
use SchoolPalm\MessageDelivery\Builders\MultiChannelMessageBuilder;
use SchoolPalm\MessageDelivery\Context\MessageContext;
use SchoolPalm\MessageDelivery\Providers\ProviderDefinition;
use SchoolPalm\MessageDelivery\Registry\DefinitionRegistry;

final class MessageDelivery
{
    /**
     * Current execution context.
     */
    private ?MessageContext $context;

    /**
     * Create a MessageDelivery instance.
     */
    public function __construct(
        ?MessageContext $context = null
    ) {
        $this->context = $context;
    }

    /**
     * Attach or replace execution context.
     */
    public static function withContext(
        array|MessageContext $context
    ): self {
        $contextInstance = $context instanceof MessageContext
            ? $context
            : new MessageContext($context);

        return new self($contextInstance);
    }

    /**
     * Merge context dynamically (works both statically and fluently).
     */
    public function mergeContext(
        array|MessageContext $context
    ): self {
        $additional = $context instanceof MessageContext ? $context->all() : $context;
        $current = $this->context?->all() ?? [];

        return new self(new MessageContext(array_merge($current, $additional)));
    }

    /**
     * Static entry point for merging context when no prior instance exists.
     */
    public static function appendContext(
        array|MessageContext $context
    ): self {
        return static::withContext($context);
    }

    /**
     * Forward static mergeContext calls or delegate dynamically via __callStatic.
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        if ($method === 'mergeContext') {
            return static::withContext(...$arguments);
        }

        throw new \BadMethodCallException("Call to undefined method " . static::class . "::{$method}()");
    }

    /**
     * Get a provider definition by name.
     *
     * @throws \InvalidArgumentException
     */
    public static function definition(string $name): ProviderDefinition
    {
        return app(DefinitionRegistry::class)->get($name);
    }

    /**
     * Get all registered provider definitions.
     *
     * @return array<string, ProviderDefinition>
     */
    public static function definitions(): array
    {
        return app(DefinitionRegistry::class)->all();
    }

    /**
     * Get all provider definitions for a specific channel.
     *
     * @return array<string, ProviderDefinition>
     */
    public static function providers(string $channel): array
    {
        return app(DefinitionRegistry::class)->forChannel($channel);
    }

    /**
     * Create a multi-channel message builder (static entry point).
     */
    public static function multi(): MultiChannelMessageBuilder
    {
        return new MultiChannelMessageBuilder(
            channels: [],
            context: [],
        );
    }

    /**
     * Create an SMS message builder.
     */
    public function sms(): ChannelMessageBuilder
    {
        return new ChannelMessageBuilder(
            channel: 'sms',
            context: $this->context?->all() ?? []
        );
    }

    /**
     * Create an email message builder.
     */
    public function email(): ChannelMessageBuilder
    {
        return new ChannelMessageBuilder(
            channel: 'email',
            context: $this->context?->all() ?? []
        );
    }

    /**
     * Create a push notification builder.
     */
    public function push(): ChannelMessageBuilder
    {
        return new ChannelMessageBuilder(
            channel: 'push',
            context: $this->context?->all() ?? []
        );
    }

    /**
     * Create a WhatsApp message builder.
     */
    public function whatsapp(): ChannelMessageBuilder
    {
        return new ChannelMessageBuilder(
            channel: 'whatsapp',
            context: $this->context?->all() ?? []
        );
    }

    /**
     * Create an in-app notification builder.
     */
    public function inApp(): ChannelMessageBuilder
    {
        return new ChannelMessageBuilder(
            channel: 'in_app',
            context: $this->context?->all() ?? []
        );
    }

    /**
     * Start a multi-channel notification with pre-set recipients.
     */
    public static function notify(
        string|array $recipients
    ): MultiChannelMessageBuilder {
        $recipients = is_array($recipients) ? $recipients : [$recipients];

        return (new MultiChannelMessageBuilder(
            channels: [],
            context: [],
        ))->to($recipients);
    }

    /**
     * Create a multi-channel message builder with context.
     */
    public function channels(
        array $channels
    ): MultiChannelMessageBuilder {
        return new MultiChannelMessageBuilder(
            channels: $channels,
            context: $this->context?->all() ?? []
        );
    }
}
