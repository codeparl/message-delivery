# WhatsApp Channel Implementation - TODO

## Goal
Implement WhatsApp channel following exactly the same architecture as SMS.

## Steps
### Source Files
- [ ] Create `src/Channels/WhatsAppChannel.php`
- [ ] Create `src/Providers/WhatsApp/Meta/MetaWhatsAppDefinition.php`
- [ ] Create `src/Providers/WhatsApp/Meta/MetaWhatsAppFactory.php`
- [ ] Create `src/Providers/WhatsApp/Meta/MetaWhatsAppProvider.php`
- [ ] Create `src/Providers/WhatsApp/Twilio/TwilioWhatsAppDefinition.php`
- [ ] Create `src/Providers/WhatsApp/Twilio/TwilioWhatsAppFactory.php`
- [ ] Create `src/Providers/WhatsApp/Twilio/TwilioWhatsAppProvider.php`
- [ ] Delete old stub files: `src/Providers/WhatsApp/MetaProvider.php`, `src/Providers/WhatsApp/TwilioProvider.php`
- [ ] Update `src/MessageDeliveryServiceProvider.php` — register WhatsApp channel, providers, definitions

### Test Files
- [ ] Create `tests/Feature/WhatsApp/ProviderResolutionTest.php`
- [ ] Create `tests/Feature/WhatsApp/MetaWhatsAppProviderTest.php`
- [ ] Create `tests/Feature/WhatsApp/TwilioWhatsAppProviderTest.php`
- [ ] Create `tests/Feature/WhatsApp/WhatsAppChannelTest.php`
- [ ] Create `tests/Feature/WhatsApp/WhatsAppConfigurationTest.php`
- [ ] Create `tests/Feature/WhatsApp/WhatsAppFailureTest.php`
- [ ] Create `tests/Feature/WhatsApp/WhatsAppMetadataTest.php`
- [ ] Create `tests/Feature/WhatsApp/WhatsAppAdvancedTest.php`
- [ ] Create `tests/Feature/MessageDeliveryWhatsAppTest.php`

### Verify
- [ ] Run `vendor/bin/pest` — all tests pass
