# Notification Engine Implementation - TODO

## Contracts
- [x] Create `src/Notification/Contracts/NotificationEngine.php`
- [x] Create `src/Notification/Contracts/EventResolver.php`
- [x] Create `src/Notification/Contracts/RecipientResolver.php`
- [x] Create `src/Notification/Contracts/ChannelResolver.php`
- [x] Create `src/Notification/Contracts/PreferenceResolver.php`
- [x] Create `src/Notification/Contracts/TemplateResolver.php`
- [x] Create `src/Notification/Contracts/LanguageResolver.php`
- [x] Create `src/Notification/Contracts/ScheduleResolver.php`
- [x] Create `src/Notification/Contracts/RetryResolver.php`
- [x] Create `src/Notification/Contracts/PriorityResolver.php`

## DTOs
- [x] Create `src/Notification/DTO/NotificationEvent.php`
- [x] Create `src/Notification/DTO/NotificationContext.php`
- [x] Create `src/Notification/DTO/NotificationDecision.php`
- [x] Create `src/Notification/DTO/NotificationDispatch.php`
- [x] Create `src/Notification/DTO/RetryPolicy.php`

## Support
- [x] Create `src/Notification/Support/NotificationCollection.php`
- [x] Create `src/Notification/Support/NotificationResult.php`

## Resolvers (Null implementations)
- [x] Create `src/Notification/Resolvers/NullEventResolver.php`
- [x] Create `src/Notification/Resolvers/NullRecipientResolver.php`
- [x] Create `src/Notification/Resolvers/NullPreferenceResolver.php`
- [x] Create `src/Notification/Resolvers/NullChannelResolver.php`
- [x] Create `src/Notification/Resolvers/NullLanguageResolver.php`
- [x] Create `src/Notification/Resolvers/NullTemplateResolver.php`
- [x] Create `src/Notification/Resolvers/NullPriorityResolver.php`
- [x] Create `src/Notification/Resolvers/NullScheduleResolver.php`
- [x] Create `src/Notification/Resolvers/NullRetryResolver.php`

## Engine / Manager / Facade
- [x] Create `src/Notification/Engine/NotificationEngine.php`
- [x] Create `src/Notification/NotificationManager.php`
- [x] Create `src/Facades/Notification.php`

## Modifications
- [x] Update `src/Builders/MultiChannelMessageBuilder.php` — add `context()` + queue option methods
- [x] Update `src/Builders/QueueOptionsBuilder.php` — add `hasConfig()`
- [x] Update `src/MessageDeliveryServiceProvider.php` — register resolver bindings, engine, manager
- [x] Update `config/message-delivery.php` — add `notification` config block
- [x] Update `composer.json` — add `Notification` facade alias

## Tests
- [x] Create `tests/Feature/Notification/NotificationDispatchTest.php`
- [x] Create `tests/Feature/Notification/NotificationEngineTest.php`
- [x] Create `tests/Feature/Notification/NotificationResolverTest.php`

## Verify
- [x] Run `php vendor/bin/pest --filter=Notification` — all notification tests pass (43 passed)
- [x] Run full `php vendor/bin/pest` — no regressions (249 passed, 776 assertions)

