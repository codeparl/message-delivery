<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Registry;

use InvalidArgumentException;
use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Contracts\ProviderFactory;

final class ProviderRegistry
{
    /**
     * Registered provider factories.
     *
     * Structure:
     *
     * [
     *     'sms' => [
     *          'egosms' => Factory
     *     ]
     * ]
     *
     * @var array<string,array<string,ProviderFactory>>
     */
    protected array $providers = [];


    /**
     * Register provider factory.
     */
    public function register(
        ProviderFactory $factory
    ): void {

        $this->providers[$factory->channel()][$factory->name()] = $factory;
    }


    /**
     * Check provider exists.
     */
    public function has(
        string $channel,
        string $provider
    ): bool {

        return isset(
            $this->providers[$channel][$provider]
        );
    }


    /**
     * Create configured provider instance.
     *
     * @throws InvalidArgumentException
     */
    public function make(
        string $channel,
        string $provider,
        array $configuration = []
    ): MessageProvider {

        if (! $this->has(
            $channel,
            $provider
        )) {

            throw new InvalidArgumentException(
                "Provider [{$provider}] is not registered for channel [{$channel}]."
            );
        }


        return $this->providers[$channel][$provider]
            ->create($configuration);
    }


    /**
     * Get providers available for a channel.
     *
     * Useful for admin/settings UI.
     */
    public function forChannel(
        string $channel
    ): array {

        return array_keys(
            $this->providers[$channel] ?? []
        );
    }


    /**
     * Get all registered providers.
     */
    public function all(): array
    {
        return $this->providers;
    }


    /**
     * Remove provider.
     */
    public function forget(
        string $channel,
        string $provider
    ): void {

        unset(
            $this->providers[$channel][$provider]
        );
    }
}
