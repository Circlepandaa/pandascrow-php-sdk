# pandascrow-php

Minimal, dependency-free PHP SDK (PHP 7.4+, `ext-curl`) for the [Pandascrow v3 API](https://pandascrow.readme.io/).

```bash
composer require pandascrow/pandascrow-php   # or require src/ via any PSR-4 autoloader
```

```php
$ps = new Pandascrow\Client($apiKey, ['sandbox' => true]); // false => https://api.pandascrow.io
$ps->wallet->balance($uuid, '1month');
$ps->bank->validate($uuid, '058', '0123456789');
$ps->escrow->createOnetime([...]);
```

Every call returns the response's `data` array and throws `Pandascrow\PandascrowException`
(`->getMessage()`, `->httpStatus`, `->docUrl`, `->response`) when `status` is false or HTTP >= 400.

| Property | Methods |
|---|---|
| `auth` | `login` |
| `escrow` | `createOnetime`, `createMilestone`, `complete`, `payNextMilestone`, `completeMilestone`, `all`, `find`, `resendReleaseOtp`, `dispute` |
| `wallet` | `list`, `balance`, `transactions`, `fund`, `payout` |
| `bank` | `banks`, `validate`, `transfer` |
| `virtualAccounts` | `createDynamic`, `createStatic`, `confirmPayment` |
| `invoices` | `create`, `all`, `find` |
| `paymentLinks` | `create`, `all`, `payments`, `initiatePayment` |
| `kyc` | `bvn`, `nin` |

Anything not wrapped: `$ps->request('GET', '/path', $query, $body)`.

Webhooks:

```php
$event = Pandascrow\Webhook::verify($secretKey);   // reads php://input + X-Pandascrow-Signature
http_response_code(200);
echo json_encode(['status' => true]);              // must return status true or Pandascrow retries
```

## Not covered (docs were inaccessible or ambiguous when written)
Login OTP, email verification, recurring escrow + join contributors, KYC (CAC, CAC by name, NUBAN, driver's license, TIN), and the slug-availability endpoint. Use `request()` for these.
