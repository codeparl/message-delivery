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
  - [Provider Configuration Fields](#provider-configuration-fields)
    - [Available APIs](#available-apis)
    - [Initializing Settings (e.g. a Settings Scope)](#initializing-settings-eg-a-settings-scope)
    - [Registered Providers](#registered-providers)
- [MessageDelivery — Queue Context and Context Restoration](#messagedelivery--queue-context-and-context-restoration)
  - [1. Purpose](#1-purpose)
  - [2. Architecture](#2-architecture)
  - [3. Separation of Responsibilities](#3-separation-of-responsibilities)
  - [4. Why Core Provides the Restorers](#4-why-core-provides-the-restorers)
  - [5. Queue Context Structure](#5-queue-context-structure)
    - [Example: Tenant Job](#example-tenant-job)
    - [Example: Central Job](#example-central-job)
    - [Example: Context-Free Job](#example-context-free-job)
  - [6. Context Capture](#6-context-capture)
  - [7. Queue Context Restoration](#7-queue-context-restoration)
  - [8. Restoration Order](#8-restoration-order)
    - [Restoration Flow](#restoration-flow)
  - [9. Null Context Semantics](#9-null-context-semantics)
  - [10. Tenant Restoration](#10-tenant-restoration)
  - [11. School Restoration](#11-school-restoration)
  - [12. User Restoration](#12-user-restoration)
  - [13. Module Restoration](#13-module-restoration)
  - [14. ModuleHost Restoration Contract](#14-modulehost-restoration-contract)
  - [15. Central Runtime](#15-central-runtime)
  - [16. Tenant Runtime](#16-tenant-runtime)
  - [17. Settings Restoration](#17-settings-restoration)
  - [18. MessageDelivery Provider Resolution](#18-messagedelivery-provider-resolution)
  - [19. MessageDelivery Context Registration](#19-messagedelivery-context-registration)
  - [20. Queue Without Context](#20-queue-without-context)
  - [21. What the Queue Worker Must Not Do](#21-what-the-queue-worker-must-not-do)
  - [22. Why Context Must Be Captured at Dispatch](#22-why-context-must-be-captured-at-dispatch)
  - [23. Context Isolation](#23-context-isolation)
  - [24. SDK Runtime](#24-sdk-runtime)
  - [25. ContextHost as an Aggregate](#25-contexthost-as-an-aggregate)
  - [26. Core Restorer Responsibilities](#26-core-restorer-responsibilities)
  - [27. Example End-to-End Execution](#27-example-end-to-end-execution)
  - [28. Verified Runtime Behavior](#28-verified-runtime-behavior)
  - [29. Testing Matrix](#29-testing-matrix)
  - [30. Design Principles](#30-design-principles)
  - [31. Summary](#31-summary)
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

## Provider Configuration Fields

The package exposes the **configuration schema** for every provider so your application can initialize provider settings in its own storage (e.g. a tenant settings scope). The package **does not persist** anything — it only returns the fields; you decide where and how to save them.

Each field is a plain array with the canonical keys:

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

### Available APIs

All are accessible via the `MessageDelivery` facade:

```php
use SchoolPalm\MessageDelivery\Facades\MessageDelivery;

// Fields for a single provider as arrays
$fields = MessageDelivery::providerConfigurationFields('twilio-sms');

// Fields for a single provider as ConfigurationField objects
$fieldObjects = MessageDelivery::providerFieldObjects('twilio-sms');

// All providers, keyed by name → fields as arrays
$all = MessageDelivery::allProviderConfigurationFields();

// Providers for a channel → fields as arrays
$sms = MessageDelivery::providerConfigurationFieldsForChannel('sms');
$email = MessageDelivery::providerConfigurationFieldsForChannel('email');

// Look up a single field
$token = MessageDelivery::providerConfigurationField('twilio-sms', 'token');

// Flat settings map for DB seeding (provider.field => default)
$seed = MessageDelivery::providerSeedSettings();

// Scoped settings separating secrets from secured fields
$scoped = MessageDelivery::providerScopedSettings();
```

### Initializing Settings (e.g. a Settings Scope)

A common pattern is to seed a tenant's settings when a provider is enabled:

```php
use SchoolPalm\MessageDelivery\Facades\MessageDelivery;

// Seed a tenant's Twilio SMS settings
$fields = MessageDelivery::providerConfigurationFields('twilio-sms');

$settings = [
    'provider'   => 'twilio-sms',
    'channel'    => 'sms',
    'fields'     => $fields,
    'defaults'   => MessageDelivery::providerSeedSettings(),
];

// Persist $settings into your own settings scope/storage here.
```

To separate credentials from non-sensitive options, use `providerScopedSettings()`:

```php
$scoped = MessageDelivery::providerScopedSettings();

// $scoped['secured']  → non-secret fields (e.g. 'from', 'sender_id')
// $scoped['secrets']   → secret fields (e.g. 'token', 'password')
```

You can store `$scoped['secrets']` in an encrypted store and `$scoped['secured']` in a normal settings table.

### Registered Providers

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

# MessageDelivery — Queue Context and Context Restoration

This document describes how MessageDelivery preserves and restores application context when notification jobs are executed asynchronously through Laravel queues.

The design supports SchoolPalm production runtime, central runtime, tenant runtime, SDK/test runtime, CLI execution, and ordinary queue jobs that do not require contextual state.

## 1. Purpose

MessageDelivery may dispatch notification jobs from a contextual application environment. For example, a notification may be created while the application is operating inside:

- a specific tenant;
- a specific school;
- a specific authenticated user;
- a specific SchoolPalm module.

Queue workers execute jobs in a different PHP process from the process that dispatched them. Therefore, the worker cannot rely on the original in-memory application state being present.

The required context must therefore be captured when the job is dispatched and restored before the job executes.

**Core principle:** Queue context is explicit and optional. A queue job restores context only when context was provided. A job without context continues to execute normally.

## 2. Architecture

Context restoration is deliberately split between the shared ModuleBridge layer and the SchoolPalm Core application.

```
MessageDelivery
      │
      │ QueueContext
      ▼
Laravel Queue
      │
      │ serialized context
      ▼
RestoreJobContext
      │
      ▼
QueuedJobsManager
      │
      ▼
ContextResolver
      │
      ├── initializeTenant()
      ├── initializeSchool()
      ├── initializeUser()
      ├── initializeModule()
      └── restoreSettingsContext()
              │
              ▼
        ContextHost
              │
              ├── TenantHost
              ├── SchoolHost
              ├── UserHost
              └── ModuleHost
                      │
                      ▼
              Core Restorers
              ├── tenant.restorer
              ├── school.restorer
              ├── user.restorer
              └── module.restorer
```

## 3. Separation of Responsibilities

| Component                 | Responsibility                                                                                                                                               |
|---------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `QueueContext`            | Represents the contextual information associated with a queued job and provides serialization/deserialization.                                               |
| `RestoreJobContext`       | Queue middleware responsible for restoring context before the queued job executes.                                                                           |
| `QueuedJobsManager`       | Provides the queue-level mechanism for registering and invoking context restoration.                                                                         |
| `ContextResolver`         | Coordinates restoration of tenant, school, user, module and settings context.                                                                                |
| `ContextHost`             | Aggregates the individual context hosts and provides a common interface for reading, setting, forgetting and restoring context.                              |
| `TenantHost`              | Manages the current tenant context.                                                                                                                          |
| `SchoolHost`              | Manages the current school context.                                                                                                                          |
| `UserHost`                | Manages the current user context.                                                                                                                            |
| `ModuleHost`              | Manages the current module context.                                                                                                                          |
| SchoolPalm Core Restorers | Perform framework/application-specific lookup and restoration. Examples include `tenant.restorer`, `school.restorer`, `user.restorer` and `module.restorer`. |

## 4. Why Core Provides the Restorers

ModuleBridge is shared infrastructure. It must be usable by both the real SchoolPalm application and the SchoolPalm SDK.

Therefore ModuleBridge must not directly reference application-specific classes such as:

```
App\Models\Tenant
App\Models\School
App\Models\User
```

Instead, ModuleBridge delegates actual restoration to the host application.

SchoolPalm Core registers the appropriate restoration mechanisms:

```
tenant.restorer
school.restorer
user.restorer
module.restorer
```

This allows ModuleBridge to remain host-agnostic while Core retains responsibility for its own database models, tenancy implementation, authentication system and module registry.

## 5. Queue Context Structure

A contextual queue job may contain the following information:

```
[
    'tenant_id' => 'emma',
    'school_id' => '1',
    'user_id'   => '1',
    'module'    => 'schoolpalm.common.student',
    'metadata'  => [],
]
```

Only identifiers and lightweight contextual information should be serialized. The queue should not serialize full Eloquent models or other large runtime objects as its context.

### Example: Tenant Job

```
[
    'tenant_id' => 'emma',
    'school_id' => '1',
    'user_id'   => '1',
    'module'    => 'schoolpalm.common.student',
]
```

### Example: Central Job

```
[
    'tenant_id' => null,
    'school_id' => null,
    'user_id'   => '15',
    'module'    => null,
]
```

A central user may still exist even though there is no tenant or school context.

### Example: Context-Free Job

```
[
    // empty context
]
```

Context-free jobs are valid and must not be forced into a central or tenant context.

## 6. Context Capture

Context is captured at dispatch time, where the original application context is available.

The important distinction is:

```
Capture:
    "What context existed when this job was dispatched?"

Restore:
    "Recreate that exact context before this job executes."
```

The worker should not attempt to infer the original context from the worker environment.

**Important:** The queue worker must not decide that a job belongs to the central application merely because the worker itself started in the central application. The serialized QueueContext is the source of truth for the job's contextual execution.

## 7. Queue Context Restoration

When a queued job begins execution, `RestoreJobContext` checks whether the job provides a queue context.

If the context is empty or absent, the job proceeds normally.

If a valid context exists, the manager invokes the registered context restoration callback.

```
if (
    $context instanceof QueueContext
    &&
    !$context->isEmpty()
) {
    $this->manager->restoreContext($context);
}

$next($job);
```

## 8. Restoration Order

Context is restored in dependency order.

```
1. Tenant
2. School
3. User
4. Module
5. Settings context
```

This order is important because school and user restoration may depend on the tenant database being initialized first.

### Restoration Flow

```
QueueContext
    │
    ├── tenant_id
    │       ↓
    │   initializeTenant()
    │
    ├── school_id
    │       ↓
    │   initializeSchool()
    │
    ├── user_id
    │       ↓
    │   initializeUser()
    │
    ├── module
    │       ↓
    │   initializeModule()
    │
    └── settings
            ↓
        restoreSettingsContext()
```

## 9. Null Context Semantics

`null` has an explicit meaning:

> There is no context of this type to restore.

Therefore restoration methods must not attempt database lookups when their identifier is `null`.

```
if ($tenantId === null) {
    $this->contextHost->forgetTenant();
    return $this;
}
```

The same principle applies to school, user and module restoration.

## 10. Tenant Restoration

Tenant restoration is host-specific and therefore delegated to the Core-provided `tenant.restorer`.

```
$restorer = app('tenant.restorer');

$tenant = $restorer($tenantId);

$this->setCurrent($tenant);
```

In SDK runtime, fake/demo tenant data may be used instead.

In central SchoolPalm runtime, there is no current tenant and tenant restoration should not manufacture a dummy tenant.

## 11. School Restoration

Schools exist within tenant context. Therefore school restoration is performed after tenant initialization.

```
$restorer = app('school.restorer');

$school = $restorer($schoolId);

$this->setCurrent($school);
```

Central SchoolPalm has no school context, so a central execution must not create or restore a dummy school.

## 12. User Restoration

Users may exist in both central and tenant execution environments. Therefore user restoration must not simply return `null` whenever the application is central.

The Core user restorer resolves the user using the database context that has already been established.

```
$user = User::find($userId);
```

In tenant execution, the tenant has already initialized the tenant database. In central execution, the application remains on the central database.

## 13. Module Restoration

Modules are represented in queue context using their `module_key`.

```
'module' => 'schoolpalm.common.student'
```

A string module value is therefore not treated as an already-resolved module object. It must be resolved through the host's module restoration mechanism.

```
if (is_string($module)) {
    $this->contextHost->restoreModule($module);
    return;
}
```

SchoolPalm Core resolves the module using the module registry:

```
ModuleRegistry::findByNamespaceOrKey($moduleKey);
```

The shared ModuleBridge layer does not need to know how the Core module registry is implemented.

## 14. ModuleHost Restoration Contract

Module restoration supports all of the following forms:

```
string
array
object
null
```

Typical usage is:

```
$moduleHost->restore('schoolpalm.common.student');
```

Arrays and objects may contain or expose a module key through fields or methods such as:

```
module_key
key
namespace

moduleKey()
getModuleKey()
toArray()
```

## 15. Central Runtime

Central SchoolPalm is a valid runtime but does not automatically imply that every context type exists.

| Context | Central Runtime |
|---------|-----------------|
| Tenant  | None            |
| School  | None            |
| User    | May exist       |
| Module  | None            |

Individual host services may therefore use runtime information such as `is_central()` where necessary to determine whether their specific context exists.

However, `ContextHostService` itself remains an aggregate service and does not own the application's central/tenant topology.

## 16. Tenant Runtime

In tenant execution, the expected contextual chain is:

```
Tenant
    ↓
School
    ↓
User
    ↓
Module
    ↓
School-scoped Settings
```

This allows MessageDelivery to resolve settings such as:

```
message_delivery.email.default_provider
```

against the correct tenant and school scope.

## 17. Settings Restoration

Settings are not required to be serialized as part of the queue context.

Instead, the worker first restores the tenant and school context. The settings system can then automatically establish the appropriate settings scope.

```
tenant_id = emma
school_id = 1
school_code = EMMA-SCH0001

        ↓

Settings Auto Scope

        ↓

context_type = school
context_id   = 1
cache_context = [emma, EMMA-SCH0001]
```

This keeps the queue payload small while ensuring that settings are resolved against the correct contextual environment.

## 18. MessageDelivery Provider Resolution

Once the queue context has been restored, MessageDelivery can resolve its provider normally.

For example:

```
message_delivery.email.default_provider
        ↓
laravel-mail
        ↓
message_delivery.email.laravel-mail.config
        ↓
mailer = mailpit
```

The provider manager therefore does not need special queue-specific logic to determine the tenant or school.

Queue restoration happens before provider resolution.

## 19. MessageDelivery Context Registration

MessageDelivery registers a context restoration callback with the queue manager.

```
MessageDelivery::restoreContextUsing(
    function (QueueContext $context): void {
        app(ContextResolver::class)
            ->restoreQueueContext($context);
    }
);
```

This registration should be performed during application/module initialization rather than inside an individual notification provider or adapter.

## 20. Queue Without Context

Not every queued job belongs to a tenant, school, user or module.

Therefore context restoration must never be mandatory for every queue job.

```
Job with context
    ↓
Restore context
    ↓
Execute job


Job without context
    ↓
Skip restoration
    ↓
Execute job normally
```

**Design rule:** Queue is general infrastructure. Context is optional metadata attached to a job when the dispatching environment requires it.

## 21. What the Queue Worker Must Not Do

The worker must not:

- guess the tenant from the worker's current application state;
- assume every job belongs to the central application;
- create dummy tenants or schools when context is absent;
- query tenant or school models from ModuleBridge directly;
- resolve modules through Core-specific classes from shared infrastructure;
- serialize complete Eloquent models as queue context.

## 22. Why Context Must Be Captured at Dispatch

Laravel queue workers are long-running processes. The application state present when a job is dispatched is not necessarily the state present when the worker executes it.

For example, a worker may start without any tenant initialized while processing a job that was originally dispatched from tenant `emma`.

The queue payload therefore needs to carry:

```
"tenant_id": "emma"
```

The worker can then explicitly initialize that tenant before accessing tenant-dependent resources.

## 23. Context Isolation

Context belongs to the individual queued job execution.

A worker must not allow context from one job to accidentally become context for another job.

Conceptually:

```
Job A
    ↓
restore Context A
    ↓
execute
    ↓
job lifecycle ends


Job B
    ↓
restore Context B
    ↓
execute
    ↓
job lifecycle ends
```

Queue middleware and application lifecycle management should therefore ensure that contextual state does not leak between jobs.

## 24. SDK Runtime

The SDK does not necessarily have access to the real SchoolPalm application database or Core models.

SDK runtime may therefore use fake/demo data supplied through its own registries and context services.

This allows the same ModuleBridge contracts to operate in both:

- real SchoolPalm runtime;
- SDK/test runtime.

The shared contract remains the same while the underlying restoration mechanism differs.

## 25. ContextHost as an Aggregate

`ContextHostService` is intentionally simple. It delegates operations to the individual context hosts.

```
ContextHostService
       │
       ├── TenantHost
       ├── SchoolHost
       ├── UserHost
       └── ModuleHost
```

For example:

```
public function restoreModule(
    array|object|string|null $module
): static {
    $this->module->restore($module);

    return $this;
}
```

The aggregate should not contain Core-specific model lookup logic.

## 26. Core Restorer Responsibilities

Core restorers are the appropriate location for application-specific restoration logic.

| Binding           | Core Responsibility                                                              |
|-------------------|----------------------------------------------------------------------------------|
| `tenant.restorer` | Resolve and initialize the requested tenant.                                     |
| `school.restorer` | Resolve the requested school within the initialized tenant.                      |
| `user.restorer`   | Resolve the requested user using the current central or tenant database context. |
| `module.restorer` | Resolve the module using the SchoolPalm module registry.                         |

## 27. Example End-to-End Execution

Consider a notification dispatched while operating in:

```
Tenant: Emma
School: EMMA-SCH0001
User:   1
Module: schoolpalm.common.student
```

The job carries:

```
[
    'tenant_id' => 'emma',
    'school_id' => '1',
    'user_id'   => '1',
    'module'    => 'schoolpalm.common.student',
]
```

The worker then performs:

```
initializeTenant('emma')
        ↓
initializeSchool('1')
        ↓
initializeUser('1')
        ↓
initializeModule('schoolpalm.common.student')
        ↓
restoreSettingsContext()
        ↓
resolve MessageDelivery settings
        ↓
resolve provider
        ↓
execute notification
```

## 28. Verified Runtime Behavior

A successful tenant queue execution produced the following contextual information:

```
tenant_id = emma
school_id = 1
user_id   = 1
module    = schoolpalm.common.student
```

Queue settings restoration then reported:

```
tenant_id = emma
school_id = 1
database_connection = tenant
```

Settings auto-scope subsequently established:

```
context_type = school
context_id   = 1
cache_context = [
    "emma",
    "EMMA-SCH0001"
]
```

MessageDelivery then resolved:

```
default_provider = laravel-mail
mailer           = mailpit
```

**Result:** The queue worker successfully reconstructed the original tenant, school, user and module context before MessageDelivery resolved its school-scoped provider configuration.

## 29. Testing Matrix

The context system should be tested against at least the following scenarios.

| Scenario                  | Tenant | School | User | Module | Expected Behavior                     |
|---------------------------|--------|--------|------|--------|---------------------------------------|
| Tenant contextual job     | Yes    | Yes    | Yes  | Yes    | Restore complete context.             |
| Tenant job without module | Yes    | Yes    | Yes  | No     | Restore tenant, school and user only. |
| Central user job          | No     | No     | Yes  | No     | Restore central user.                 |
| Context-free job          | No     | No     | No   | No     | Skip context restoration.             |
| SDK contextual job        | Fake   | Fake   | Fake | Fake   | Use SDK/demo restoration mechanisms.  |

## 30. Design Principles

1. **Context is explicit.** A queued job carries the identifiers needed to reconstruct its original execution context.
2. **Context is optional.** Ordinary jobs do not need to provide contextual information.
3. **Core owns application-specific restoration.** ModuleBridge does not depend on Core models.
4. **Restoration follows dependency order.** Tenant is restored before tenant-dependent school and user operations.
5. **Null means absent.** A null identifier means there is nothing to restore.
6. **No dummy production context.** Central execution must not manufacture fake tenants, schools or modules.
7. **Module keys are serialized.** Queue payloads carry lightweight module identifiers rather than runtime module objects.
8. **Settings are resolved after context restoration.** This ensures settings are scoped to the correct tenant and school.
9. **The worker does not infer original context.** The serialized job context is authoritative for that job.

## 31. Summary

MessageDelivery queue context provides a clean mechanism for carrying contextual execution state across Laravel queue boundaries.

The architecture separates generic queue/context infrastructure from SchoolPalm-specific application logic:

```
MessageDelivery
      ↓
QueueContext
      ↓
Queue middleware
      ↓
ContextResolver
      ↓
ContextHost
      ↓
Core restorers
      ↓
Tenant / School / User / Module
      ↓
Settings
      ↓
Message provider
```

This allows the same MessageDelivery infrastructure to operate safely across central, tenant, SDK and context-free execution environments without coupling the shared package to SchoolPalm Core models.

**Final architectural rule:** The queue carries context; ModuleBridge coordinates restoration; Core knows how to restore application-specific resources; and MessageDelivery consumes the restored context without needing to understand tenancy, schools or module internals.

SchoolPalm MessageDelivery — Queue Context and Context Restoration

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
