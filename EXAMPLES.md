# SchoolPalm Message Delivery - Practical Examples

This document contains real-world usage examples for the Message Delivery package.

## Table of Contents

1. [Basic Usage Examples](#basic-usage-examples)
2. [Advanced Patterns](#advanced-patterns)
3. [Error Handling](#error-handling)
4. [Event Handling](#event-handling)
5. [Testing](#testing)
6. [Custom Implementations](#custom-implementations)

---

## Basic Usage Examples

### Simple SMS

```php
<?php

use SchoolPalm\MessageDelivery\Facades\MessageDelivery;

// Send a simple SMS to a student's parent
MessageDelivery::sms()
    ->text('Your child has completed their assignment.')
    ->to('256701234567')
    ->send();

// Multiple recipients
MessageDelivery::sms()
    ->text('School will be closed tomorrow due to maintenance.')
    ->to(['256701234567', '256702234567', '256703234567'])
    ->send();
```

### Template-Based SMS

```php
<?php

// Using Laravel Blade view
MessageDelivery::sms()
    ->view('sms.fee-reminder')
    ->to($parent->phone)
    ->with([
        'student_name' => $student->name,
        'amount_due' => $balance->amount,
        'due_date' => $balance->due_date->format('d M Y'),
    ])
    ->send();

// Using database template
MessageDelivery::sms()
    ->template('fee_reminder')
    ->to($parent->phone)
    ->with([
        'student_name' => $student->name,
        'amount_due' => $balance->amount,
    ])
    ->send();
```

### Email with Attachment

```php
<?php

// Email using a Blade view
MessageDelivery::email()
    ->view('emails.report-card')
    ->to($parent->email)
    ->with([
        'student' => $student,
        'report_card' => $reportCard,
    ])
    ->send();

// Raw text email
MessageDelivery::email()
    ->text('Your fee invoice has been generated. Please login to download.')
    ->to($parent->email)
    ->send();
```

### Push Notification

```php
<?php

// Send push notification
MessageDelivery::push()
    ->text('You have a new assignment in Mathematics')
    ->to($student->device_tokens) // Array of device tokens
    ->send();

// With priority
MessageDelivery::push()
    ->text('URGENT: System maintenance scheduled for tonight 10 PM')
    ->to($deviceToken)
    ->priority('high')
    ->send();
```

### WhatsApp Message

```php
<?php

// WhatsApp text message
MessageDelivery::whatsapp()
    ->text('Hello! Your exam results are now available.')
    ->to('256701234567')
    ->send();

// Using Meta provider specifically
MessageDelivery::whatsapp()
    ->text('Attendance: Your child attended 95% of classes this month.')
    ->to($parent->phone)
    ->provider('meta')
    ->send();
```

---

## Advanced Patterns

### Multi-Channel Delivery

Send the same message through multiple channels simultaneously:

```php
<?php

// Send through SMS, Email, and Push
$result = MessageDelivery::channels(['sms', 'email', 'push'])
    ->view('messages.exam-alert')
    ->to($parent)
    ->with([
        'student' => $student,
        'subject' => 'Mathematics',
        'score' => 85,
    ])
    ->send();

// Each channel delivers independently
foreach ($result as $delivery) {
    echo "Channel: {$delivery->channel}, Status: {$delivery->status}";
}
```

### Queue Delivery

Queue messages for background processing:

```php
<?php

// Queue SMS for immediate (but async) delivery
MessageDelivery::sms()
    ->text('Your fee balance is UGX 50,000')
    ->to($parent->phone)
    ->queue()
    ->send();

// Queue for later delivery (delayed)
MessageDelivery::sms()
    ->view('sms.reminder')
    ->to($phone)
    ->with(['due_date' => $date])
    ->delay(now()->addDay()) // Send tomorrow
    ->send();

// Or use queue() with delay
MessageDelivery::sms()
    ->text('Fee reminder')
    ->to($phone)
    ->queue()
    ->delay(now()->addHours(2))
    ->send();
```

### Provider Selection

Explicitly select a provider or set fallback order:

```php
<?php

// Force specific provider
MessageDelivery::sms()
    ->text('Critical alert')
    ->to($phone)
    ->provider('egosms')
    ->send();

// Failover chain: Try EgoSMS, fallback to Twilio, then Africa's Talking
MessageDelivery::sms()
    ->text('Important message')
    ->to($phone)
    ->failover(['egosms', 'twilio', 'africastalking'])
    ->send();
```

### With Execution Context

Attach metadata for multi-tenant applications:

```php
<?php

// Attach context that flows through entire delivery
MessageDelivery::withContext([
    'tenant_id' => auth()->user()->tenant_id,
    'school_id' => auth()->user()->school_id,
    'module' => 'finance',
    'triggered_by' => auth()->id(),
])
->sms()
->view('sms.fee-alert')
->to($parent->phone)
->with(['amount' => $amount])
->send();

// Context is available in events
Event::listen(MessageSent::class, function ($event) {
    Activity::create([
        'tenant_id' => $event->message->context('tenant_id'),
        'school_id' => $event->message->context('school_id'),
        'action' => 'sms_sent',
        'triggered_by' => $event->message->context('triggered_by'),
    ]);
});
```

### Batch Operations

Efficiently send messages to many recipients:

```php
<?php

// GOOD: Queue bulk messages (don't send immediately)
$parents = Parent::where('school_id', $schoolId)
    ->where('active', true)
    ->chunk(100, function ($chunk) {
        foreach ($chunk as $parent) {
            MessageDelivery::withContext([
                'school_id' => $schoolId,
                'parent_id' => $parent->id,
            ])
            ->sms()
            ->text('School closing early at 1 PM today.')
            ->to($parent->phone)
            ->queue() // Queue for background processing
            ->send();
        }
    });

// BAD: Don't send immediately (blocks request)
foreach ($parents as $parent) {
    MessageDelivery::sms()
        ->text('...')
        ->to($parent->phone)
        ->send(); // This blocks!
}
```

### Custom Message Priority

```php
<?php

// High priority - send immediately
MessageDelivery::sms()
    ->text('URGENT: Security alert detected')
    ->to($phone)
    ->priority('high')
    ->send();

// Normal priority (default)
MessageDelivery::sms()
    ->text('Monthly report available')
    ->to($phone)
    ->priority('normal')
    ->send();

// Low priority - send when convenient
MessageDelivery::sms()
    ->text('Marketing information')
    ->to($phone)
    ->priority('low')
    ->send();
```

---

## Error Handling

### Checking Delivery Status

```php
<?php

try {
    $result = MessageDelivery::sms()
        ->text('Hello')
        ->to($phone)
        ->send();

    if ($result->isSuccessful()) {
        Log::info('SMS sent successfully', [
            'provider' => $result->provider,
            'message_id' => $result->message_id,
        ]);
    } elseif ($result->isFailed()) {
        Log::error('SMS failed', [
            'error' => $result->error,
            'status' => $result->status,
        ]);
    } elseif ($result->isQueued()) {
        Log::info('SMS queued for later delivery');
    }
} catch (InvalidArgumentException $e) {
    Log::error('Message configuration error: ' . $e->getMessage());
} catch (Exception $e) {
    Log::error('Unexpected error sending SMS: ' . $e->getMessage());
}
```

### Graceful Degradation

```php
<?php

// Try to send email, fallback to SMS
try {
    $result = MessageDelivery::email()
        ->view('emails.important')
        ->to($parent->email)
        ->with(['data' => $data])
        ->send();

    if ($result->isFailed()) {
        // Email failed, send SMS instead
        Log::warning('Email failed, falling back to SMS', [
            'error' => $result->error,
        ]);
        
        MessageDelivery::sms()
            ->text('Please check email for important update')
            ->to($parent->phone)
            ->send();
    }
} catch (Exception $e) {
    Log::error('Email error: ' . $e->getMessage());
    
    // Fallback to SMS
    MessageDelivery::sms()
        ->text('Please check email for important update')
        ->to($parent->phone)
        ->send();
}
```

### Retry Failed Messages

```php
<?php

// Manually retry failed messages
$failedMessages = DB::table('message_deliveries')
    ->where('status', 'failed')
    ->where('created_at', '>', now()->subDay())
    ->get();

foreach ($failedMessages as $msg) {
    try {
        MessageDelivery::$msg->channel()
            ->text($msg->content)
            ->to($msg->recipient)
            ->send();
            
        // Update status
        DB::table('message_deliveries')
            ->where('id', $msg->id)
            ->update(['status' => 'sent']);
    } catch (Exception $e) {
        Log::warning("Retry failed for message {$msg->id}: {$e->getMessage()}");
    }
}

// Or use console command (built-in)
// php artisan message-delivery:retry-failed
```

---

## Event Handling

### Listen to Message Events

```php
<?php

namespace App\Listeners;

use SchoolPalm\MessageDelivery\Events\MessageSending;
use SchoolPalm\MessageDelivery\Events\MessageSent;
use SchoolPalm\MessageDelivery\Events\MessageFailed;
use SchoolPalm\MessageDelivery\Events\MessageDelivered;

class LogMessageDelivery
{
    public function handle(MessageSending $event)
    {
        Log::info('About to send message', [
            'channel' => $event->message->channel,
            'recipients' => count($event->message->recipients),
        ]);
    }

    public function handle(MessageSent $event)
    {
        Log::info('Message sent successfully', [
            'channel' => $event->result->channel,
            'provider' => $event->result->provider,
        ]);
    }

    public function handle(MessageFailed $event)
    {
        Log::error('Message delivery failed', [
            'channel' => $event->result->channel,
            'error' => $event->result->error,
        ]);
    }

    public function handle(MessageDelivered $event)
    {
        Log::info('Delivery confirmed by provider', [
            'channel' => $event->result->channel,
            'message_id' => $event->result->message_id,
        ]);
    }
}
```

### Register in EventServiceProvider

```php
<?php

protected $listen = [
    MessageSending::class => [
        LogMessageDelivery::class,
        PreventSpamFilter::class,
    ],
    MessageSent::class => [
        LogMessageDelivery::class,
        NotifyAdmin::class,
    ],
    MessageFailed::class => [
        LogMessageDelivery::class,
        AlertDevTeam::class,
    ],
];
```

### Custom Event Listener

```php
<?php

namespace App\Listeners;

use SchoolPalm\MessageDelivery\Events\MessageSent;
use App\Models\Activity;

class RecordDeliveryActivity
{
    public function handle(MessageSent $event)
    {
        Activity::create([
            'user_id' => auth()->id(),
            'tenant_id' => $event->message->context('tenant_id'),
            'action' => 'message_sent',
            'subject_type' => 'message',
            'data' => [
                'channel' => $event->result->channel,
                'provider' => $event->result->provider,
                'recipients' => count($event->message->recipients),
            ],
        ]);
    }
}
```

---

## Testing

### Mock MessageDelivery in Tests

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use SchoolPalm\MessageDelivery\Facades\MessageDelivery;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;

class FeeReminderTest extends TestCase
{
    public function test_fee_reminder_sends_sms()
    {
        MessageDelivery::shouldReceive('sms->text->to->send')
            ->once()
            ->andReturn(DeliveryResult::success('egosms', 'msg_123'));

        // Your code that triggers SMS
        $this->sendFeeReminder($parent);

        // Assertions
        $this->assertDatabaseHas('fee_reminders', [
            'parent_id' => $parent->id,
            'sent' => true,
        ]);
    }

    public function test_failed_sms_notifies_admin()
    {
        MessageDelivery::shouldReceive('send')
            ->once()
            ->andReturn(DeliveryResult::failure('egosms', 'Invalid phone'));

        Event::fake();
        $this->sendFeeReminder($parent);

        Event::assertDispatched(MessageFailed::class);
    }
}
```

### Use Fake Delivery Result

```php
<?php

use SchoolPalm\MessageDelivery\Messages\DeliveryResult;

// Success result
$result = DeliveryResult::success('egosms', 'msg_12345');
assert($result->isSuccessful());

// Failure result
$result = DeliveryResult::failure('twilio', 'Invalid number format');
assert($result->isFailed());

// Queued result
$result = DeliveryResult::queuedResult();
assert($result->isQueued());

// Delivered result
$result = DeliveryResult::deliveredResult('egosms', 'msg_12345');
assert($result->status === 'delivered');
```

### Test Event Dispatching

```php
<?php

use SchoolPalm\MessageDelivery\Facades\MessageDelivery;
use SchoolPalm\MessageDelivery\Events\MessageSent;
use Illuminate\Support\Facades\Event;

public function test_message_sent_event_is_dispatched()
{
    Event::fake();

    MessageDelivery::sms()
        ->text('Test message')
        ->to('256701234567')
        ->send();

    Event::assertDispatched(MessageSent::class, function ($event) {
        return $event->result->channel === 'sms';
    });
}
```

---

## Custom Implementations

### Create Custom Channel

```php
<?php

namespace App\Channels;

use SchoolPalm\MessageDelivery\Channels\Channel;
use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;

class SlackChannel extends Channel
{
    public function name(): string
    {
        return 'slack';
    }

    public function send(Message $message, MessageProvider $provider): DeliveryResult
    {
        // Render message content
        $content = $message->text 
            ?? view($message->view, $message->data)->render();

        // Create message for provider
        $slackMessage = new Message(
            channel: 'slack',
            recipients: $message->recipients, // Slack channel IDs
            text: $content,
        );

        // Execute delivery
        return $provider->send($slackMessage);
    }
}
```

### Create Custom Provider

```php
<?php

namespace App\Providers;

use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use Illuminate\Support\Facades\Http;

class SlackProvider implements MessageProvider
{
    private string $webhookUrl;

    public function __construct(array $config = [])
    {
        $this->webhookUrl = $config['webhook_url'] ?? config('messaging.slack.webhook_url');
    }

    public function name(): string
    {
        return 'slack';
    }

    public function channel(): string
    {
        return 'slack';
    }

    public function configured(): bool
    {
        return !empty($this->webhookUrl);
    }

    public function metadata(): array
    {
        return [
            'rate_limit' => 'unlimited',
            'supports_templates' => true,
            'delivery_speed' => 'instant',
        ];
    }

    public function send(Message $message): DeliveryResult
    {
        try {
            $response = Http::post($this->webhookUrl, [
                'channel' => $message->recipients[0],
                'text' => $message->text,
            ]);

            if ($response->successful()) {
                return DeliveryResult::success('slack', uniqid());
            }

            return DeliveryResult::failure('slack', $response->body());
        } catch (Exception $e) {
            return DeliveryResult::failure('slack', $e->getMessage());
        }
    }
}
```

### Create Provider Factory

```php
<?php

namespace App\Factories;

use SchoolPalm\MessageDelivery\Contracts\ProviderFactory;
use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use App\Providers\SlackProvider;

class SlackFactory implements ProviderFactory
{
    public function name(): string
    {
        return 'slack';
    }

    public function channel(): string
    {
        return 'slack';
    }

    public function create(array $configuration = []): MessageProvider
    {
        return new SlackProvider($configuration);
    }
}
```

### Register Custom Channel in Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SchoolPalm\MessageDelivery\Registry\ChannelRegistry;
use SchoolPalm\MessageDelivery\Registry\ProviderRegistry;
use App\Channels\SlackChannel;
use App\Factories\SlackFactory;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Register custom channel
        app(ChannelRegistry::class)->register(
            'slack',
            new SlackChannel()
        );

        // Register custom provider factory
        app(ProviderRegistry::class)->register(
            'slack',
            new SlackFactory()
        );

        // Now available as:
        // MessageDelivery::slack()->text('...')->to($channel)->send()
    }
}
```

### Implement Tenant Provider Settings

```php
<?php

namespace App\Messaging;

use SchoolPalm\MessageDelivery\Contracts\TenantProviderSettings;
use App\Models\Tenant;

class TenantMessagingSettings implements TenantProviderSettings
{
    public function providerFor(string $channel): ?string
    {
        $tenant = auth()->user()->tenant;
        
        return $tenant->getSetting(
            "messaging.channels.{$channel}.provider"
        );
    }

    public function configurationFor(string $channel, string $provider): array
    {
        $tenant = auth()->user()->tenant;
        
        return $tenant->getSetting(
            "messaging.channels.{$channel}.providers.{$provider}",
            []
        );
    }

    public function enabled(string $channel, string $provider): bool
    {
        $config = $this->configurationFor($channel, $provider);
        
        // Provider is enabled if it has configuration
        return count($config) > 0;
    }
}
```

---

## Best Practices Summary

1. **Always queue bulk operations** - Use `queue()` for batch messages
2. **Use context for multi-tenant** - Attach tenant_id and school_id to all messages
3. **Implement failover** - Set multiple providers as fallback
4. **Listen to events** - Track delivery for auditing and analytics
5. **Handle errors gracefully** - Check `isSuccessful()` or use try-catch
6. **Test with mocks** - Don't actually send messages in tests
7. **Monitor delivery** - Review message_deliveries table for failed messages
8. **Set provider explicitly for critical messages** - Don't rely on default for critical alerts
9. **Delay non-urgent messages** - Use `delay()` to spread load
10. **Document custom providers** - Include configuration requirements for team

