# SchoolPalm Message Delivery - Complete Documentation

## Table of Contents

1. [Philosophy](#philosophy)
2. [Core Concepts](#core-concepts)
3. [Getting Started](#getting-started)
4. [Builder API](#builder-api)
5. [Channels](#channels)
6. [Providers](#providers)
7. [Queue Delivery](#queue-delivery)
8. [Events](#events)
9. [Advanced Features](#advanced-features)
10. [Internal Architecture](#internal-architecture)
11. [Extension Points](#extension-points)

## Philosophy

### Design Principles

SchoolPalm Message Delivery is built on the **Abstraction-over-Implementation** principle. The core philosophy is:

> **Applications communicate with Message Delivery without knowing the underlying provider.**

**Core Abstraction Layers:**

- Application → MessageDelivery API (channel-agnostic)
- MessageDelivery API → Channel Layer (SMS, Email, Push, WhatsApp)
- Channel Layer → Provider Layer (EgoSMS, Twilio, Firebase, etc.)
- Provider Layer → External Services (3rd party APIs)

### Key Principles

1. **Provider Independence**: Applications don't lock into specific providers
2. **Channel Abstraction**: SMS, Email, Push, WhatsApp all use the same API
3. **Configuration Centralization**: Tenant settings drive provider selection
4. **Extensibility**: Custom channels and providers can be registered
5. **Reliability**: Queue support, retry mechanisms, and delivery tracking

---

## Core Concepts

### Channels

A **Channel** is a communication medium:

- **SMS**: Text messaging
- **Email**: Email messages
- **Push**: Push notifications
- **WhatsApp**: WhatsApp Business API

Channels are implementation-agnostic. They define the *how* messages are sent, but not *by whom*.

### Providers

A **Provider** is a third-party service that delivers messages through a channel.

| Channel | Providers |
|---------|-----------|
| SMS | EgoSMS, Twilio, Africa's Talking |
| Email | Laravel Mail (SES, Mailgun, Postmark) |
| Push | Firebase Cloud Messaging |
| WhatsApp | Meta (WhatsApp Business), Twilio |

### Messages

A **Message** is a unit of communication containing:

- Recipients (phone, email, device token)
- Content (view, template, or raw text)
- Data/variables for template rendering
- Metadata (provider, priority, context)

### Templates

Templates come in two flavors:

1. **Application Views**: Laravel Blade templates
2. **Database Templates**: Admin-managed templates

### Execution Context

Context carries application-specific metadata (tenant_id, school_id, module) that flows through the entire delivery pipeline.

---

## Getting Started

### Installation

```bash
composer require schoolpalm/message-delivery
```

### Configuration

Publish migrations:

```bash
php artisan vendor:publish --tag=message-delivery-migrations
php artisan migrate
```

### Basic SMS

```php
use SchoolPalm\\MessageDelivery\\Facades\\MessageDelivery;

MessageDelivery::sms()
    ->text('Hello, your fee balance is UGX 50,000')
    ->to('256701234567')
    ->send();
```

### Basic Email

```php
MessageDelivery::email()
    ->view('emails.invoice')
    ->to('parent@example.com')
    ->with(['invoice' => $invoice])
    ->send();
```

### Push Notification

```php
MessageDelivery::push()
    ->text('Attendance alert')
    ->to($deviceToken)
    ->send();
```

### WhatsApp

```php
MessageDelivery::whatsapp()
    ->text('Your exam results are ready')
    ->to('256701234567')
    ->send();
```

---

## Builder API

The package uses the **Builder Pattern** for fluent, chainable APIs.

### ChannelMessageBuilder

Used for single-channel messages:

```php
MessageDelivery::sms()
    ->text('Hello')              // Set content
    ->to($recipients)            // Set recipients
    ->with($data)                // Pass variables
    ->provider('egosms')         // Force provider
    ->priority('high')           // Set priority
    ->queue()                    // Queue delivery
    ->delay($time)               // Delay sending
    ->send();                    // Execute
```

### MultiChannelMessageBuilder

Send same message through multiple channels:

```php
MessageDelivery::channels(['sms', 'email', 'push'])
    ->view('messages.exam-results')
    ->to($parent)
    ->with(['student' => $student])
    ->send();
```

### Builder Methods

| Method | Purpose |
|--------|---------|
| `text()` | Raw message content |
| `view()` | Use Laravel template |
| `template()` | Use database template |
| `to()` | Set recipients |
| `with()` | Pass template variables |
| `provider()` | Force specific provider |
| `priority()` | Set delivery priority |
| `queue()` | Queue for later delivery |
| `delay()` | Delay delivery |
| `send()` | Send immediately |
| `failover()` | Set provider fallback order |

---

## Channels

### SMS Channel

```php
MessageDelivery::sms()
    ->text('Your code is 12345')
    ->to('256701234567')
    ->send();
```

Supported providers: EgoSMS, Twilio, Africa's Talking

### Email Channel

```php
MessageDelivery::email()
    ->view('emails.welcome')
    ->to('user@example.com')
    ->send();
```

Supported providers: Laravel Mail (SES, Mailgun, Postmark)

### Push Channel

```php
MessageDelivery::push()
    ->text('You have a new assignment')
    ->to($student->device_tokens) // Array of device tokens
    ->send();
```

Supported providers: Firebase Cloud Messaging

### WhatsApp Channel

```php
MessageDelivery::whatsapp()
    ->text('Your fee payment is due')
    ->to('256701234567')
    ->send();
```

Supported providers: Meta (WhatsApp Business), Twilio

---

## Providers

### Built-in Providers

**SMS Providers:**
- EgoSMS: Configuration (username, api_key, sender_id), Features (Unicode, delivery reports)
- Twilio: Configuration (account_sid, auth_token, from_number), Features (Global, webhooks)
- Africa's Talking: Configuration (username, api_key, sender_id), Features (Africa-focused)

**Email Providers:**
- Laravel Mail: SES, Mailgun, Postmark, SMTP

**Push Providers:**
- Firebase: Configuration (project_id, service_account_key)

**WhatsApp Providers:**
- Meta: Configuration (phone_number_id, access_token)
- Twilio: Configuration (account_sid, auth_token, from_number)

### Provider Discovery

```php
// All providers
$all = MessageDelivery::definitions();

// SMS providers only
$sms = MessageDelivery::providers('sms');

// Get provider definition
$definition = MessageDelivery::definition('egosms');
$fields = $definition->configurationFields();
```

### Provider Resolution

1. Explicit provider in message
2. Tenant-configured provider
3. Error if no provider found

---

## Queue Delivery

### Queued Delivery

```php
MessageDelivery::sms()
    ->view('messages.alert')
    ->to($parents)
    ->queue();
```

### Delayed Delivery

```php
MessageDelivery::sms()
    ->text('Reminder')
    ->to($phone)
    ->delay(now()->addDay())
    ->send();
```

### Retry Failed Messages

```bash
php artisan message-delivery:retry-failed
```

---

## Events

The package dispatches events throughout the message lifecycle:

- **MessageSending**: Before delivery attempt
- **MessageSent**: After successful immediate delivery
- **MessageFailed**: When delivery fails
- **MessageDelivered**: When provider confirms delivery
- **DeliveryReceiptReceived**: When delivery receipt webhook received

```php
Event::listen(MessageSent::class, function($event) {
    $result = $event->result;
    Log::info("Message sent via {$result->provider}");
});
```

---

## Advanced Features

### Provider Failover

```php
MessageDelivery::sms()
    ->text('Emergency alert')
    ->to($phone)
    ->failover(['egosms', 'twilio', 'africastalking'])
    ->send();
```

### Message Priority

```php
MessageDelivery::sms()
    ->text('Critical alert')
    ->priority('high')
    ->send();
```

### Execution Context

```php
MessageDelivery::withContext([
    'tenant_id' => 1,
    'school_id' => 5,
    'module' => 'finance'
])
->sms()
->view('messages.fee-reminder')
->to($phone)
->send();
```

### Multiple Recipients

```php
$parents = ['256701234567', '256702234567', '256703234567'];

MessageDelivery::sms()
    ->text('School notice')
    ->to($parents)
    ->send();
```

---

## Internal Architecture

### System Architecture Diagram

```
Application Code
       ↓
MessageDelivery (Facade)
       ↓
Builder Pattern (ChannelMessageBuilder / MultiChannelMessageBuilder)
       ↓
MessageManager (Orchestrates delivery)
       ↓
DeliveryManager (Resolves channel and provider)
       ↓
Channel (SMS, Email, Push, WhatsApp)
       ↓
Provider (EgoSMS, Twilio, Firebase, etc.)
       ↓
External API Service
       ↓
DeliveryResult (sent, failed, queued, delivered)
```

### Delivery Flow

1. Application calls MessageDelivery::sms()->send()
2. Builder assembles Message object
3. MessageManager dispatches MessageSending event
4. DeliveryManager orchestrates:
   - Resolves channel from ChannelRegistry
   - Resolves provider from ProviderManager
   - Calls Channel.send(message, provider)
5. Provider executes:
   - Validates configuration
   - Formats message for API
   - Calls external service
   - Returns DeliveryResult
6. Results handled and recorded
7. Return DeliveryResult to caller

### Key Components

**MessageDelivery** (Facade Entry Point)
- sms() → ChannelMessageBuilder
- email() → ChannelMessageBuilder
- push() → ChannelMessageBuilder
- whatsapp() → ChannelMessageBuilder
- channels(array) → MultiChannelMessageBuilder
- withContext(context) → new MessageDelivery
- providers(channel) → Provider definitions
- definitions() → All definitions

**Builders**
- ChannelMessageBuilder: Single channel messages
- MultiChannelMessageBuilder: Multi-channel messages

**Managers**
- MessageManager: Orchestrates message delivery
- DeliveryManager: Coordinates channel and provider
- ProviderManager: Resolves provider instances
- ChannelManager: Manages channel registration

**Registries**
- ChannelRegistry: Registers and resolves channels
- ProviderRegistry: Registers and creates providers
- DefinitionRegistry: Stores provider definitions

**Message Objects**
- Message: Container for message data and metadata
- DeliveryResult: Delivery outcome (sent, failed, queued, delivered)

---

## Extension Points

### Custom Channels

Implement the `MessageChannel` contract:

```php
class SlackChannel extends Channel
{
    public function name(): string { return 'slack'; }
    
    public function send(Message $message, MessageProvider $provider): DeliveryResult
    {
        $content = $message->text 
            ?? view($message->view, $message->data)->render();
        
        return $provider->send(
            new Message(channel: 'slack', recipients: $message->recipients, text: $content)
        );
    }
}
```

### Custom Providers

Implement the `MessageProvider` contract:

```php
class SlackProvider implements MessageProvider
{
    public function name(): string { return 'slack'; }
    public function channel(): string { return 'slack'; }
    public function configured(): bool { /* ... */ }
    public function metadata(): array { /* ... */ }
    public function send(Message $message): DeliveryResult { /* ... */ }
}
```

### Custom Provider Factories

Implement the `ProviderFactory` contract:

```php
class SlackFactory implements ProviderFactory
{
    public function name(): string { return 'slack'; }
    public function channel(): string { return 'slack'; }
    public function create(array $configuration = []): MessageProvider
    {
        return new SlackProvider($configuration);
    }
}
```

---

## Best Practices

### Error Handling

```php
try {
    $result = MessageDelivery::sms()
        ->view('messages.alert')
        ->to($phone)
        ->send();

    if ($result->isFailed()) {
        Log::warning('SMS failed', ['error' => $result->error]);
    }
} catch (InvalidArgumentException $e) {
    Log::error('Message configuration error', ['message' => $e->getMessage()]);
}
```

### Large Batches

Always queue for bulk operations:

```php
foreach ($students as $student) {
    MessageDelivery::sms()
        ->view('messages.result')
        ->to($student->parent_phone)
        ->queue();  // Queue, don't send immediately
}
```

### Context Usage

Always include tenant/school context:

```php
MessageDelivery::withContext([
    'tenant_id' => $school->tenant_id,
    'school_id' => $school->id,
    'module' => 'finance'
])
->sms()
->to($phone)
->send();
```

### Event Monitoring

Listen to delivery events:

```php
Event::listen([MessageSent::class, MessageFailed::class], function($event) {
    Activity::create([
        'tenant_id' => $event->message->context('tenant_id'),
        'status' => $event instanceof MessageSent ? 'sent' : 'failed'
    ]);
});
```

---

## Configuration

### Tenant Provider Settings

Implement `TenantProviderSettings` to resolve provider configuration:

```php
class TenantMessagingSettings implements TenantProviderSettings
{
    public function providerFor(string $channel): ?string
    {
        $tenant = auth()->user()->tenant;
        return $tenant->getSetting("messaging.{$channel}.provider");
    }

    public function configurationFor(string $channel, string $provider): array
    {
        $tenant = auth()->user()->tenant;
        return $tenant->getSetting("messaging.{$channel}.{$provider}", []);
    }

    public function enabled(string $channel, string $provider): bool
    {
        $config = $this->configurationFor($channel, $provider);
        return count($config) > 0;
    }
}
```

---

## Database Schema

The package publishes a migration creating `message_deliveries` table:

```php
Schema::create('message_deliveries', function (Blueprint $table) {
    $table->id();
    $table->string('channel');
    $table->string('provider')->nullable();
    $table->string('recipient');
    $table->text('view')->nullable();
    $table->string('template')->nullable();
    $table->text('content')->nullable();
    $table->json('data')->nullable();
    $table->json('context')->nullable();
    $table->string('status');
    $table->string('provider_message_id')->nullable();
    $table->text('error')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamp('sent_at')->nullable();
    $table->timestamp('delivered_at')->nullable();
    $table->timestamps();
});
```

---

## Testing

### Mock Providers

```php
public function test_sms_sending()
{
    MessageDelivery::shouldReceive('send')
        ->once()
        ->andReturn(DeliveryResult::success('egosms', 'msg_123'));

    $result = MessageDelivery::sms()
        ->text('Test')
        ->to('256701234567')
        ->send();

    $this->assertTrue($result->isSuccessful());
}
```

---

## Conclusion

SchoolPalm Message Delivery provides a powerful, extensible system for multi-channel message delivery. By separating concerns into channels and providers, it enables:

- **Flexibility**: Switch providers without changing application code
- **Scalability**: Add new channels and providers easily
- **Reliability**: Queue support, failover, and delivery tracking
- **Tenant Isolation**: Context-aware delivery for multi-tenant applications

For more information, refer to the README.md and code examples.
