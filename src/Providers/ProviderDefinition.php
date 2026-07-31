<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers;

final class ProviderDefinition
{
    /**
     * Normalized configuration fields.
     *
     * @var array<ConfigurationField>
     */
    private readonly array $fields;

    /**
     * Create provider definition.
     *
     * Accepts configuration as:
     *
     * 1. Plain string array:
     *    ['sid', 'token', 'from']
     *
     * 2. Array of ConfigurationField objects:
     *    [new ConfigurationField(...), ...]
     *
     * 3. Array of associative arrays:
     *    [['name' => 'sid', 'type' => 'text', ...], ...]
     *
     * All formats are normalized to ConfigurationField objects.
     *
     * @param string                       $name
     * @param string                       $channel
     * @param string                       $label
     * @param array                        $configuration  Raw configuration schema
     * @param array                        $capabilities
     */
    public function __construct(
        public readonly string $name,

        public readonly string $channel,

        public readonly string $label,

        public readonly array $configuration = [],

        public readonly array $capabilities = [],
    ) {
        $this->fields = $this->normalizeConfiguration($configuration);
    }


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
     * Get required configuration fields as ConfigurationField objects.
     *
     * Returns an array of ConfigurationField value objects describing
     * the provider's configuration schema. This is the canonical form
     * used by administration interfaces.
     *
     * @return array<ConfigurationField>
     */
    public function configurationFields(): array
    {
        return $this->fields;
    }


    /**
     * Get configuration fields as arrays for UI rendering.
     *
     * Each field is converted to a plain array via ConfigurationField::toArray().
     *
     * @return array<int, array<string, mixed>>
     */
    public function toConfigurationArray(): array
    {
        return array_map(
            fn(ConfigurationField $field): array => $field->toArray(),
            $this->fields
        );
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
     *
     * The 'configuration' key contains the normalized
     * array representation of each field.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'channel' => $this->channel,
            'label' => $this->label,
            'configuration' => $this->toConfigurationArray(),
            'capabilities' => $this->capabilities,
        ];
    }


    /**
     * Normalize mixed configuration input to ConfigurationField array.
     *
     * @param  array  $configuration
     * @return array<ConfigurationField>
     */
    private function normalizeConfiguration(array $configuration): array
    {
        $fields = [];

        foreach ($configuration as $item) {
            if ($item instanceof ConfigurationField) {
                $fields[] = $item;
            } elseif (is_string($item)) {
                $fields[] = ConfigurationField::fromString($item);
            } elseif (is_array($item)) {
                $fields[] = ConfigurationField::fromArray($item);
            }
        }

        return $fields;
    }
}
