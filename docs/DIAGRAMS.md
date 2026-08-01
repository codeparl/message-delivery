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
