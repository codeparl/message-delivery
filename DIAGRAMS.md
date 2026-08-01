# SchoolPalm Message Delivery - Visual Architecture Diagrams

This document contains Mermaid diagrams showing the internal architecture, data flow, and relationships in the Message Delivery package.

## 1. System Architecture Overview

```mermaid
graph TD
    A["Application Code"] -->|MessageDelivery::sms()<br/>MessageDelivery::email()<br/>etc.| B["MessageDelivery Facade"]
    B -->|Instantiate| C["ChannelMessageBuilder<br/>or<br/>MultiChannelMessageBuilder"]
    C -->|call send| D["Message"]
    D -->|dispatch| E["MessageManager"]
    E -->|coordinate| F["DeliveryManager"]
    F -->|resolve channel| G["ChannelRegistry"]
    G -->|get| H["Channel Instance<br/>SMS/Email/Push/WhatsApp"]
    F -->|resolve provider| I["ProviderManager"]
    I -->|check config| J["TenantProviderSettings"]
    I -->|get provider| K["ProviderRegistry"]
    K -->|instantiate| L["MessageProvider<br/>EgoSMS/Twilio<br/>Firebase/Meta/etc."]
    H -->|execute| L
    L -->|call external API| M["External Service"]
    L -->|return| N["DeliveryResult"]
    N -->|dispatch event| E
    E -->|return| A
    style A fill:#e1f5ff
    style B fill:#fff3e0
    style D fill:#f3e5f5
    style N fill:#e8f5e9
```

## 2. Message Flow - Request to Delivery

```mermaid
sequenceDiagram
    participant App as Application
    participant Builder as ChannelMessageBuilder
    participant Manager as MessageManager
    participant DeliveryMgr as DeliveryManager
    participant Channel as Channel
    participant Provider as Provider
    participant API as External API
    participant Db as Database

    App->>Builder: sms().text().to().send()
    Builder->>Builder: Assemble Message object
    Builder->>Manager: dispatch(Message)
    Manager->>Manager: Dispatch MessageSending event
    Manager->>DeliveryMgr: deliver(Message)
    DeliveryMgr->>DeliveryMgr: Resolve Channel from Registry
    DeliveryMgr->>DeliveryMgr: Resolve Provider from ProviderManager
    DeliveryMgr->>Channel: send(Message, Provider)
    Channel->>Channel: Validate configuration
    Channel->>Channel: Format message for API
    Channel->>Provider: execute delivery
    Provider->>API: HTTP POST/API Call
    API-->>Provider: Response (status, message_id)
    Provider-->>Channel: DeliveryResult
    Channel-->>DeliveryMgr: DeliveryResult
    DeliveryMgr->>Db: Save message_deliveries record
    DeliveryMgr->>Manager: Return result
    Manager->>Manager: Dispatch MessageSent/MessageFailed event
    Manager-->>App: DeliveryResult
    App->>App: Handle result
```

## 3. Builder Pattern - Method Chaining

```mermaid
graph LR
    A["MessageDelivery::sms()"] -->|ChannelMessageBuilder| B["text/view/template()"]
    B -->|return self| C["to()"]
    C -->|return self| D["with()"]
    D -->|return self| E["provider()"]
    E -->|return self| F["priority()"]
    F -->|return self| G["failover()"]
    G -->|return self| H["queue()"]
    H -->|return self| I["delay()"]
    I -->|return self| J["send()/queue()"]
    J -->|DeliveryResult| K["Application Result Handling"]
    style A fill:#fff3e0
    style J fill:#e8f5e9
    style K fill:#e1f5ff
```

## 4. Channel-Provider Relationship

```mermaid
graph TD
    SMS["SMS Channel"]
    Email["Email Channel"]
    Push["Push Channel"]
    WhatsApp["WhatsApp Channel"]
    
    SMS -->|multiple providers| EgoSMS["EgoSMS"]
    SMS -->|multiple providers| Twilio1["Twilio"]
    SMS -->|multiple providers| Africa["Africa's Talking"]
    
    Email -->|multiple providers| SES["AWS SES"]
    Email -->|multiple providers| Mailgun["Mailgun"]
    Email -->|multiple providers| Postmark["Postmark"]
    
    Push -->|single provider| Firebase["Firebase CMG"]
    
    WhatsApp -->|multiple providers| Meta["Meta WhatsApp Business"]
    WhatsApp -->|multiple providers| Twilio2["Twilio"]
    
    EgoSMS -->|HTTP API| EgoAPI["EgoSMS API"]
    Twilio1 -->|HTTP API| TwilioAPI1["Twilio API"]
    Africa -->|HTTP API| AfricaAPI["Africa's Talking API"]
    SES -->|AWS SDK| SESAPI["AWS SES"]
    Mailgun -->|HTTP API| MailgunAPI["Mailgun API"]
    Postmark -->|HTTP API| PostmarkAPI["Postmark API"]
    Firebase -->|HTTP API| FirebaseAPI["Firebase API"]
    Meta -->|HTTP API| MetaAPI["Meta API"]
    Twilio2 -->|HTTP API| TwilioAPI2["Twilio API"]
    
    style SMS fill:#bbdefb
    style Email fill:#bbdefb
    style Push fill:#bbdefb
    style WhatsApp fill:#bbdefb
```

## 5. Dependency Injection & Service Container

```mermaid
graph TD
    SP["MessageDeliveryServiceProvider<br/>Register & Boot"]
    
    SP -->|bind| CR["ChannelRegistry"]
    SP -->|bind| PR["ProviderRegistry"]
    SP -->|bind| DR["DefinitionRegistry"]
    SP -->|bind| MM["MessageManager"]
    SP -->|bind| DM["DeliveryManager"]
    SP -->|bind| PMgr["ProviderManager"]
    SP -->|bind| CM["ChannelManager"]
    
    CR -->|register| SMS["SMS Channel"]
    CR -->|register| Email["Email Channel"]
    CR -->|register| Push["Push Channel"]
    CR -->|register| WA["WhatsApp Channel"]
    
    PR -->|register factory| EgoFactory["EgoSMS Factory"]
    PR -->|register factory| TwilioFactory["Twilio Factory"]
    PR -->|register factory| FirebaseFactory["Firebase Factory"]
    
    DR -->|store definitions| Definitions["Provider<br/>Configuration<br/>Definitions"]
    
    MM -->|uses| DM
    DM -->|uses| CR
    DM -->|uses| PMgr
    PMgr -->|uses| PR
    PMgr -->|uses| Definitions
    
    style SP fill:#fff3e0
    style CR fill:#e8f5e9
    style PR fill:#e8f5e9
    style MM fill:#f3e5f5
```

## 6. Message Data Object Structure

```mermaid
graph TD
    Message["Message"]
    Message -->|channel| Channel["string<br/>sms/email/push/whatsapp"]
    Message -->|recipients| Recipients["array<br/>phone/email/token"]
    Message -->|view| View["string | null<br/>blade template path"]
    Message -->|template| Template["string | null<br/>database template name"]
    Message -->|text| Text["string | null<br/>raw message content"]
    Message -->|data| Data["array<br/>template variables"]
    Message -->|provider| Provider["string | null<br/>specific provider"]
    Message -->|priority| Priority["string | null<br/>low/normal/high"]
    Message -->|context| Context["array<br/>tenant_id, school_id, module"]
    Message -->|queueOptions| QueueOpts["QueueOptions<br/>queue?: bool<br/>delay?: DateTime"]
    Message -->|metadata| Metadata["array | null<br/>custom metadata"]
```

## 7. Provider Resolution Flow

```mermaid
graph TD
    A["Message received<br/>with provider config"]
    
    A -->|provider specified?| B{Provider in<br/>Message?}
    
    B -->|yes| C["Use explicit<br/>provider"]
    B -->|no| D{Provider in<br/>Tenant Settings?}
    
    D -->|yes| E["Resolve from<br/>TenantProviderSettings"]
    D -->|no| F["Error: No provider<br/>configured"]
    
    C -->|get from| G["ProviderRegistry"]
    E -->|get from| G
    
    G -->|instantiate<br/>via factory| H["Provider Factory"]
    H -->|create| I["MessageProvider<br/>Instance"]
    
    I -->|check| J["Provider::configured()"]
    
    J -->|true| K["Return Provider"]
    J -->|false| L["Error: Provider<br/>not configured"]
    
    K -->|pass to| M["Channel::send()"]
    
    style A fill:#e1f5ff
    style K fill:#e8f5e9
    style F fill:#ffebee
    style L fill:#ffebee
```

## 8. Event Lifecycle

```mermaid
graph TD
    A["Message Dispatch Start"]
    B["MessageSending Event"]
    C["Delivery Attempted"]
    D{Delivery<br/>Successful?}
    E["MessageSent Event"]
    F["Result recorded<br/>to database"]
    G["MessageFailed Event"]
    H["MessageQueued Event"]
    
    A -->|dispatch| B
    B -->|continue| C
    C -->|result check| D
    D -->|yes| E
    D -->|no| G
    D -->|queued| H
    E -->|record| F
    G -->|record| F
    H -->|record| F
    
    I["(Later) Delivery confirmed<br/>by webhook"]
    I -->|dispatch| J["MessageDelivered Event"]
    J -->|update| F
    
    K["Delivery receipt<br/>webhook received"]
    K -->|dispatch| L["DeliveryReceiptReceived Event"]
    
    style A fill:#e1f5ff
    style E fill:#e8f5e9
    style G fill:#ffebee
    style H fill:#fff3e0
    style J fill:#e8f5e9
```

## 9. Multi-Channel Message Flow

```mermaid
graph TD
    A["MessageDelivery::channels(['sms', 'email', 'push'])"]
    B["MultiChannelMessageBuilder"]
    C["Call send()"]
    D["Create Message for each channel"]
    
    D -->|channel: sms| E["SMS Message"]
    D -->|channel: email| F["Email Message"]
    D -->|channel: push| G["Push Message"]
    
    E -->|dispatch| SMS["DeliveryManager<br/>→ SMS Channel<br/>→ Provider"]
    F -->|dispatch| Email["DeliveryManager<br/>→ Email Channel<br/>→ Provider"]
    G -->|dispatch| Push["DeliveryManager<br/>→ Push Channel<br/>→ Provider"]
    
    SMS -->|return| SMS_Result["DeliveryResult"]
    Email -->|return| Email_Result["DeliveryResult"]
    Push -->|return| Push_Result["DeliveryResult"]
    
    SMS_Result -->|collect| Results["Array of Results"]
    Email_Result -->|collect| Results
    Push_Result -->|collect| Results
    
    Results -->|return to| A
    
    style A fill:#fff3e0
    style Results fill:#e8f5e9
```

## 10. Queue & Retry Flow

```mermaid
graph TD
    A["Message::queue()"]
    B["Serialize message to queue"]
    C["message_deliveries table<br/>status: queued"]
    D["Queue Worker processes"]
    E["Dequeue message"]
    F["Attempt delivery"]
    G{Delivery<br/>Successful?}
    H["Update status: sent"]
    I["Update status: failed"]
    J{Retries<br/>remaining?}
    K["Update status: retry_pending<br/>Schedule retry"]
    L["Max retries exceeded<br/>status: failed"]
    M["Dispatch MessageFailed event"]
    
    A -->|save to queue| B
    B -->|create| C
    D -->|poll| C
    C -->|dequeue| E
    E -->|execute| F
    F -->|check| G
    G -->|yes| H
    G -->|no| I
    I -->|check| J
    J -->|yes| K
    J -->|no| L
    L -->|dispatch| M
    H -->|dispatch| N["MessageSent event"]
    
    K -->|later| D
    
    style A fill:#fff3e0
    style C fill:#f3e5f5
    style H fill:#e8f5e9
    style M fill:#ffebee
    style N fill:#e8f5e9
```

## 11. Context Propagation

```mermaid
graph TD
    A["Application"]
    B["MessageDelivery::withContext(context)"]
    C["New MessageDelivery instance<br/>with context stored"]
    D["sms()->text()->to()->send()"]
    E["Message object created<br/>context attached"]
    F["MessageManager receives"]
    G["DeliveryManager receives"]
    H["Channel receives"]
    I["Provider receives"]
    J["External API call<br/>(context available for logging)"]
    K["DeliveryResult created<br/>context included"]
    L["Database record<br/>context saved"]
    M["Events dispatched<br/>context available"]
    
    A -->|call| B
    B -->|return| C
    C -->|call| D
    D -->|create| E
    E -->|pass| F
    F -->|pass| G
    G -->|pass| H
    H -->|pass| I
    I -->|pass| J
    J -->|return| K
    K -->|save| L
    K -->|dispatch| M
    
    style A fill:#e1f5ff
    style E fill:#f3e5f5
    style L fill:#e8f5e9
    style M fill:#e8f5e9
```

## 12. Extension Points - Custom Channel

```mermaid
graph TD
    A["Create Custom Channel"]
    B["Extend Channel abstract class"]
    C["Implement required methods:<br/>name(): string<br/>send(Message, Provider): DeliveryResult"]
    D["Register in ServiceProvider<br/>ChannelRegistry::register()"]
    E["Create Custom Provider<br/>Implement MessageProvider"]
    F["Register Provider Factory<br/>ProviderRegistry::register()"]
    G["User can now use:<br/>MessageDelivery::slack()->send()"]
    
    A -->|implement| B
    B -->|code| C
    C -->|boot()| D
    D -->|requires| E
    E -->|register| F
    F -->|enable| G
    
    style A fill:#fff3e0
    style G fill:#e8f5e9
    style D fill:#f3e5f5
```

## 13. Database Schema

```mermaid
erDiagram
    MESSAGE_DELIVERIES {
        bigint id
        string channel
        string provider
        string recipient
        text view
        string template
        text content
        json data
        json context
        string status
        string provider_message_id
        text error
        json metadata
        timestamp sent_at
        timestamp delivered_at
        timestamp created_at
        timestamp updated_at
    }
```

## 14. Status State Machine

```mermaid
stateDiagram-v2
    [*] --> pending: Message created
    
    pending --> sent: Immediate delivery successful
    pending --> failed: Immediate delivery failed
    pending --> queued: Message queued for later
    pending --> queued_scheduled: Message delayed
    
    queued --> sent: Queue worker succeeds
    queued --> retry_pending: Queue worker fails, retries remain
    queued --> failed: Max retries exceeded
    
    queued_scheduled --> queued: Delay elapsed
    
    retry_pending --> sent: Retry succeeds
    retry_pending --> failed: Retry failed, max retries exceeded
    
    sent --> delivered: Webhook delivery confirmation
    
    failed --> [*]
    delivered --> [*]
```

## 15. Configuration Resolution Hierarchy

```mermaid
graph TD
    A["Need to send message"]
    B["Check message provider<br/>parameter"]
    C{Provider<br/>specified?}
    
    C -->|yes| D["Use specified provider"]
    C -->|no| E["Check TenantProviderSettings"]
    E -->|configured| F["Use tenant provider"]
    E -->|not configured| G["Error: No provider"]
    
    D -->|get config| H["TenantProviderSettings<br/>::configurationFor()"]
    F -->|get config| H
    H -->|validate| I["Provider fields filled?"]
    I -->|no| J["Error: Incomplete config"]
    I -->|yes| K["Provider ready for use"]
    K -->|execute| L["Delivery attempt"]
    
    style D fill:#e8f5e9
    style F fill:#fff3e0
    style K fill:#e8f5e9
    style G fill:#ffebee
    style J fill:#ffebee
```

---

## Diagram Legend

- **Blue boxes** (`#e1f5ff`): Application entry points
- **Green boxes** (`#e8f5e9`): Success states / final results
- **Orange boxes** (`#fff3e0`): Configuration / Setup
- **Purple boxes** (`#f3e5f5`): Message / Data objects
- **Red boxes** (`#ffebee`): Error states
- **Rounded diamonds** (`{}`): Decision points
- **Arrows** (`→`, `-->`): Flow direction

---

## Using These Diagrams

1. **System Overview**: Start with Diagram #1 to understand the high-level architecture
2. **Message Flow**: See Diagram #2 for a sequence of how messages are delivered
3. **Builder Usage**: Reference Diagram #3 when learning the fluent API
4. **Channel-Provider Mapping**: Use Diagram #4 to understand which providers support which channels
5. **Provider Resolution**: Study Diagram #7 to understand how providers are selected
6. **Event System**: Review Diagram #8 to understand when events are dispatched
7. **Extending**: Check Diagrams #12 for custom channel/provider patterns
8. **Status Tracking**: Use Diagram #14 to understand message state transitions

