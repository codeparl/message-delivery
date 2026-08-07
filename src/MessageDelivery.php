<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery;

use SchoolPalm\MessageDelivery\Builders\ChannelMessageBuilder;
use SchoolPalm\MessageDelivery\Builders\MultiChannelMessageBuilder;
use SchoolPalm\MessageDelivery\Context\MessageContext;
use SchoolPalm\MessageDelivery\Providers\ConfigurationField;
use SchoolPalm\MessageDelivery\Providers\ProviderConfigurationFields;
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

    /*
    |--------------------------------------------------------------------------
    | Provider Configuration Fields
    |--------------------------------------------------------------------------
    |
    | These APIs expose the configuration schema for every provider so
    | consuming applications can initialize settings in their own storage
    | (e.g. a tenant settings scope). The package does not persist anything.
    |
    */

    /**
     * Get a single provider's configuration fields as arrays.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function providerConfigurationFields(string $name): array
    {
        return ProviderConfigurationFields::make()->provider($name);
    }

    /**
     * Get a single provider's ConfigurationField objects.
     *
     * @return array<ConfigurationField>
     */
    public static function providerFieldObjects(string $name): array
    {
        return ProviderConfigurationFields::make()->providerFields($name);
    }

    /**
     * Get all registered providers keyed by name → fields as arrays.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function allProviderConfigurationFields(): array
    {
        return ProviderConfigurationFields::make()->all();
    }

    /**
     * Get all providers for a channel → fields as arrays.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function providerConfigurationFieldsForChannel(string $channel): array
    {
        return ProviderConfigurationFields::make()->forChannel($channel);
    }

    /**
     * Get a single provider configuration field by provider and field name.
     *
     * @return array<string, mixed>|null
     */
    public static function providerConfigurationField(string $provider, string $field): ?array
    {
        return ProviderConfigurationFields::make()->field($provider, $field);
    }

    /**
     * Build a flat settings map ready for DB seeding.
     *
     * @return array<string, mixed>
     */
    public static function providerSeedSettings(): array
    {
        return ProviderConfigurationFields::make()->seedSettings();
    }

    /**
     * Build scoped settings (secured vs secrets) for DB seeding.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    public static function providerScopedSettings(): array
    {
        return ProviderConfigurationFields::make()->scopedSettings();
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
