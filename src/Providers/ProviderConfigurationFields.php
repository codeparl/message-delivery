<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers;

use InvalidArgumentException;
use SchoolPalm\MessageDelivery\Registry\DefinitionRegistry;

/**
 * Provider configuration field helper.
 *
 * Exposes the configuration schema for every registered provider
 * so consuming applications can initialize settings in their own
 * storage (e.g. a tenant settings scope) without this package
 * persisting anything.
 *
 * This helper is deliberately storage-agnostic: it only reads the
 * package DefinitionRegistry and returns the fields as arrays.
 * The caller decides where and how to save them.
 *
 * Example usage inside a consuming application:
 *
 *   use SchoolPalm\MessageDelivery\Providers\ProviderConfigurationFields;
 *
 *   // Fields for a single provider, ready to validate / persist.
 *   $fields = ProviderConfigurationFields::provider('twilio-sms');
 *
 *   // All providers grouped by name.
 *   $all = ProviderConfigurationFields::all();
 *
 *   // Flat map of provider defaults for DB seeding.
 *   $defaults = ProviderConfigurationFields::seedSettings();
 *
 * @see ProviderDefinition::toConfigurationArray()
 * @see ConfigurationField::toArray()
 */
final class ProviderConfigurationFields
{
    public function __construct(
        private readonly DefinitionRegistry $registry,
    ) {}

    /**
     * Resolve from the application container.
     */
    public static function make(): self
    {
        return new self(
            app(DefinitionRegistry::class)
        );
    }

    /**
     * Get a single provider's configuration fields as arrays.
     *
     * Useful when a consuming application needs the fields for one
     * provider to validate or initialize its settings scope.
     *
     * @return array<int, array<string, mixed>>
     */
    public function provider(string $name): array
    {
        return $this->definition($name)->toConfigurationArray();
    }

    /**
     * Get a single provider's ConfigurationField objects.
     *
     * @return array<ConfigurationField>
     */
    public function providerFields(string $name): array
    {
        return $this->definition($name)->configurationFields();
    }

    /**
     * Get the provider metadata (name, channel, label, fields).
     *
     * @return array<string, mixed>
     */
    public function metadata(string $name): array
    {
        return $this->definition($name)->toArray();
    }

    /**
     * Get all registered providers keyed by name → fields as arrays.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function all(): array
    {
        $result = [];

        foreach ($this->registry->all() as $name => $definition) {
            $result[$name] = $definition->toConfigurationArray();
        }

        return $result;
    }

    /**
     * Get all providers for a channel → fields as arrays.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function forChannel(string $channel): array
    {
        $result = [];

        foreach ($this->registry->forChannel($channel) as $name => $definition) {
            $result[$name] = $definition->toConfigurationArray();
        }

        return $result;
    }

    /**
     * Get a provider configuration field by provider name and field name.
     *
     * @return array<string, mixed>|null
     */
    public function field(string $provider, string $field): ?array
    {
        foreach ($this->provider($provider) as $configField) {
            if (($configField['name'] ?? null) === $field) {
                return $configField;
            }
        }

        return null;
    }

    /**
     * Build a flat settings map ready for DB seeding.
     *
     * Returns each provider's fields keyed as "provider.field" with
     * its default value (or null when no default is defined). This is
     * provided as a convenience so consuming applications can store
     * the initial settings in their own storage/scope.
     *
     * @return array<string, mixed>
     */
    public function seedSettings(): array
    {
        $result = [];

        foreach ($this->registry->all() as $name => $definition) {
            foreach ($definition->toConfigurationArray() as $field) {
                $result[$name . '.' . $field['name']] = $field['default'];
            }
        }

        return $result;
    }

    /**
     * Build a nested settings map keyed by provider name.
     *
     * Each provider gets its non-secret fields under
     * "secured.{provider}.{field}" and its secret fields under
     * "secrets.{provider}.{field}" so consuming applications can
     * store credentials separately.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    public function scopedSettings(): array
    {
        $scopes = [
            'secured' => [],
            'secrets' => [],
        ];

        foreach ($this->registry->all() as $name => $definition) {
            foreach ($definition->toConfigurationArray() as $field) {
                $target = $field['secret'] ? 'secrets' : 'secured';

                $scopes[$target][$name][$field['name']] = $field['default'];
            }
        }

        return $scopes;
    }

    /**
     * Resolve a provider definition by name.
     *
     * @throws InvalidArgumentException
     */
    private function definition(string $name): ProviderDefinition
    {
        return $this->registry->get($name);
    }
}
