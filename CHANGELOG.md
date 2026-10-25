# Changelog

## 1.1.0 (2026-10-25)

Rewritten against the current v3 API. If you installed 1.0.0, replace your code with the examples in the README: 1.0.0 was an early draft that didn't match the API.

### Fixed
- Package name in `composer.json` is back to `circlepandaa/pandascrow-php-sdk`, the name it is registered under on Packagist. The previous rename stopped Packagist from picking up new versions.
- `Webhook::verify()` now checks the signature against the raw request body. It used to re-encode the payload, which produced a different signature for any event containing URLs or non-ASCII text, so valid webhooks were rejected.
- `bank->transfer()` generates idempotency keys short enough for the transfer provider (25 characters maximum).
- `wallet->payout()` sends the destination as JSON so it can be read back from `payouts->all()`.
- Responses that have PHP notices printed before the JSON are still parsed instead of failing as "invalid JSON".
- Removed the unused `vlucas/phpdotenv` dependency.
- `examples/quickstart.php` loads Composer's autoloader.

### Added
- `payouts` resource: `create`, `all`, `settlementAccounts`.
- Escrow: `createRecurring`, `create`, `regeneratePayment`, `regenerateMilestonePayment`, `disputes`, `joinRecurring`.
- Wallet: `balances`, `accounts`; `transactions` accepts a type filter.
- Bank: `settlementAccount`.
- Virtual accounts: `reallocate`, `deactivate`, `reactivate`; accept any amount, expiry, reference, beneficiary and `metadata`.
- Invoices: `pay`, `cancel`, and `metadata`.
- Payment links: `find`, `isSlugAvailable`, `goLive`, `toggleTip`, `completePayment`, `addMargin`.
- KYC: `phone`, `nuban`, `driversLicense`, `tin`, `cac`, `searchCac`, `email`.
- Auth: `verifyLoginOtp`.
- `metadata` support documented for escrows, invoices and virtual accounts.
- Pluggable HTTP transport (`Pandascrow\Http\Transport`), `headers` and `connect_timeout` options, a `User-Agent` header, and getters on `PandascrowException`.
- PHPUnit test suite covering every endpoint helper and webhook verification.

### Changed
- `wallet->balance()` is deprecated in favour of `balances()` (the API has no date range on balances).
- `wallet->fund()` was removed: `/wallet/deposit` only accepts Pandascrow's internal key.
- `virtualAccounts->createDynamic()` / `createStatic()` now take `($uuid, $accountName, $amount = null, $options = [])`.
- `escrow->complete()` / `completeMilestone()` send IDs as integers.
