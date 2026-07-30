<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers;

final class ProviderDefinition
{
    /**
     * Create provider definition.
     */
    public function __construct(
        public readonly string $name,

        public readonly string $channel,

        public readonly string $label,

        public readonly array $configuration = [],

        public readonly array $capabilities = [],
    ) {}


    /**
     * Get provider name.
     *
     * Example:
     *
     * egosms
     */
    public function name(): string
    {
        return $this->name;
    }


    /**
     * Get supported channel.
     *
     * Example:
     *
     * sms
     */
    public function channel(): string
    {
        return $this->channel;
    }


    /**
     * Get display label.
     */
    public function label(): string
    {
        return $this->label;
    }


    /**
     * Get required configuration fields.
     *
     * Example:
     *
     * [
     *     'api_url',
     *     'username',
     *     'password'
     * ]
     */
    public function configurationFields(): array
    {
        return $this->configuration;
    }


    /**
     * Check if provider supports capability.
     */
    public function supports(
        string $capability
    ): bool {

        return in_array(
            $capability,
            $this->capabilities,
            true
        );
    }


    /**
     * Convert definition to array.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'channel' => $this->channel,
            'label' => $this->label,
            'configuration' => $this->configuration,
            'capabilities' => $this->capabilities,
        ];
    }
}
