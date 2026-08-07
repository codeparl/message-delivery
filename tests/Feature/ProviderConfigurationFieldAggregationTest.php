<?php

declare(strict_types=1);

use SchoolPalm\MessageDelivery\MessageDelivery;
use SchoolPalm\MessageDelivery\Providers\ConfigurationField;
use SchoolPalm\MessageDelivery\Providers\ProviderConfigurationFields;

/*
|--------------------------------------------------------------------------
| provider() — single provider fields as arrays
|--------------------------------------------------------------------------
*/

it('returns egosms configuration fields as arrays', function (): void {
    $fields = ProviderConfigurationFields::make()->provider('egosms');

    expect($fields)->toBeArray()
        ->and($fields)->toHaveCount(4);

    $names = array_column($fields, 'name');
    expect($names)->toBe(['api_url', 'username', 'password', 'sender_id']);

    foreach ($fields as $field) {
        expect($field)->toBeArray();
    }
});

it('returns twilio sms configuration fields as arrays', function (): void {
    $fields = ProviderConfigurationFields::make()->provider('twilio-sms');

    expect($fields)->toBeArray()
        ->and($fields)->toHaveCount(3)
        ->and(array_column($fields, 'name'))->toBe(['sid', 'token', 'from']);
});

it('returns africas talking configuration fields as arrays', function (): void {
    $fields = ProviderConfigurationFields::make()->provider('africas-talking');

    expect($fields)->toBeArray()
        ->and($fields)->toHaveCount(3)
        ->and(array_column($fields, 'name'))->toBe(['api_key', 'username', 'sender_id']);
});

it('returns laravel mail configuration fields as arrays', function (): void {
    $fields = ProviderConfigurationFields::make()->provider('laravel-mail');

    expect($fields)->toBeArray()
        ->and($fields)->toHaveCount(1)
        ->and(array_column($fields, 'name'))->toBe(['mailer']);
});

it('returns firebase push configuration fields as arrays', function (): void {
    $fields = ProviderConfigurationFields::make()->provider('firebase-push');

    expect($fields)->toBeArray()
        ->and($fields)->toHaveCount(3)
        ->and(array_column($fields, 'name'))
        ->toBe(['credentials_json', 'project_id', 'server_key']);
});

it('returns twilio whatsapp configuration fields as arrays', function (): void {
    $fields = ProviderConfigurationFields::make()->provider('twilio-whatsapp');

    expect($fields)->toBeArray()
        ->and($fields)->toHaveCount(3)
        ->and(array_column($fields, 'name'))->toBe(['sid', 'token', 'from']);
});

it('returns meta whatsapp configuration fields as arrays', function (): void {
    $fields = ProviderConfigurationFields::make()->provider('meta-whatsapp');

    expect($fields)->toBeArray()
        ->and($fields)->toHaveCount(4)
        ->and(array_column($fields, 'name'))
        ->toBe(['access_token', 'phone_number_id', 'version', 'verify_ssl']);
});

it('returns database notifications configuration fields as arrays', function (): void {
    $fields = ProviderConfigurationFields::make()->provider('database-notifications');

    expect($fields)->toBeArray()
        ->and($fields)->toHaveCount(1)
        ->and(array_column($fields, 'name'))->toBe(['default_notifiable']);
});

/*
|--------------------------------------------------------------------------
| Structure of each field array
|--------------------------------------------------------------------------
*/

it('every field array contains the canonical configuration keys', function (): void {
    $all = ProviderConfigurationFields::make()->all();

    $expectedKeys = ['name', 'label', 'type', 'required', 'placeholder', 'description', 'default', 'options', 'secret'];

    foreach ($all as $provider => $fields) {
        foreach ($fields as $field) {
            expect($field)->toBeArray();
            expect(array_keys($field))->toBe($expectedKeys);
            expect($field['name'])->toBeString();
            expect($field['label'])->toBeString();
            expect($field['type'])->toBeString();
            expect($field['required'])->toBeBool();
            expect($field['secret'])->toBeBool();
        }
    }
});

/*
|--------------------------------------------------------------------------
| all() — every provider
|--------------------------------------------------------------------------
*/

it('returns all registered providers with fields as arrays', function (): void {
    $all = ProviderConfigurationFields::make()->all();

    expect($all)->toHaveKeys([
        'laravel-mail',
        'egosms',
        'twilio-sms',
        'africas-talking',
        'meta-whatsapp',
        'twilio-whatsapp',
        'firebase-push',
        'database-notifications',
    ]);

    foreach ($all as $fields) {
        expect($fields)->toBeArray();
    }
});

/*
|--------------------------------------------------------------------------
| forChannel()
|--------------------------------------------------------------------------
*/

it('filters providers by channel', function (): void {
    $helper = ProviderConfigurationFields::make();

    $sms = $helper->forChannel('sms');
    expect(array_keys($sms))->toBe(['egosms', 'twilio-sms', 'africas-talking']);

    $email = $helper->forChannel('email');
    expect(array_keys($email))->toBe(['laravel-mail']);

    $whatsapp = $helper->forChannel('whatsapp');
    expect(array_keys($whatsapp))->toBe(['meta-whatsapp', 'twilio-whatsapp']);

    expect($helper->forChannel('nonexistent'))->toBe([]);
});

/*
|--------------------------------------------------------------------------
| providerFields() — ConfigurationField objects
|--------------------------------------------------------------------------
*/

it('returns provider fields as ConfigurationField objects', function (): void {
    $fields = ProviderConfigurationFields::make()->providerFields('twilio-sms');

    expect($fields)->toHaveCount(3);

    foreach ($fields as $field) {
        expect($field)->toBeInstanceOf(ConfigurationField::class);
    }
});

/*
|--------------------------------------------------------------------------
| field() — lookup a single field
|--------------------------------------------------------------------------
*/

it('looks up a single field by provider and field name', function (): void {
    $helper = ProviderConfigurationFields::make();

    $field = $helper->field('twilio-sms', 'token');

    expect($field)->toBeArray()
        ->and($field['name'])->toBe('token')
        ->and($field['secret'])->toBeTrue();

    expect($helper->field('twilio-sms', 'nonexistent'))->toBeNull();
});

/*
|--------------------------------------------------------------------------
| seedSettings() — DB seeding defaults
|--------------------------------------------------------------------------
*/

it('builds a flat settings map for db seeding', function (): void {
    $settings = ProviderConfigurationFields::make()->seedSettings();

    // Every provider field is present under provider.field
    expect($settings)->toHaveKey('egosms.api_url');
    expect($settings)->toHaveKey('egosms.password');
    expect($settings)->toHaveKey('twilio-sms.sid');
    expect($settings)->toHaveKey('twilio-sms.token');
    expect($settings)->toHaveKey('africas-talking.api_key');
    expect($settings)->toHaveKey('laravel-mail.mailer');
    expect($settings)->toHaveKey('firebase-push.credentials_json');
    expect($settings)->toHaveKey('meta-whatsapp.access_token');
    expect($settings)->toHaveKey('database-notifications.default_notifiable');

    // Defaults are applied where defined
    expect($settings['meta-whatsapp.version'])->toBe('v23.0');
    expect($settings['meta-whatsapp.verify_ssl'])->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| scopedSettings() — secured vs secrets
|--------------------------------------------------------------------------
*/

it('separates secret fields into a secrets scope', function (): void {
    $scoped = ProviderConfigurationFields::make()->scopedSettings();

    expect($scoped)->toHaveKeys(['secured', 'secrets']);

    // Secret fields land in secrets scope
    expect($scoped['secrets']['twilio-sms']['token'])->toBeNull();
    expect($scoped['secrets']['egosms']['password'])->toBeNull();
    expect($scoped['secrets']['meta-whatsapp']['access_token'])->toBeNull();

    // Non-secret fields land in secured scope
    expect($scoped['secured']['twilio-sms']['from'])->toBeNull();
    expect($scoped['secured']['egosms']['sender_id'])->toBeNull();
    expect($scoped['secured']['meta-whatsapp']['version'])->toBe('v23.0');

    // A secret field must not appear in secured scope
    expect($scoped['secured'])->not->toHaveKey('twilio-sms.token');
});

/*
|--------------------------------------------------------------------------
| MessageDelivery::xxx() facade APIs
|--------------------------------------------------------------------------
*/

it('MessageDelivery exposes single provider configuration fields as arrays', function (): void {
    $fields = MessageDelivery::providerConfigurationFields('twilio-sms');

    expect($fields)->toBeArray()
        ->and($fields)->toHaveCount(3)
        ->and(array_column($fields, 'name'))->toBe(['sid', 'token', 'from']);
});

it('MessageDelivery exposes provider field objects', function (): void {
    $fields = MessageDelivery::providerFieldObjects('twilio-sms');

    expect($fields)->toHaveCount(3);

    foreach ($fields as $field) {
        expect($field)->toBeInstanceOf(ConfigurationField::class);
    }
});

it('MessageDelivery exposes all provider configuration fields', function (): void {
    $all = MessageDelivery::allProviderConfigurationFields();

    expect($all)->toHaveKeys([
        'laravel-mail',
        'egosms',
        'twilio-sms',
        'africas-talking',
        'meta-whatsapp',
        'twilio-whatsapp',
        'firebase-push',
        'database-notifications',
    ]);

    foreach ($all as $fields) {
        expect($fields)->toBeArray();
    }
});

it('MessageDelivery exposes provider configuration fields by channel', function (): void {
    $sms = MessageDelivery::providerConfigurationFieldsForChannel('sms');

    expect(array_keys($sms))->toBe(['egosms', 'twilio-sms', 'africas-talking']);

    $email = MessageDelivery::providerConfigurationFieldsForChannel('email');
    expect(array_keys($email))->toBe(['laravel-mail']);
});

it('MessageDelivery exposes a single provider configuration field', function (): void {
    $field = MessageDelivery::providerConfigurationField('twilio-sms', 'token');

    expect($field)->toBeArray()
        ->and($field['name'])->toBe('token')
        ->and($field['secret'])->toBeTrue();

    expect(MessageDelivery::providerConfigurationField('twilio-sms', 'nonexistent'))->toBeNull();
});

it('MessageDelivery exposes flat seed settings for db initialization', function (): void {
    $settings = MessageDelivery::providerSeedSettings();

    expect($settings)->toHaveKey('twilio-sms.sid');
    expect($settings)->toHaveKey('egosms.password');
    expect($settings['meta-whatsapp.version'])->toBe('v23.0');
});

it('MessageDelivery exposes scoped settings separating secrets', function (): void {
    $scoped = MessageDelivery::providerScopedSettings();

    expect($scoped)->toHaveKeys(['secured', 'secrets']);

    expect($scoped['secrets']['twilio-sms']['token'])->toBeNull();
    expect($scoped['secured']['twilio-sms']['from'])->toBeNull();
    expect($scoped['secured'])->not->toHaveKey('twilio-sms.token');
});
