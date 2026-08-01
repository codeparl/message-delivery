# SchoolPalm Message Delivery

A Laravel package for multi-channel message delivery supporting **Email**, **SMS**, **WhatsApp**, **Push Notifications**, and **In-App Notifications** — with a **Notification Engine** that orchestrates the entire flow through resolver interfaces.

---

## Table of Contents

- [SchoolPalm Message Delivery](#schoolpalm-message-delivery)
  - [Table of Contents](#table-of-contents)
  - [Architecture](#architecture)
  - [Installation](#installation)
    - [Aliases](#aliases)
  - [Configuration](#configuration)
    - [Configuration Reference](#configuration-reference)
  - [Message Delivery](#message-delivery)
    - [Single Channel](#single-channel)
    - [Multi-Channel](#multi-channel)
    - [Context Propagation](#context-propagation)
    - [Queue Options](#queue-options)
  - [Notification Engine](#notification-engine)
    - [Overview](#overview)
    - [Resolvers](#resolvers)
    - [Engine Flow](#engine-flow)
    - [Fluent API](#fluent-api)
    - [Extending Resolvers](#extending-resolvers)
  - [Channels](#channels)
  - [Providers](#providers)
    - [SMS Providers](#sms-providers)
    - [WhatsApp Providers](#whatsapp-providers)
    - [Push Providers](#push-providers)
    - [Email Providers](#email-providers)
    - [In-App Provider](#in-app-provider)
  - [Provider Definitions](#provider-definitions)
  - [Provider Registry](#provider-registry)
  - [Delivery Tracking](#delivery-tracking)
  - [Events](#events)
  - [Testing](#testing)
    - [Running Tests](#running-tests)
    - [Writing Tests](#writing-tests)
  - [Publishing](#publishing)
    - [Config](#config)
    - [Migrations](#migrations)
  - [License](#license)

---

## Architecture

```
Business Module
       │
       ▼
Notification Engine   (orchestrator — resolves everything)
       │
       ▼
    Resolvers         (interfaces — replaced by application adapters)
       │
       ▼
Message Builder       (fluent API for constructing messages)
       │
       ▼
Message Delivery      (core delivery logic)
       │
       ▼
  ┌────┬────┬────┬────┐
Email  SMS  Push  In-App
(WhatsApp)
```

**Key principle:** The package is an **infrastructure package** only. It knows nothing about your business models (Student, Parent, Teacher, etc.). All business-specific logic is supplied by your application through resolver interfaces.

---

## Installation

```bash
composer require schoolpalm/message-delivery
```

The service provider is auto-discovered. If you disable auto-discovery, add it manually:

```php
// config/app.php
'providers' => [
    SchoolPalm\MessageDelivery\MessageDeliveryServiceProvider::class,
],
```

### Aliases

The package registers two facades:

| Facade            | Accessor           | Description                       |
| ----------------- | ------------------ | --------------------------------- |
| `MessageDelivery` | `message-delivery` | Direct message delivery API       |
| `Notification`    | `notification`     | Notification Engine orchestration |

---

## Configuration

Publish the configuration:

```bash
php artisan vendor:publish --tag=message-delivery-config
```

### Configuration Reference

```php
// config/message-delivery.php

return [

    /*
    | Default channel when none is explicitly selected.
    */
    'default_channel' => env('MESSAGE_DEFAULT_CHANNEL', 'email'),

    /*
    | Notification Engine defaults.
    */
    'notification' => [
        'default_language' => env('MESSAGE_DEFAULT_LANGUAGE', 'en'),
        'default_priority' => env('MESSAGE_DEFAULT_PRIORITY', 'normal'),
    ],

    /*
    | Enable/disable delivery lifecycle tracking.
    */
    'delivery_tracking' => env('MESSAGE_DELIVERY_TRACKING', true),

    /*
    | Default provider for each channel.
    */
    'channels' => [
        'email'    => env('MESSAGE_EMAIL_PROVIDER', 'laravel-mail'),
        'sms'      => env('MESSAGE_SMS_PROVIDER', 'egosms'),
        'whatsapp' => env('MESSAGE_WHATSAPP_PROVIDER', 'twilio-whatsapp'),
        'push'     => env('MESSAGE_PUSH_PROVIDER', 'firebase'),
    ],

    /*
    | Provider credentials (for development/testing only).
    | In production, providers may obtain config from TenantProviderSettings.
    */
    'providers' => [
        'laravel-mail' => [
            'mailer' => env('MESSAGE_MAIL_MAILER', env('MAIL_MAILER', 'smtp')),
        ],
        'egosms' => [
            'api_url'   => env('EGOSMS_API_URL'),
            'username'  => env('EGOSMS_USERNAME'),
            'password'  => env('EGOSMS_PASSWORD'),
            'sender_id' => env('EGOSMS_SENDER_ID'),
        ],
        'twilio-sms' => [
            'sid'   => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'from'  => env('TWILIO_FROM'),
        ],
        'twilio-whatsapp' => [
            'sid'   => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'from'  => env('TWILIO_WHATSAPP_FROM'),
        ],
        'firebase' => [
            'credentials' => env('FIREBASE_CREDENTIALS'),
        ],
    ],
];
```

---

## Message Delivery

### Single Channel

Each channel has a dedicated builder method accessible via the `MessageDelivery` facade.

**SMS:**
```php
use SchoolPalm\MessageDelivery\Facades\MessageDelivery;

MessageDelivery::sms()
    ->to('+250788123456')
    ->text('Your verification code is 1234')
    ->send();
```

**Email:**
```php
MessageDelivery::email()
    ->to('user@example.com')
    ->subject('Welcome')
    ->text('Thank you for joining')
    ->send();
```

**Email with view:**
```php
MessageDelivery::email()
    ->to('user@example.com')
    ->view('emails.welcome')
    ->with(['name' => 'John'])
    ->send();
```

**Push Notification:**
```php
MessageDelivery::push()
    ->to('device-token-xyz')
    ->title('New Message')
    ->text('You have a new message')
    ->with(['deep_link' => '/messages/123'])
    ->send();
```

**In-App Notification:**
```php
MessageDelivery::inApp()
    ->to(['notifiable_type' => 'App\Models\User', 'notifiable_id' => 1])
    ->title('Account Updated')
    ->text('Your profile was updated successfully')
    ->send();
```

**WhatsApp:**
```php
MessageDelivery::whatsapp()
    ->to('+250788123456')
    ->text('Your order has been confirmed')
    ->send();
```

### Multi-Channel

Send the same message through multiple channels:

```php
MessageDelivery::multi()
    ->channels(['email', 'sms', 'in_app'])
    ->to('user@example.com')
    ->title('Payment Received')
    ->text('Your payment of $50 has been received')
    ->send();
```

Or chain with context:

```php
MessageDelivery::withContext(['tenant_id' => 1])
    ->channels(['email', 'sms'])
    ->to('user@example.com')
    ->text('Your invoice is ready')
    ->send();
```

### Context Propagation

Attach execution context that flows through the entire delivery:

```php
MessageDelivery::withContext([
    'tenant_id' => 1,
    'school_id' => 42,
    'module'    => 'finance',
])
->sms()
->to('+250788123456')
->text('Fee payment reminder')
->send();
```

### Queue Options

Send messages through the queue:

```php
// Queue immediately
MessageDelivery::sms()
    ->to('+250788123456')
    ->text('Hello')
    ->queue();

// Queue with delay
MessageDelivery::email()
    ->to('user@example.com')
    ->text('Reminder')
    ->delay(now()->addHours(24))
    ->queue();

// Advanced queue configuration
MessageDelivery::sms()
    ->to('+250788123456')
    ->text('Hello')
    ->onQueue('notifications')
    ->onConnection('redis')
    ->tries(3)
    ->backoff([10, 30, 60])
    ->timeout(120)
    ->send();
```

---

## Notification Engine

### Overview

The Notification Engine is an **orchestrator** that sits between your business modules and the Message Delivery layer. Instead of calling `MessageDelivery::sms()->to(...)->send()` directly, you dispatch a **notification event** and the engine resolves everything.

```php
use SchoolPalm\MessageDelivery\Facades\Notification;

// Simple dispatch
Notification::dispatch('fee.payment_received', [
    'student_name' => 'John Doe',
    'amount'       => 50000,
    'due_date'     => '2025-01-15',
]);

// Or use the fluent API
Notification::event('fee.payment_received')
    ->data(['student_name' => 'John Doe', 'amount' => 50000])
    ->channels(['email', 'sms'])
    ->priority('high')
    ->dispatch();
```

### Resolvers

The engine uses **resolver interfaces** to determine how to deliver the notification. All resolvers have **Null implementations** so the package works out of the box. Your application replaces these bindings with custom implementations.

| Resolver Interface   | Null Implementation      | Purpose                               |
| -------------------- | ------------------------ | ------------------------------------- |
| `EventResolver`      | `NullEventResolver`      | Enrich event with metadata            |
| `RecipientResolver`  | `NullRecipientResolver`  | Resolve who receives the notification |
| `PreferenceResolver` | `NullPreferenceResolver` | Resolve user channel preferences      |
| `ChannelResolver`    | `NullChannelResolver`    | Determine delivery channels           |
| `LanguageResolver`   | `NullLanguageResolver`   | Determine notification language       |
| `TemplateResolver`   | `NullTemplateResolver`   | Load message templates                |
| `PriorityResolver`   | `NullPriorityResolver`   | Determine message priority            |
| `ScheduleResolver`   | `NullScheduleResolver`   | Determine delivery schedule           |
| `RetryResolver`      | `NullRetryResolver`      | Determine retry policy                |

### Engine Flow

```
NotificationEvent
       │
       ▼
EventResolver      → enrich event metadata
       │
       ▼
RecipientResolver  → resolve recipients
       │
       ▼
PreferenceResolver → resolve channel preferences
       │
       ▼
ChannelResolver    → determine channels
       │
       ▼
LanguageResolver   → determine language
       │
       ▼
TemplateResolver   → load message template
       │
       ▼
PriorityResolver   → determine priority
       │
       ▼
ScheduleResolver   → determine schedule/delay
       │
       ▼
RetryResolver      → determine retry policy
       │
       ▼
Build Messages     → construct Message objects per channel
       │
       ▼
MessageDelivery    → delegate to existing delivery infrastructure
```

### Fluent API

The `NotificationDispatch` builder provides a fluent chainable API:

```php
Notification::event('student.admitted')
    ->data([
        'student_name' => 'Jane Doe',
        'class'        => 'Grade 5',
        'admission_no' => 'ADM-2025-001',
    ])
    ->context([
        'tenant_id' => 1,
        'school_id' => 42,
    ])
    ->metadata([
        'source' => 'admissions_module',
    ])
    ->channels(['email', 'sms', 'in_app'])
    ->language('en')
    ->priority('high')
    ->template('student_admitted')
    ->dispatch();
```

### Extending Resolvers

To replace a resolver, bind your implementation in the service container:

```php
// In your AppServiceProvider or a dedicated service provider
use SchoolPalm\MessageDelivery\Notification\Contracts\RecipientResolver;

$this->app->bind(RecipientResolver::class, function ($app) {
    return new \App\Resolvers\MyRecipientResolver();
});
```

The engine will automatically use your implementation.

---

## Channels

The package registers five channels out of the box:

| Channel  | Identifier | Provider(s)                                       |
| -------- | ---------- | ------------------------------------------------- |
| Email    | `email`    | Laravel Mail (SES, Mailgun, SMTP, Postmark, etc.) |
| SMS      | `sms`      | EgoSMS, Twilio, Africa's Talking                  |
| WhatsApp | `whatsapp` | Meta WhatsApp, Twilio WhatsApp                    |
| Push     | `push`     | Firebase Cloud Messaging                          |
| In-App   | `in_app`   | Database Notifications                            |

---

## Providers

### SMS Providers

**EgoSMS** (`egosms`):
```php
MessageDelivery::sms()
    ->provider('egosms')
    ->to('+250788123456')
    ->text('Hello from EgoSMS')
    ->send();
```

**Twilio SMS** (`twilio-sms`):
```php
MessageDelivery::sms()
    ->provider('twilio-sms')
    ->to('+250788123456')
    ->text('Hello from Twilio')
    ->send();
```

**Africa's Talking** (`africas-talking`):
```php
MessageDelivery::sms()
    ->provider('africas-talking')
    ->to('+250788123456')
    ->text('Hello from Africa\'s Talking')
    ->send();
```

### WhatsApp Providers

**Meta WhatsApp** (`meta-whatsapp`):
```php
MessageDelivery::whatsapp()
    ->provider('meta-whatsapp')
    ->to('+250788123456')
    ->text('Hello from Meta WhatsApp')
    ->send();
```

**Twilio WhatsApp** (`twilio-whatsapp`):
```php
MessageDelivery::whatsapp()
    ->provider('twilio-whatsapp')
    ->to('+250788123456')
    ->text('Hello from Twilio WhatsApp')
    ->send();
```

### Push Providers

**Firebase Cloud Messaging** (`firebase`):
```php
MessageDelivery::push()
    ->provider('firebase')
    ->to('device-token')
    ->title('New Update')
    ->text('Your app has been updated')
    ->with(['click_action' => 'OPEN_ACTIVITY'])
    ->send();
```

### Email Providers

**Laravel Mail** (`laravel-mail`):
```php
MessageDelivery::email()
    ->provider('laravel-mail')
    ->to('user@example.com')
    ->subject('Welcome')
    ->text('Thank you for registering')
    ->send();
```

The Laravel Mail provider supports any mailer configured in `config/mail.php` (SES, Mailgun, Postmark, SMTP, Log, etc.).

### In-App Provider

**Database Notifications** (`database-notifications`):
```php
MessageDelivery::inApp()
    ->provider('database-notifications')
    ->to(['notifiable_type' => 'App\Models\User', 'notifiable_id' => 1])
    ->title('New Message')
    ->text('You have a new notification')
    ->send();
```

Recipients can be specified as:
- Associative array with `notifiable_type` and `notifiable_id` keys
- Simple string ID (uses configured default notifiable model)

---

## Provider Definitions

Provider definitions expose configuration fields for admin UIs:

```php
use SchoolPalm\MessageDelivery\Facades\MessageDelivery;

// Get a specific definition
$definition = MessageDelivery::definition('twilio-sms');
$fields = $definition->configurationFields();

// Get all definitions
$all = MessageDelivery::definitions();

// Get definitions for a channel
$smsProviders = MessageDelivery::providers('sms');
```

---

## Provider Registry

The provider registry manages the lifecycle of provider factories:

```php
use SchoolPalm\MessageDelivery\Registry\ProviderRegistry;

$registry = app(ProviderRegistry::class);
$factory = $registry->resolve('sms', 'egosms');
$provider = $factory->create($config);
```

---

## Delivery Tracking

When enabled, the package records delivery lifecycle events:

```php
// config/message-delivery.php
'delivery_tracking' => true,
```

Each delivery goes through statuses:
- `queued` → `processing` → `sent` → `delivered` / `failed`

Data is stored in the `message_deliveries` table and operational logs are written via `AppLogger`.

---

## Events

| Event                     | Description                                     |
| ------------------------- | ----------------------------------------------- |
| `MessageSending`          | Dispatched before a message is sent             |
| `MessageSent`             | Dispatched after a message is sent successfully |
| `MessageFailed`           | Dispatched when a message fails                 |
| `DeliveryReceiptReceived` | Dispatched when a delivery receipt is received  |

---

## Testing

### Running Tests

```bash
composer test
```

This runs all 225+ tests (712+ assertions) covering:

- Each channel and provider
- Delivery tracking lifecycle
- Provider resolution and configuration
- Failure handling and timeouts
- Metadata handling
- Multi-channel message building
- Notification Engine dispatch
- Resolver resolution and replacement
- Default (Null) resolver behavior
- Queue options
- Context propagation

### Writing Tests

```bash
php vendor/bin/pest --filter="Notification Engine"
php vendor/bin/pest --filter="SMS|Push"
```

---

## Publishing

### Config

```bash
php artisan vendor:publish --tag=message-delivery-config
```

### Migrations

```bash
php artisan vendor:publish --tag=message-delivery-migrations
php artisan migrate
```

---

## License

MIT License. See [LICENSE](LICENSE) for more information.
