<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Registry;

use InvalidArgumentException;
use SchoolPalm\MessageDelivery\Providers\ProviderDefinition;

/**
 * Registry for provider definitions.
 *
 * Stores ProviderDefinition instances and provides lookup
 * by name, channel, or all definitions. This is the single
 * source of truth for provider configuration schema metadata.
 *
 * Definitions are registered by the service provider during
 * the boot phase and consumed by administration interfaces
 * to dynamically build configuration forms.
 */
final class DefinitionRegistry
{
    /**
     * Registered definitions, keyed by provider name.
     *
     * @var array<string, ProviderDefinition>
     */
    private array $definitions = [];

    /**
     * Register a provider definition.
     *
     * @throws InvalidArgumentException If a definition with the same name already exists
     */
    public function register(ProviderDefinition $definition): void
    {
        $name = $definition->name();

        if (isset($this->definitions[$name])) {
            throw new InvalidArgumentException(
                "Provider definition [{$name}] is already registered."
            );
        }

        $this->definitions[$name] = $definition;
    }

    /**
     * Check if a definition exists by provider name.
     */
    public function has(string $name): bool
    {
        return isset($this->definitions[$name]);
    }

    /**
     * Get a provider definition by name.
     *
     * @throws InvalidArgumentException If the definition is not found
     */
    public function get(string $name): ProviderDefinition
    {
        if (! $this->has($name)) {
            throw new InvalidArgumentException(
                "Provider definition [{$name}] is not registered."
            );
        }

        return $this->definitions[$name];
    }

    /**
     * Get all registered provider definitions.
     *
     * @return array<string, ProviderDefinition>
     */
    public function all(): array
    {
        return $this->definitions;
    }

    /**
     * Get all provider definitions for a specific channel.
     *
     * @return array<string, ProviderDefinition>
     */
    public function forChannel(string $channel): array
    {
        $result = [];

        foreach ($this->definitions as $name => $definition) {
            if ($definition->channel() === $channel) {
                $result[$name] = $definition;
            }
        }

        return $result;
    }

    /**
     * Remove a registered definition.
     */
    public function forget(string $name): void
    {
        unset($this->definitions[$name]);
    }

    /**
     * Clear all registered definitions.
     */
    public function clear(): void
    {
        $this->definitions = [];
    }
}
