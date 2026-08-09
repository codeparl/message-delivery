# TODO: Notification Test - Email + In-App Channels with Mailpit

## Steps
- [x] 1. Change email provider config in `NotificationEngineTest.php` from `['mailer' => 'array']` to `['mailer' => 'mailpit']`
- [x] 2. Add Mailpit availability guard (fsockopen on 127.0.0.1:1025) in `beforeEach`
- [x] 3. Enhance TEST 9 (multi-channel) to assert email uses `laravel-mail` provider with `mailer` = `mailpit`
- [x] 4. Run the Notification tests to confirm they pass with Mailpit running
- [x] 5. Fix placeholder rendering - ensure template variables (`{{ name }}`, `{{ title }}`) are fully rendered in the sent message body (added `name` to event data + assertions that no `{{ }}` tokens remain)
