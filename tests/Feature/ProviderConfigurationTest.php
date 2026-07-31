<?php

declare(strict_types=1);

use SchoolPalm\MessageDelivery\MessageDelivery;
use SchoolPalm\MessageDelivery\Providers\ConfigurationField;
use SchoolPalm\MessageDelivery\Providers\ProviderDefinition;
use SchoolPalm\MessageDelivery\Providers\Sms\AfricasTalking\AfricasTalkingDefinition;
use SchoolPalm\MessageDelivery\Providers\Sms\EgoSms\EgoSmsDefinition;
use SchoolPalm\MessageDelivery\Providers\Sms\Twilio\TwilioDefinition;
use SchoolPalm\MessageDelivery\Registry\DefinitionRegistry;

/*
|--------------------------------------------------------------------------
| ConfigurationField Creation
|--------------------------------------------------------------------------
*/

it('creates configuration field from string with defaults', function (): void {
    $field = ConfigurationField::fromString('sid');

    expect($field)->toBeInstanceOf(ConfigurationField::class)
        ->and($field->name())->toBe('sid')
        ->and($field->label())->toBe('Sid')
        ->and($field->type())->toBe('text')
        ->and($field->required())->toBeTrue()
        ->and($field->placeholder())->toBeNull()
        ->and($field->description())->toBeNull()
        ->and($field->default())->toBeNull()
        ->and($field->options())->toBe([])
        ->and($field->secret())->toBeFalse();
});

it('detects sensitive string fields automatically', function (): void {
    expect(ConfigurationField::fromString('password')->secret())->toBeTrue();
    expect(ConfigurationField::fromString('api_token')->secret())->toBeTrue();
    expect(ConfigurationField::fromString('secret_key')->secret())->toBeTrue();
    expect(ConfigurationField::fromString('api_key')->secret())->toBeTrue();
    expect(ConfigurationField::fromString('sid')->secret())->toBeFalse();
    expect(ConfigurationField::fromString('sender_id')->secret())->toBeFalse();
});

it('creates configuration field from array', function (): void {
    $field = ConfigurationField::fromArray([
        'name' => 'mailer',
        'type' => 'select',
        'required' => true,
        'options' => ['array', 'ses', 'mailgun'],
        'description' => 'The Laravel mailer to use',
    ]);

    expect($field)->toBeInstanceOf(ConfigurationField::class)
        ->and($field->name())->toBe('mailer')
        ->and($field->type())->toBe('select')
        ->and($field->required())->toBeTrue()
        ->and($field->options())->toBe(['array', 'ses', 'mailgun'])
        ->and($field->description())->toBe('The Laravel mailer to use')
        ->and($field->secret())->toBeFalse();
});

it('creates configuration field with overridden label', function (): void {
    $field = ConfigurationField::fromArray([
        'name' => 'api_key',
        'label' => 'API Key',
    ]);

    expect($field->label())->toBe('API Key');
});

it('generates labels from field names', function (): void {
    expect(ConfigurationField::fromString('sender_id')->label())->toBe('Sender Id');
    expect(ConfigurationField::fromString('api_url')->label())->toBe('Api Url');
    expect(ConfigurationField::fromString('username')->label())->toBe('Username');
});

it('returns field to array format', function (): void {
    $field = ConfigurationField::fromString('api_key');
    $array = $field->toArray();

    expect($array)->toBe([
        'name' => 'api_key',
        'label' => 'Api Key',
        'type' => 'text',
        'required' => true,
        'placeholder' => null,
        'description' => null,
        'default' => null,
        'options' => [],
        'secret' => true,
    ]);
});

it('configuration field is immutable', function (): void {
    $field = ConfigurationField::fromString('token');

    expect($field->name())->toBe('token');
    expect($field->secret())->toBeTrue();

    // Verify there's no way to mutate (reflection-based check)
    $reflection = new ReflectionClass($field);
    foreach ($reflection->getProperties() as $prop) {
        expect($prop->isReadOnly())->toBeTrue();
    }
});

/*
|--------------------------------------------------------------------------
| String Configuration Normalization
|--------------------------------------------------------------------------
*/

it('normalizes string configuration array in provider definition', function (): void {
    $definition = new ProviderDefinition(
        name: 'test',
        channel: 'sms',
        label: 'Test',
        configuration: ['api_key', 'password', 'sender_id'],
    );

    $fields = $definition->configurationFields();

    expect($fields)->toBeArray()
        ->and($fields)->toHaveCount(3);

    foreach ($fields as $field) {
        expect($field)->toBeInstanceOf(ConfigurationField::class);
    }

    expect($fields[0]->name())->toBe('api_key')
        ->and($fields[0]->secret())->toBeTrue();

    expect($fields[1]->name())->toBe('password')
        ->and($fields[1]->secret())->toBeTrue();

    expect($fields[2]->name())->toBe('sender_id')
        ->and($fields[2]->secret())->toBeFalse();
});

it('normalizes mixed configuration array', function (): void {
    $definition = new ProviderDefinition(
        name: 'test',
        channel: 'sms',
        label: 'Test',
        configuration: [
            'api_key',
            ConfigurationField::fromArray([
                'name' => 'mailer',
                'type' => 'select',
                'options' => ['array', 'ses'],
            ]),
            ['name' => 'timeout', 'type' => 'number', 'default' => 30],
        ],
    );

    $fields = $definition->configurationFields();

    expect($fields)->toHaveCount(3);
    expect($fields[0]->name())->toBe('api_key');
    expect($fields[1]->name())->toBe('mailer');
    expect($fields[1]->type())->toBe('select');
    expect($fields[2]->name())->toBe('timeout');
    expect($fields[2]->type())->toBe('number');
    expect($fields[2]->default())->toBe(30);
});

it('normalizes empty configuration to empty array', function (): void {
    $definition = new ProviderDefinition(
        name: 'empty',
        channel: 'sms',
        label: 'Empty',
        configuration: [],
    );

    expect($definition->configurationFields())->toBe([]);
});

/*
|--------------------------------------------------------------------------
| toConfigurationArray()
|--------------------------------------------------------------------------
*/

it('returns configuration fields as array for ui rendering', function (): void {
    $definition = new ProviderDefinition(
        name: 'test',
        channel: 'sms',
        label: 'Test',
        configuration: ['api_key'],
    );

    $configArray = $definition->toConfigurationArray();

    expect($configArray)->toBeArray()
        ->and($configArray)->toHaveCount(1);

    expect($configArray[0])->toBe([
        'name' => 'api_key',
        'label' => 'Api Key',
        'type' => 'text',
        'required' => true,
        'placeholder' => null,
        'description' => null,
        'default' => null,
        'options' => [],
        'secret' => true,
    ]);
});

it('toArray includes normalized configuration', function (): void {
    $definition = new ProviderDefinition(
        name: 'test-provider',
        channel: 'sms',
        label: 'Test Provider',
        configuration: ['sid', 'token'],
        capabilities: ['unicode', 'delivery_status'],
    );

    $array = $definition->toArray();

    expect($array['name'])->toBe('test-provider')
        ->and($array['channel'])->toBe('sms')
        ->and($array['label'])->toBe('Test Provider')
        ->and($array['configuration'])->toBeArray()
        ->and($array['configuration'])->toHaveCount(2)
        ->and($array['configuration'][0]['name'])->toBe('sid')
        ->and($array['configuration'][1]['name'])->toBe('token')
        ->and($array['configuration'][0]['secret'])->toBeFalse()
        ->and($array['configuration'][1]['secret'])->toBeTrue()
        ->and($array['capabilities'])->toBe(['unicode', 'delivery_status']);
});

/*
|--------------------------------------------------------------------------
| Existing Definition Compatibility
|--------------------------------------------------------------------------
*/

it('egosms definition returns configuration fields', function (): void {
    $definition = EgoSmsDefinition::create();
    $fields = $definition->configurationFields();

    expect($fields)->toHaveCount(4);

    $names = array_map(fn(ConfigurationField $f) => $f->name(), $fields);
    expect($names)->toBe(['api_url', 'username', 'password', 'sender_id']);

    expect($fields[0]->type())->toBe('text');
    expect($fields[1]->required())->toBeTrue();
    expect($fields[2]->secret())->toBeTrue();
    expect($fields[3]->secret())->toBeFalse();
});

it('twilio definition returns configuration fields', function (): void {
    $definition = TwilioDefinition::make();
    $fields = $definition->configurationFields();

    expect($fields)->toHaveCount(3);

    $names = array_map(fn(ConfigurationField $f) => $f->name(), $fields);
    expect($names)->toBe(['sid', 'token', 'from']);

    expect($fields[0]->label())->toBe('Sid');
    expect($fields[1]->label())->toBe('Token');
    expect($fields[2]->label())->toBe('From');

    expect($fields[1]->secret())->toBeTrue(); // 'token' is sensitive
    expect($fields[2]->secret())->toBeFalse();
});

it('africas talking definition returns configuration fields', function (): void {
    $definition = AfricasTalkingDefinition::make();
    $fields = $definition->configurationFields();

    expect($fields)->toHaveCount(3);

    $names = array_map(fn(ConfigurationField $f) => $f->name(), $fields);
    expect($names)->toBe(['api_key', 'username', 'sender_id']);

    expect($fields[0]->secret())->toBeTrue();
    expect($fields[1]->secret())->toBeFalse();
    expect($fields[2]->secret())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Backward Compatibility
|--------------------------------------------------------------------------
*/

it('configuration property retains original raw values', function (): void {
    $raw = ['sid', 'token', 'from'];
    $definition = new ProviderDefinition(
        name: 'twilio-sms',
        channel: 'sms',
        label: 'Twilio SMS',
        configuration: $raw,
    );

    // The public readonly $configuration still has the original raw input
    expect($definition->configuration)->toBe($raw);
});

it('configurationFields works with new config format', function (): void {
    $definition = TwilioDefinition::make();

    // This must still work — returns objects now instead of strings
    $fields = $definition->configurationFields();

    expect($fields)->toBeArray();
    expect($fields[0])->toBeInstanceOf(ConfigurationField::class);

    // Existing code that iterates names should still work via ->name()
    $names = [];
    foreach ($fields as $field) {
        $names[] = $field->name();
    }
    expect($names)->toBe(['sid', 'token', 'from']);
});

it('supports method works unchanged', function (): void {
    $definition = AfricasTalkingDefinition::make();

    expect($definition->supports('unicode'))->toBeTrue();
    expect($definition->supports('bulk'))->toBeTrue();
    expect($definition->supports('delivery_status'))->toBeTrue();
    expect($definition->supports('html'))->toBeFalse();
});

it('name channel label methods work unchanged', function (): void {
    $definition = TwilioDefinition::make();

    expect($definition->name())->toBe('twilio-sms');
    expect($definition->channel())->toBe('sms');
    expect($definition->label())->toBe('Twilio SMS');
});

/*
|--------------------------------------------------------------------------
| DefinitionRegistry
|--------------------------------------------------------------------------
*/

it('definition registry registers and retrieves definitions', function (): void {
    $registry = new DefinitionRegistry();
    $definition = EgoSmsDefinition::create();

    $registry->register($definition);

    expect($registry->has('egosms'))->toBeTrue();
    expect($registry->has('nonexistent'))->toBeFalse();

    $retrieved = $registry->get('egosms');
    expect($retrieved)->toBe($definition);
    expect($retrieved->name())->toBe('egosms');
});

it('definition registry throws for unregistered definition', function (): void {
    $registry = new DefinitionRegistry();

    expect(fn() => $registry->get('nonexistent'))
        ->toThrow(\InvalidArgumentException::class, 'nonexistent');
});

it('definition registry throws on duplicate registration', function (): void {
    $registry = new DefinitionRegistry();
    $definition = TwilioDefinition::make();

    $registry->register($definition);

    expect(fn() => $registry->register(TwilioDefinition::make()))
        ->toThrow(\InvalidArgumentException::class, 'twilio-sms');
});

it('definition registry retrieves all definitions', function (): void {
    $registry = new DefinitionRegistry();

    $registry->register(EgoSmsDefinition::create());
    $registry->register(TwilioDefinition::make());

    $all = $registry->all();

    expect($all)->toHaveCount(2);
    expect($all)->toHaveKey('egosms');
    expect($all)->toHaveKey('twilio-sms');
});

it('definition registry filters definitions by channel', function (): void {
    $registry = new DefinitionRegistry();

    $registry->register(EgoSmsDefinition::create());
    $registry->register(TwilioDefinition::make());
    $registry->register(AfricasTalkingDefinition::make());

    $smsDefinitions = $registry->forChannel('sms');

    expect($smsDefinitions)->toHaveCount(3);
    expect($smsDefinitions)->toHaveKey('egosms');
    expect($smsDefinitions)->toHaveKey('twilio-sms');
    expect($smsDefinitions)->toHaveKey('africas-talking');
});

it('definition registry supports forget and clear', function (): void {
    $registry = new DefinitionRegistry();

    $registry->register(EgoSmsDefinition::create());
    $registry->register(TwilioDefinition::make());
    expect($registry->all())->toHaveCount(2);

    $registry->forget('egosms');
    expect($registry->has('egosms'))->toBeFalse();
    expect($registry->all())->toHaveCount(1);

    $registry->clear();
    expect($registry->all())->toBe([]);
});

/*
|--------------------------------------------------------------------------
| MessageDelivery Facade — Definition Lookup
|--------------------------------------------------------------------------
*/

it('resolves twilio definition via facade', function (): void {
    $definition = MessageDelivery::definition('twilio-sms');

    expect($definition)->toBeInstanceOf(ProviderDefinition::class)
        ->and($definition->name())->toBe('twilio-sms')
        ->and($definition->channel())->toBe('sms')
        ->and($definition->label())->toBe('Twilio SMS');

    $fields = $definition->configurationFields();
    expect($fields)->toHaveCount(3);
    expect($fields[0]->name())->toBe('sid');
});

it('resolves egosms definition via facade', function (): void {
    $definition = MessageDelivery::definition('egosms');

    expect($definition)->toBeInstanceOf(ProviderDefinition::class)
        ->and($definition->name())->toBe('egosms');

    $fields = $definition->configurationFields();
    expect($fields)->toHaveCount(4);
    expect($fields[0]->name())->toBe('api_url');
});

it('resolves africas talking definition via facade', function (): void {
    $definition = MessageDelivery::definition('africas-talking');

    expect($definition)->toBeInstanceOf(ProviderDefinition::class)
        ->and($definition->name())->toBe('africas-talking');
});

it('returns all definitions via facade', function (): void {
    $definitions = MessageDelivery::definitions();

    expect($definitions)->toHaveKey('twilio-sms');
    expect($definitions)->toHaveKey('egosms');
    expect($definitions)->toHaveKey('africas-talking');
    expect($definitions)->toHaveKey('laravel-mail');
});

it('returns definitions filtered by channel via facade', function (): void {
    $smsProviders = MessageDelivery::providers('sms');

    expect($smsProviders)->toHaveCount(3);
    expect($smsProviders)->toHaveKey('egosms');
    expect($smsProviders)->toHaveKey('twilio-sms');
    expect($smsProviders)->toHaveKey('africas-talking');

    $emailProviders = MessageDelivery::providers('email');
    expect($emailProviders)->toHaveCount(1);
    expect($emailProviders)->toHaveKey('laravel-mail');
});

it('returns empty for unregistered channel', function (): void {
    $providers = MessageDelivery::providers('push');

    expect($providers)->toBeArray()->toBeEmpty();
});

it('throws for nonexistent definition', function (): void {
    expect(fn() => MessageDelivery::definition('nonexistent'))
        ->toThrow(\InvalidArgumentException::class);
});

/*
|--------------------------------------------------------------------------
| Configuration via definition chaining
|--------------------------------------------------------------------------
*/

it('supports method chaining for configuration fields', function (): void {
    $fields = MessageDelivery::definition('twilio-sms')
        ->configurationFields();

    expect($fields)->toHaveCount(3);
    expect($fields[0])->toBeInstanceOf(ConfigurationField::class);
});

it('supports method chaining for configuration array', function (): void {
    $configArray = MessageDelivery::definition('egosms')
        ->toConfigurationArray();

    expect($configArray)->toHaveCount(4);
    expect($configArray[0]['name'])->toBe('api_url');
    expect($configArray[0]['type'])->toBe('text');
    expect($configArray[0]['required'])->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Delivery behaviour unchanged
|--------------------------------------------------------------------------
*/

it('definition api does not affect sms delivery', function (): void {
    // Just verify that calling the definition API doesn't break anything
    $definition = MessageDelivery::definition('egosms');
    $fields = $definition->configurationFields();

    // Still able to access all provider metadata
    expect($definition->supports('unicode'))->toBeTrue();
    expect($definition->supports('delivery_reports'))->toBeTrue();

    // Capabilities unchanged
    expect($definition->toArray()['capabilities'])->toContain('unicode');
});
