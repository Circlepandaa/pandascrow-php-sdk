# Pandascrow PHP SDK

Official PHP library for the [Pandascrow v3 API](https://pandascrow.readme.io/): escrow, wallets, payouts, bank transfers, virtual accounts, invoices, payment links, KYC and webhook verification.

- PHP 7.4 or newer, with `ext-curl` and `ext-json`
- No other dependencies

## Install

```bash
composer require circlepandaa/pandascrow-php-sdk
```

## Quick start

```php
require 'vendor/autoload.php';

$ps = new Pandascrow\Client('pk_test_xxx');                          // sandbox (default)
// $ps = new Pandascrow\Client('pk_live_xxx', ['sandbox' => false]); // live

$escrow = $ps->escrow->createOnetime([
    'uuid'           => $uuid,          // the Pandascrow user acting
    'initiator_id'   => $uuid,
    'initiator_role' => 'seller',       // buyer | seller | broker
    'title'          => 'Apartment 4B rent',
    'description'    => '12-month rent, 12 Admiralty Way',
    'currency'       => 'NGN',
    'amount'         => 350000,
    'buyer_details'  => ['name' => 'Tenant', 'email' => 'tenant@example.com'],
    'metadata'       => ['property_id' => 'PR-204'],   // your own IDs, see below
]);

echo $escrow['payment_url'];   // send the buyer here to fund the escrow
```

Your API key is the **public key** of your application (Dashboard → Developers). It is sent as the `Token` header. Most calls also take the `uuid` of the Pandascrow user you are acting for.

Every method returns the `data` object of the API response as an array.

## Errors

Every failure throws `Pandascrow\PandascrowException`: network errors, invalid responses, HTTP errors and any response with `status: false`.

```php
try {
    $ps->escrow->complete($uuid, $escrowId, $otp);
} catch (Pandascrow\PandascrowException $e) {
    $e->getMessage();   // "Invalid OTP"
    $e->httpStatus;     // 400
    $e->docUrl;         // link to the API reference, when available
    $e->response;       // full decoded response body
}
```

## Metadata

Escrows, invoices and virtual accounts accept a `metadata` object with your own identifiers. Pandascrow stores it as-is, returns it when you fetch the record, and includes it as `data.metadata` in every webhook for that record:

```php
$ps->escrow->createOnetime([... , 'metadata' => ['property_id' => 'PR-204']]);

// later, in your webhook handler
$event['data']['metadata']['property_id'];   // "PR-204"
```

Limits: up to 50 keys, key names up to 64 characters, 5 levels of nesting, 8 KB in total.

## Resources

| Property | Methods |
|---|---|
| `escrow` | `createOnetime`, `createMilestone`, `createRecurring`, `create`, `all`, `find`, `complete`, `resendReleaseOtp`, `payNextMilestone`, `completeMilestone`, `regeneratePayment`, `regenerateMilestonePayment`, `dispute`, `disputes`, `joinRecurring` |
| `wallet` | `list`, `balances`, `transactions`, `accounts`, `payout` |
| `payouts` | `create`, `all`, `settlementAccounts` |
| `bank` | `banks`, `validate`, `transfer`, `settlementAccount` |
| `virtualAccounts` | `createDynamic`, `createStatic`, `reallocate`, `deactivate`, `reactivate`, `confirmPayment` |
| `invoices` | `create`, `all`, `find`, `pay`, `cancel` |
| `paymentLinks` | `create`, `all`, `find`, `isSlugAvailable`, `goLive`, `toggleTip`, `payments`, `initiatePayment`, `completePayment`, `addMargin` |
| `kyc` | `bvn`, `nin`, `phone`, `nuban`, `driversLicense`, `tin`, `cac`, `searchCac`, `email` |
| `auth` | `login`, `verifyLoginOtp` |

Anything not wrapped yet:

```php
$ps->request('GET', '/clients', ['uuid' => $uuid]);
$ps->request('POST', '/escrow/recurring/update', [], ['uuid' => $uuid, 'escrow_id' => 12]);
```

### Examples

```php
// Milestone escrow, paid stage by stage
$ps->escrow->createMilestone([
    'uuid' => $uuid, 'initiator_id' => $uuid, 'initiator_role' => 'buyer',
    'title' => 'Website build', 'description' => 'Company website', 'currency' => 'NGN',
    'seller_details' => ['name' => 'Dev Studio', 'email' => 'studio@example.com'],
    'deposit_option' => 'milestone',
    'milestones' => [
        ['title' => 'Design', 'amount' => 150000],
        ['title' => 'Build',  'amount' => 350000],
    ],
]);

// NGN transfer to a bank account (keep the idempotency key to retry safely)
$ps->bank->transfer($uuid, 25000, [
    'account_number' => '0123456789', 'account_name' => 'Ada Obi', 'bank_code' => '058',
], 'NGN', ['idempotency_key' => 'payout-7781']);

// Virtual account that accepts any amount, expiring in 24 hours
$ps->virtualAccounts->createDynamic($uuid, 'Ada Obi', null, ['expiry_hours' => 24, 'metadata' => ['customer_id' => 'C-9']]);

// Invoice
$ps->invoices->create($uuid,
    ['full_name' => 'Client Co', 'email' => 'billing@client.co'],
    ['currency' => 'NGN', 'due_date' => '2026-12-31'],
    [['description' => 'Consulting', 'quantity' => 10, 'unit_price' => 20000]],
    'payment_link',
    ['metadata' => ['order_id' => 'A-1001']]
);
```

## Webhooks

Pandascrow signs every webhook with HMAC-SHA256 of the **raw request body**, using your application's **secret key**, and sends it in the `X-Pandascrow-Signature` header.

```php
try {
    $event = Pandascrow\Webhook::verify(getenv('PANDASCROW_SECRET_KEY'));
} catch (Pandascrow\PandascrowException $e) {
    http_response_code($e->httpStatus ?: 400);
    exit;
}

switch ($event['event']) {
    case 'escrow.paid':      /* $event['data'] */ break;
    case 'escrow.completed': break;
    case 'invoice.paid':     break;
}

http_response_code(200);
```

`verify()` reads `php://input` and the signature header for you. In frameworks, pass them in: `Webhook::verify($secret, $request->getContent(), $request->header('X-Pandascrow-Signature'))`. Always pass the raw body string, not a re-encoded array. Events older than 5 minutes are rejected; pass a fourth argument to change that (`0` turns the check off).

Events include `escrow.created`, `escrow.paid`, `escrow.completed`, `escrow.milestone.completed`, `escrow.dispute.opened`, `escrow.payout.completed`, `escrow.payout.processing`, `escrow.payout.failed`, `recurring.payment.success`, `invoice.paid` and `wallet.deposit.success`. See `examples/webhook.php`.

## Options

```php
new Pandascrow\Client($apiKey, [
    'sandbox'         => true,      // false for live
    'base_url'        => null,      // override the host, e.g. a local API
    'timeout'         => 30,        // seconds
    'connect_timeout' => 10,
    'headers'         => [],        // extra headers on every request
    'transport'       => null,      // custom Pandascrow\Http\Transport (logging, retries, tests)
]);
```

## Testing

```bash
composer install
composer test
```

The test suite uses a fake transport, so it never calls the API.

## License

MIT
