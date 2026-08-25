## [3.0.0] - 2026-08-25

### Added
- `ITPalert\Web2sms\Contracts\Client` interface (`send`, `get`, `delete`, `balance`),
  implemented by the real `Client`. The container and the facade resolve this now,
  so something other than the live API can stand behind them.
- `ITPalert\Web2sms\Testing\Web2smsFake`: records messages instead of delivering
  them, with `assertSent`, `assertSentTo`, `assertNotSent`, `assertNothingSent`,
  `assertSentCount` and `sent()`. Still runs `SMS::verifyMessage()`, so a fake
  cannot accept a message the API would reject.
- `services.web2sms.driver`: `api` sends for real, `fake`/`array`/`null` record and
  swallow, `log` records and writes what it swallowed to the PSR-3 logger.
- `Web2sms::fake()` and `Web2sms::isFake()` on the facade.

### Changed
- **The driver defaults to `fake` under the testing environment.** Sending a text
  costs money per attempt and arrives on a real handset, so a suite that has never
  thought about SMS must not be able to send one. Set `driver` explicitly to opt
  back in.
- A missing `services.web2sms` config is no longer fatal while testing; it yields
  the fake rather than a `RuntimeException`.

### Breaking
- The container binds `Contracts\Client`, not the concrete `Client`, and the two
  are deliberately **not** aliased. An alias resolves ahead of bindings, so it
  silently swallows a test's attempt to bind a stub and sends to the live API
  instead. Type-hint and bind `Contracts\Client`.
- `itpalert/web2sms-notification-channel` must be updated in step:
  `Web2smsChannel::__construct()` type-hints the concrete `Client` and will not
  accept the fake.

## [2.2.1] - 2024-12-21

### Fixed
- Fixed Laravel dependency injection error when injecting `Client` class directly
- Updated `Web2smsServiceProvider` to properly bind `Client::class` to the container
- Fixed facade accessor to return correct class for dependency injection

### Changed
- Reorganized tests into `Unit` and `Integration` directories
- Improved code quality with proper PHPDoc annotations
- Standardized HTTP request calls in `Client` class
- Updated `phpunit.xml` to reflect new test structure

### Added
- Account type validation in `Basic` credentials class
- Throws `InvalidArgumentException` for invalid account types

### Improved
- Better type safety across the codebase
- Cleaner code with null coalescing assignment operator
- Enhanced documentation and comments