<?php

/**
 * composer require circlepandaa/pandascrow-php-sdk
 *
 * PANDASCROW_API_KEY=pk_test_xxx PANDASCROW_UUID=your-user-uuid php examples/quickstart.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Pandascrow\Client;
use Pandascrow\PandascrowException;

$ps   = new Client((string) getenv('PANDASCROW_API_KEY'), ['sandbox' => true]);
$uuid = (string) getenv('PANDASCROW_UUID');

try {
    // 1. Create a one-time escrow. You are the seller; the buyer pays into escrow.
    $escrow = $ps->escrow->createOnetime([
        'uuid'              => $uuid,
        'initiator_id'      => $uuid,
        'initiator_role'    => 'seller',
        'title'             => 'Apartment 4B, 12-month rent',
        'description'       => 'Rent for 12 Admiralty Way, Lekki',
        'currency'          => 'NGN',
        'amount'            => 350000,
        'inspection_period' => '2',
        'delivery_date'     => '2026-12-01',
        'who_pay_fees'      => 'buyer',
        'buyer_details'     => ['name' => 'Tenant T', 'email' => 'tenant@example.com', 'phone' => '+2348000000000'],
        'payout'            => ['payout_type' => 'bank', 'bank_code' => '058', 'account_number' => '0123456789', 'account_name' => 'Landlord L'],
        'idempotency_key'   => 'lease-PR-204-2026',
        // Your own identifiers: returned on reads and in every webhook for this escrow
        'metadata'          => ['property_id' => 'PR-204', 'lease_id' => 'L-88'],
    ]);

    echo 'Escrow #' . $escrow['escrow_id'] . ' created. Send the buyer to: '
        . ($escrow['payment_url'] ?? json_encode($escrow['virtual_account'] ?? null)) . PHP_EOL;

    // 2. Read it back
    $details = $ps->escrow->find($uuid, $escrow['escrow_id']);
    echo 'Status: ' . $details['escrow']['status'] . ', property: ' . $details['escrow']['metadata']['property_id'] . PHP_EOL;

    // 3. Balances
    print_r($ps->wallet->balances($uuid)['balances'] ?? []);
} catch (PandascrowException $e) {
    echo "Pandascrow error (HTTP {$e->httpStatus}): {$e->getMessage()}" . ($e->docUrl ? " (see {$e->docUrl})" : '') . PHP_EOL;
    exit(1);
}
