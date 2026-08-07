# SchoolPalm Message Delivery - Documentation Index

Welcome! This is your complete guide to understanding and using the SchoolPalm Message Delivery package. The documentation is organized into comprehensive guides designed for different learning styles and use cases.

## 📚 Documentation Files

### 1. [ARCHITECTURE.md](./ARCHITECTURE.md) - Complete Reference Guide
**Best for:** Understanding the package deeply, learning the philosophy, and API reference

### 2. [DIAGRAMS.md](./DIAGRAMS.md) - Visual Architecture Guide
**Best for:** Visual learners, understanding system structure, and debugging

### 3. [EXAMPLES.md](./EXAMPLES.md) - Practical Code Examples
**Best for:** Learning by doing, copy-paste ready code, and common patterns

### 4. [PROVIDER_CONFIGURATION.md](./PROVIDER_CONFIGURATION.md) - Provider Configuration Fields
**Best for:** Understanding and using the provider configuration field APIs to initialize provider settings in your own storage

---

## 🎯 Quick Navigation

**...send an SMS**
→ [EXAMPLES.md - Simple SMS](./EXAMPLES.md#simple-sms)

**...understand the architecture**
→ [ARCHITECTURE.md - Internal Architecture](./ARCHITECTURE.md#internal-architecture) + [DIAGRAMS.md - System Overview](./DIAGRAMS.md#1-system-architecture-overview)

**...see provider selection**
→ [DIAGRAMS.md - Provider Resolution](./DIAGRAMS.md#7-provider-resolution-flow)

---

## 🚀 Getting Started (5 minutes)

### Step 1: Install
```bash
composer require schoolpalm/message-delivery
php artisan vendor:publish --tag=message-delivery-migrations
php artisan migrate
```

### Step 2: Send Your First SMS
```php
use SchoolPalm\MessageDelivery\Facades\MessageDelivery;

MessageDelivery::sms()
    ->text('Hello, your fee balance is UGX 50,000')
    ->to('256701234567')
    ->send();
```

---

## 📁 Docs location

All documentation files are available in this package's docs folder:

- [ARCHITECTURE.md](./ARCHITECTURE.md)
- [DIAGRAMS.md](./DIAGRAMS.md)
- [EXAMPLES.md](./EXAMPLES.md)
- [PROVIDER_CONFIGURATION.md](./PROVIDER_CONFIGURATION.md)

