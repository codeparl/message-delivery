# Provider Configuration Fields

This guide explains how to use the provider configuration field APIs to initialize provider settings in your application's own storage (e.g. a tenant settings scope).

> **Important:** This package is storage-agnostic. It only **returns** the configuration fields for each provider. It does **not** persist anything — your application decides where and how to save them.

---

## Field Schema

Every provider field is returned as a plain array with the canonical keys:

| Key           | Type    | Description                                        |
| ------------- | ------- | -------------------------------------------------- |
| `name`        | string  | Field identifier (e.g. `api_key`)                  |
| `label`       | string  | Human-readable label                               |
| `type`        | string  | `text`, `password`, `select`, `boolean`, `number`… |
| `required`    | bool    | Whether the field is mandatory                     |
| `placeholder` | ?string | Input placeholder                                  |
| `description` | ?string | Help text                                          |
| `default`     | mixed   | Default value                                      |
| `options`     | array   | Allowed values for `select` fields                 |
| `secret`      | bool    | Whether the field contains sensitive data          |

---

## Accessing Fields

All APIs are accessible via the `MessageDelivery` facade.

### A Single Provider

```php
use SchoolPalm\MessageDelivery\Facades\MessageDelivery;

// Fields as arrays
$fields = MessageDelivery::providerConfigurationFields('twilio-sms');

// Fields as ConfigurationField objects
$fieldObjects = MessageDelivery::providerFieldObjects('twilio-sms');
```

### All Providers

```php
// All providers keyed by name → fields as arrays
$all = MessageDelivery::allProviderConfigurationFields();
```

### By Channel

```php
// Providers for a channel → fields as arrays
$sms = MessageDelivery::providerConfigurationFieldsForChannel('sms');
$email = MessageDelivery::providerConfigurationFieldsForChannel('email');
$whatsapp = MessageDelivery::providerConfigurationFieldsForChannel('whatsapp');
$push = MessageDelivery::providerConfigurationFieldsForChannel('push');
```

### A Single Field

```php
// Look up a single field (returns null if not found)
$token = MessageDelivery::providerConfigurationField('twilio-sms', 'token');
```

---

## Initializing Settings

### Flat Defaults (DB Seeding)

`providerSeedSettings()` returns a flat `provider.field => default` map — useful for seeding a settings table:

```php
$seed = MessageDelivery::providerSeedSettings();

// Example output (excerpt):
// [
//   'laravel-mail.mailer' => null,
//   'twilio-sms.sid' => null,
//   'twilio-sms.token' => null,
//   'twilio-sms.from' => null,
//   'meta-whatsapp.version' => 'v23.0',
//   'meta-whatsapp.verify_ssl' => true,
//   ...
// ]
```

### Scoped Settings (Secured vs Secrets)

`providerScopedSettings()` separates fields into two scopes so you can store credentials separately (e.g. encrypted):

```php
$scoped = MessageDelivery::providerScopedSettings();

// $scoped['secured'] → non-secret fields (e.g. 'from', 'sender_id')
$secured = $scoped['secured'];

// $scoped['secrets'] → secret fields (e.g. 'token', 'password')
$secrets = $scoped['secrets'];
```

Store `$secrets` in an encrypted store and `$secured` in a normal settings table.

### Example: Seeding a Tenant Settings Scope

```php
use SchoolPalm\MessageDelivery\Facades\MessageDelivery;

// When enabling Twilio SMS for a tenant
$fields = MessageDelivery::providerConfigurationFields('twilio-sms');

$settings = [
    'provider' => 'twilio-sms',
    'channel'  => 'sms',
    'fields'   => $fields,
    'defaults' => MessageDelivery::providerSeedSettings(),
];

// Persist $settings into your own settings scope/storage here.
```

---

## Registered Providers & Fields

| Provider                 | Channel    | Fields                                                     |
| ------------------------ | ---------- | ---------------------------------------------------------- |
| `laravel-mail`           | `email`    | `mailer`                                                   |
| `egosms`                 | `sms`      | `api_url`, `username`, `password`, `sender_id`             |
| `twilio-sms`             | `sms`      | `sid`, `token`, `from`                                     |
| `africas-talking`        | `sms`      | `api_key`, `username`, `sender_id`                         |
| `meta-whatsapp`          | `whatsapp` | `access_token`, `phone_number_id`, `version`, `verify_ssl` |
| `twilio-whatsapp`        | `whatsapp` | `sid`, `token`, `from`                                     |
| `firebase-push`          | `push`     | `credentials_json`, `project_id`, `server_key`             |
| `database-notifications` | `in_app`   | `default_notifiable`                                       |

---

## Under the Hood

These APIs delegate to the `ProviderConfigurationFields` helper, which reads from the `DefinitionRegistry`:

```php
use SchoolPalm\MessageDelivery\Providers\ProviderConfigurationFields;

$helper = ProviderConfigurationFields::make();

// Equivalent to the facade:
$fields = $helper->provider('twilio-sms');
$all = $helper->all();
$sms = $helper->forChannel('sms');
$field = $helper->field('twilio-sms', 'token');
$seed = $helper->seedSettings();
$scoped = $helper->scopedSettings();
