<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers;

/**
 * Immutable value object describing a single provider configuration field.
 *
 * This is the canonical representation used by ProviderDefinition to
 * expose configuration schema to administration interfaces. Providers
 * supply string field names in their definitions, which are normalized
 * into ConfigurationField objects with sensible defaults.
 *
 * The object is immutable: every "with*" / fluent mutator returns a NEW
 * instance with the modified value, leaving the original untouched.
 *
 * Example fluent usage:
 *
 *   ConfigurationField::string('access_token')
 *       ->label('Access Token')
 *       ->required()
 *       ->secret();
 *
 * Example string input:
 *
 *   'sid'
 *
 * becomes:
 *
 *   ConfigurationField(
 *       name: 'sid',
 *       label: 'Sid',
 *       type: 'text',
 *       required: true,
 *   )
 *
 * @see ProviderDefinition::configurationFields()
 */
final class ConfigurationField
{
    /**
     * Sentinel used to detect when an optional mutator argument was omitted.
     */
    private const UNSET = "\0__unset__\0";

    /**
     * @param string      $name        Field identifier (e.g. 'api_key')
     * @param string      $label       Human-readable label (e.g. 'API Key')
     * @param string      $type        Input type: text, password, select, boolean, number, email, url
     * @param bool        $required    Whether the field is mandatory
     * @param string|null $placeholder Placeholder text for UI input
     * @param string|null $description Help text describing the field
     * @param mixed|null  $default     Default value
     * @param array       $options     Allowed values for select-type fields
     * @param bool        $secret      Whether the field contains sensitive data (e.g. tokens, passwords)
     */
    public function __construct(
        private readonly string $name,
        private readonly string $label = '',
        private readonly string $type = 'text',
        private readonly bool $required = true,
        private readonly ?string $placeholder = null,
        private readonly ?string $description = null,
        private readonly mixed $default = null,
        private readonly array $options = [],
        private readonly bool $secret = false,
    ) {}

    /**
     * Create a text-type ConfigurationField.
     *
     * @param string $name Field identifier
     * @return self
     */
    public static function string(string $name): self
    {
        return new self(
            name: $name,
            label: self::generateLabel($name),
            type: 'text',
            required: true,
            secret: self::isSensitive($name),
        );
    }

    /**
     * Create a boolean-type ConfigurationField.
     *
     * @param string $name Field identifier
     * @return self
     */
    public static function boolean(string $name): self
    {
        return new self(
            name: $name,
            label: self::generateLabel($name),
            type: 'boolean',
            required: true,
            default: false,
        );
    }

    /**
     * Create a ConfigurationField from a plain string field name.
     *
     * Generates sensible defaults:
     * - label: Title-cased version of name
     * - type: 'text'
     * - required: true
     * - secret: true if name contains 'password', 'token', 'secret', 'key'
     */
    public static function fromString(string $name): self
    {
        return new self(
            name: $name,
            label: self::generateLabel($name),
            type: 'text',
            required: true,
            secret: self::isSensitive($name),
        );
    }

    /**
     * Create a ConfigurationField from an associative array.
     *
     * Supports the same keys as the constructor. Missing keys
     * fall back to defaults derived from the 'name' field.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $name = $data['name'] ?? '';

        return new self(
            name: $name,
            label: $data['label'] ?? self::generateLabel($name),
            type: $data['type'] ?? 'text',
            required: $data['required'] ?? true,
            placeholder: $data['placeholder'] ?? null,
            description: $data['description'] ?? null,
            default: $data['default'] ?? null,
            options: $data['options'] ?? [],
            secret: $data['secret'] ?? self::isSensitive($name),
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Fluent Mutators
    |--------------------------------------------------------------------------
    |
    | Each mutator returns a NEW instance, preserving immutability.
    |
    */

    /**
     * Return a copy with a different label.
     *
     * When called with no argument, acts as the label getter.
     *
     * @return string|self
     */
    public function label(?string $label = null): string|self
    {
        if ($label === null) {
            return $this->label;
        }

        return $this->copyWith(['label' => $label]);
    }

    /**
     * Return a copy with a different type.
     *
     * When called with no argument, acts as the type getter.
     *
     * @return string|self
     */
    public function type(?string $type = null): string|self
    {
        if ($type === null) {
            return $this->type;
        }

        return $this->copyWith(['type' => $type]);
    }

    /**
     * Return a copy with a different required flag.
     *
     * When called with no argument, acts as the required getter.
     *
     * @return bool|self
     */
    public function required(?bool $required = null): bool|self
    {
        if ($required === null) {
            return $this->required;
        }

        return $this->copyWith(['required' => $required]);
    }

    /**
     * Return a copy with a different secret flag.
     *
     * When called with no argument, acts as the secret getter.
     *
     * @return bool|self
     */
    public function secret(?bool $secret = null): bool|self
    {
        if ($secret === null) {
            return $this->secret;
        }

        return $this->copyWith(['secret' => $secret]);
    }

    /**
     * Return a copy with a different placeholder.
     *
     * When called with no argument, acts as the placeholder getter.
     *
     * @return string|null|self
     */
    public function placeholder(string|null $placeholder = self::UNSET): string|null|self
    {
        if ($placeholder === self::UNSET) {
            return $this->placeholder;
        }

        return $this->copyWith(['placeholder' => $placeholder]);
    }

    /**
     * Return a copy with a different description.
     *
     * When called with no argument, acts as the description getter.
     *
     * @return string|null|self
     */
    public function description(string|null $description = self::UNSET): string|null|self
    {
        if ($description === self::UNSET) {
            return $this->description;
        }

        return $this->copyWith(['description' => $description]);
    }

    /**
     * Return a copy with a different default value.
     *
     * When called with no argument, acts as the default getter.
     */
    public function default(mixed $default = self::UNSET)
    {
        if ($default === self::UNSET) {
            return $this->default;
        }

        return $this->copyWith(['default' => $default]);
    }

    /**
     * Return a copy with different options.
     *
     * When called with no argument, acts as the options getter.
     *
     * @return array|self
     */
    public function options(?array $options = null): array|self
    {
        if ($options === null) {
            return $this->options;
        }

        return $this->copyWith(['options' => $options]);
    }

    /**
     * Return a copy with a different label.
     *
     * @deprecated Use label() fluent mutator instead.
     */
    public function withLabel(string $label): self
    {
        return $this->copyWith(['label' => $label]);
    }

    /**
     * Return a copy with a different type.
     *
     * @deprecated Use type() fluent mutator instead.
     */
    public function withType(string $type): self
    {
        return $this->copyWith(['type' => $type]);
    }

    /**
     * Return a copy with a different required flag.
     *
     * @deprecated Use required() fluent mutator instead.
     */
    public function withRequired(bool $required = true): self
    {
        return $this->copyWith(['required' => $required]);
    }

    /**
     * Return a copy with a different secret flag.
     *
     * @deprecated Use secret() fluent mutator instead.
     */
    public function withSecret(bool $secret = true): self
    {
        return $this->copyWith(['secret' => $secret]);
    }

    /**
     * Return a copy with a different placeholder.
     *
     * @deprecated Use placeholder() fluent mutator instead.
     */
    public function withPlaceholder(?string $placeholder): self
    {
        return $this->copyWith(['placeholder' => $placeholder]);
    }

    /**
     * Return a copy with a different description.
     *
     * @deprecated Use description() fluent mutator instead.
     */
    public function withDescription(?string $description): self
    {
        return $this->copyWith(['description' => $description]);
    }

    /**
     * Return a copy with a different default value.
     *
     * @deprecated Use default() fluent mutator instead.
     */
    public function withDefault(mixed $default): self
    {
        return $this->copyWith(['default' => $default]);
    }

    /**
     * Return a copy with different options.
     *
     * @deprecated Use options() fluent mutator instead.
     */
    public function withOptions(array $options): self
    {
        return $this->copyWith(['options' => $options]);
    }

    /**
     * Get the field identifier.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Convert to array for UI rendering.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type,
            'required' => $this->required,
            'placeholder' => $this->placeholder,
            'description' => $this->description,
            'default' => $this->default,
            'options' => $this->options,
            'secret' => $this->secret,
        ];
    }

    /**
     * Create a copy with the given overrides applied.
     *
     * @param  array<string, mixed>  $overrides
     * @return self
     */
    private function copyWith(array $overrides): self
    {
        return new self(
            name: $overrides['name'] ?? $this->name,
            label: $overrides['label'] ?? $this->label,
            type: $overrides['type'] ?? $this->type,
            required: $overrides['required'] ?? $this->required,
            placeholder: $overrides['placeholder'] ?? $this->placeholder,
            description: $overrides['description'] ?? $this->description,
            default: array_key_exists('default', $overrides)
                ? $overrides['default']
                : $this->default,
            options: $overrides['options'] ?? $this->options,
            secret: $overrides['secret'] ?? $this->secret,
        );
    }

    /**
     * Generate a human-readable label from a field name.
     *
     * Examples:
     *   'api_url'    => 'Api Url'
     *   'sender_id'  => 'Sender Id'
     *   'apiKey'     => 'ApiKey'
     */
    private static function generateLabel(string $name): string
    {
        // Split on underscores, hyphens, and camelCase boundaries
        $parts = preg_split('/[_-]|(?<=[a-z])(?=[A-Z])/', $name);

        $label = implode(' ', array_map(
            fn(string $part): string => ucfirst($part),
            array_filter($parts, fn($v) => $v !== '')
        ));

        return $label !== '' ? $label : ucfirst($name);
    }

    /**
     * Determine whether a field name indicates sensitive data.
     */
    private static function isSensitive(string $name): bool
    {
        $lower = strtolower($name);

        return str_contains($lower, 'password')
            || str_contains($lower, 'token')
            || str_contains($lower, 'secret')
            || str_contains($lower, 'key');
    }
}
