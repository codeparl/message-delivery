# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]
- Initial scaffold

## [1.0.0] - 2025-01-15

### Added
- **Notification Engine**: Full orchestration layer with resolver interfaces and fluent dispatch API
  - `EventResolver`, `RecipientResolver`, `PreferenceResolver`, `ChannelResolver`
  - `LanguageResolver`, `TemplateResolver`, `PriorityResolver`, `ScheduleResolver`, `RetryResolver`
  - `NotificationEvent`, `NotificationContext`, `NotificationDecision`, `NotificationDispatch` DTOs
  - `NotificationCollection`, `NotificationResult` support classes
  - `Null` resolver implementations for all interfaces
  - `NotificationManager` with `Notification::dispatch()` and `Notification::event()` fluent API
  - `Notification` facade registered in `composer.json` aliases
- **Publishing Readiness**:
  - `mergeConfigFrom()` in service provider so `config('message-delivery.*')` works out of the box
  - Publishable config via `php artisan vendor:publish --tag=message-delivery-config`
  - Publishable migrations via `php artisan vendor:publish --tag=message-delivery-migrations`
- **Comprehensive Documentation**: Full README.md covering architecture, all channels, providers, Notification Engine, resolver extension, testing, and publishing
- **225+ tests** (712 assertions) covering all channels, providers, delivery tracking, Notification Engine, default resolvers, and engine orchestration
