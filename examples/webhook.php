<?php

/**
 * Webhook endpoint. Set its URL in Dashboard → Developers → Webhook URL.
 */

require __DIR__ . '/../vendor/autoload.php';

use Pandascrow\PandascrowException;
use Pandascrow\Webhook;

try {
    // Reads the raw body and the X-Pandascrow-Signature header, checks the HMAC.
    $event = Webhook::verify((string) getenv('PANDASCROW_SECRET_KEY'));
} catch (PandascrowException $e) {
    http_response_code($e->httpStatus ?: 400);
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
    exit;
}

$data     = $event['data'];
$metadata = $data['metadata'] ?? [];   // whatever you sent when creating the escrow / invoice / account

switch ($event['event']) {
    case 'escrow.paid':
        // Buyer funded the escrow: e.g. mark the property as reserved
        // markReserved($metadata['property_id'] ?? null, $data['escrow_id']);
        break;

    case 'escrow.completed':
    case 'escrow.payout.completed':
        // Funds released to the seller
        break;

    case 'escrow.dispute.opened':
        break;

    case 'invoice.paid':
    case 'wallet.deposit.success':
        break;
}

http_response_code(200);
echo json_encode(['status' => true]);
