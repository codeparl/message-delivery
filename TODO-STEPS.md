# Implementation Steps

## Fix 1: Add `title()` method to `ChannelMessageBuilder`
- [x] Add `title(string $title)` method to `src/Builders/ChannelMessageBuilder.php`
  - Sets `$this->data['title'] = $title`
  - Returns `$this` for fluent chaining

## Fix 2: Update `ProviderConfigurationTest`
- [x] Change `it('returns empty for unregistered channel')` test
  - Use `'nonexistent'` instead of `'push'` since push is now a registered channel

## Fix 3: Resolve provider name in queued delivery records
- [x] Update `DatabaseDeliveryRecorder` to resolve provider via `TenantProviderSettings` when not set on message
  - Added `resolveProvider()` method
  - Constructor now accepts optional `TenantProviderSettings`
- [x] Update `MessageDeliveryServiceProvider` to inject `TenantProviderSettings` into recorder (defensively)

## Verify
- [ ] Run `vendor/bin/pest` — all tests pass, no regressions
- [ ] Update `TODO.md` to check off final item

