<?php
require __DIR__ . '/autoload.php';

use Pandascrow\Client;
use Pandascrow\PandascrowException;

$ps = new Client(getenv('PANDASCROW_API_KEY'), ['sandbox' => true]);

try {
    $uuid = 'your-user-uuid';

    // Create a one-time escrow
    $escrow = $ps->escrow->createOnetime([
        'uuid' => $uuid,
        'initiator_id' => $uuid,
        'initiator_role' => 'seller',
        'title' => 'Order #1234',
        'description' => '20 units of widgets',
        'currency' => 'NGN',
        'amount' => 35000,
        'inspection_period' => '2',
        'delivery_date' => '2026-12-01',
        'who_pay_fees' => 'buyer',
        'buyer_details'  => ['name' => 'Buyer', 'email' => 'buyer@example.com', 'phone' => '+2348000000000'],
        'seller_details' => ['name' => 'Seller', 'email' => 'seller@example.com', 'phone' => '+2348000000001'],
        'payout' => ['payout_type' => 'bank', 'bank_code' => '058', 'account_number' => '0123456789', 'account_name' => 'Seller'],
    ]);
    echo $escrow['payment_url'] ?? json_encode($escrow['virtual_account'] ?? []), PHP_EOL;

    // Wallet balance
    print_r($ps->wallet->balance($uuid, '1week'));
} catch (PandascrowException $e) {
    echo "Error {$e->httpStatus}: {$e->getMessage()} {$e->docUrl}\n";
}
