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

---

