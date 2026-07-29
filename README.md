# SchoolPalm Message Delivery API Reference

A Laravel package providing a unified API for sending messages through multiple channels and providers.

## Design Philosophy

Applications communicate with the Message Delivery package without knowing the underlying provider.

```
Application
      |
      v
MessageDelivery API
      |
      v
Channel
      |
      v
Provider
      |
      v
External Service
```

## Basic Message Sending

### SMS View Message

```
MessageDelivery::sms()
    ->view('messages.fee-reminder')
    ->to($parent->phone)
    ->with([
        'student' => $student,
        'amount'  => $balance
    ])
    ->send();
```

### Email View Message

```
MessageDelivery::email()
    ->view('emails.invoice')
    ->to($parent->email)
    ->with([
        'invoice'=>$invoice
    ])
    ->send();
```

### Push Notification

```
MessageDelivery::push()
    ->view('notifications.attendance')
    ->to($deviceToken)
    ->with([
        'student'=>$student
    ])
    ->send();
```

### WhatsApp

```
MessageDelivery::whatsapp()
    ->view('whatsapp.fee-reminder')
    ->to($phone)
    ->with([
        'amount'=>50000
    ])
    ->send();
```

## Raw Messages

```
MessageDelivery::sms()
    ->text('Your fee balance is UGX 50,000')
    ->to($phone)
    ->send();
```

## Recipients

### Single Recipient

```
MessageDelivery::sms()
    ->text('Hello')
    ->to($phone)
    ->send();
```

### Multiple Recipients

```
MessageDelivery::sms()
    ->text('School announcement')
    ->to([
        $phone1,
        $phone2
    ])
    ->send();
```

## Message Data

Variables are passed using `with()`.

```
MessageDelivery::email()
    ->view('emails.results')
    ->with([
        'student'=>$student,
        'results'=>$results
    ])
    ->send();
```

## Database Templates

Templates managed by administrators can be used instead of application views.

```
MessageDelivery::sms()
    ->template('fee_reminder')
    ->to($parent)
    ->with([
        'name'=>$student->name,
        'amount'=>$balance
    ])
    ->send();
```

## Multiple Channels

```
MessageDelivery::channels([
        'sms',
        'email',
        'push'
    ])
    ->view('messages.exam-results')
    ->to($parent)
    ->with([
        'student'=>$student
    ])
    ->send();
```

## Queue Delivery

```
MessageDelivery::sms()
    ->view('messages.notice')
    ->to($parents)
    ->queue();
```

## Delayed Delivery

```
MessageDelivery::sms()
    ->view('messages.reminder')
    ->to($parent)
    ->delay(
        now()->addDay()
    )
    ->send();
```

## Provider Selection

Normally providers are selected from tenant settings.

A provider can be manually selected:

```
MessageDelivery::sms()
    ->provider('egosms')
    ->view('messages.notice')
    ->to($phone)
    ->send();
```

## Provider Failover

```
MessageDelivery::sms()
    ->view('messages.alert')
    ->failover([
        'africastalking',
        'egosms',
        'twilio'
    ])
    ->send();
```

## Priority

```
MessageDelivery::sms()
    ->view('messages.emergency')
    ->priority('high')
    ->send();
```

## Delivery Result

```
$result = MessageDelivery::sms()
    ->text('Hello')
    ->to($phone)
    ->send();


$result->id();

$result->status();

$result->provider();

$result->providerMessageId();
```

## Checking Delivery Status

```
MessageDelivery::status($messageId);
```

## Adding Custom Providers

External packages can register providers.

```
MessageDelivery::extend(
    'uganda_sms',
    UgandaSmsProvider::class
);
```

## Provider Discovery

```
MessageDelivery::providers()
    ->sms()
    ->available();
```

Example response:

```
[
 {
   "name":"EgoSMS",
   "channel":"sms",
   "fields":[
      "username",
      "api_key"
   ]
 }
]
```

## Tenant Provider Resolution

The application does not specify credentials.

```
MessageDelivery::sms()
    ->view('messages.notice')
    ->to($parent)
    ->send();
```

The package automatically resolves:

```
Current Tenant
      |
Provider Settings
      |
Credentials
      |
Provider
```

## Events

```
MessageSending

MessageSent

MessageFailed

MessageDelivered

DeliveryReceiptReceived
```

Example:

```
Event::listen(
    MessageDelivered::class,
    function($event){

    }
);
```

## Supported Builder Methods

| Method     | Description                   |
| ---------- | ----------------------------- |
| view()     | Use application template view |
| template() | Use database template         |
| text()     | Send raw message              |
| to()       | Set recipients                |
| with()     | Pass variables                |
| provider() | Force provider                |
| queue()    | Queue delivery                |
| delay()    | Delay sending                 |
| priority() | Set delivery priority         |
| send()     | Execute delivery              |