<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Contracts;

use SchoolPalm\MessageDelivery\Contracts\MessageProvider;

interface ProviderRegistry
{
    /**
     * Register a provider.
     */
    public function register(
        MessageProvider $provider
    ): void;


    /**
     * Get provider by name.
     */
    public function get(
        string $name
    ): ?MessageProvider;


    /**
     * Get providers supporting a channel.
     */
    public function forChannel(
        string $channel
    ): array;


    /**
     * Get all registered providers.
     */
    public function all(): array;


    /**
     * Check provider exists.
     */
    public function has(
        string $name
    ): bool;
}
