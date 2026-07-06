<?php

declare(strict_types=1);

/**
 * Webhook handler example for Pandascrow
 * 
 * This script demonstrates how to handle incoming webhooks from Pandascrow.
 * Place this as your webhook endpoint URL in your application.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Pandascrow\Client;
use Pandascrow\Exceptions\WebhookException;
use Pandascrow\Utils\Logger;

// Configuration
$webhookSecret = getenv('PANDASCROW_WEBHOOK_SECRET') ?: 'your_webhook_secret';
$apiKey = getenv('PANDASCROW_API_KEY') ?: 'sk_test_xxxxxxxx';

// Initialize logger
$logger = new Logger();
$logger->createFileHandler('webhook.log');

// Get raw payload
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_PANDASCROW_SIGNATURE'] ?? $_SERVER['HTTP_X_SIGNATURE'] ?? '';

// Initialize client
$client = new Client($apiKey);

try {
    // Parse and validate webhook
    $event = $client->webhooks()->parse($payload, $signature, $webhookSecret);

    $eventType = $event['event'] ?? 'unknown';
    $eventId = $event['id'] ?? 'unknown';

    $logger->info("Processing webhook", [
        'event' => $eventType,
        'id' => $eventId
    ]);

    // Handle different event types
    switch ($eventType) {
        case 'payment.success':
            handlePaymentSuccess($event['data']);
            break;

        case 'payment.failed':
            handlePaymentFailed($event['data']);
            break;

        case 'payment.pending':
            handlePaymentPending($event['data']);
            break;

        case 'transfer.completed':
            handleTransferCompleted($event['data']);
            break;

        case 'transfer.failed':
            handleTransferFailed($event['data']);
            break;

        case 'transfer.reversed':
            handleTransferReversed($event['data']);
            break;

        case 'verification.completed':
            handleVerificationCompleted($event['data']);
            break;

        default:
            $logger->warning("Unknown webhook event", ['event' => $eventType]);
            // Still respond with 200 to acknowledge receipt
            respond(200, ['status' => 'ignored', 'event' => $eventType]);
            return;
    }

    // Acknowledge receipt
    respond(200, ['status' => 'success', 'event' => $eventType]);
} catch (WebhookException $e) {
    $logger->error("Webhook validation failed", [
        'error' => $e->getMessage(),
        'signature' => $signature
    ]);

    // Return 401 for invalid signatures
    respond(401, ['error' => 'Invalid webhook signature']);
} catch (\Exception $e) {
    $logger->error("Webhook processing failed", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    // Return 500 for processing errors
    respond(500, ['error' => 'Internal server error']);
}

// Event handlers

function handlePaymentSuccess(array $data): void
{
    global $logger;

    $paymentId = $data['id'] ?? 'unknown';
    $amount = $data['amount'] ?? 0;
    $currency = $data['currency'] ?? 'NGN';
    $customer = $data['customer']['email'] ?? 'unknown';

    $logger->info("Payment successful", [
        'payment_id' => $paymentId,
        'amount' => $amount,
        'currency' => $currency,
        'customer' => $customer
    ]);

    // TODO: Update your database
    // TODO: Send confirmation email
    // TODO: Fulfill order
}

function handlePaymentFailed(array $data): void
{
    global $logger;

    $paymentId = $data['id'] ?? 'unknown';
    $reason = $data['failure_reason'] ?? 'Unknown reason';

    $logger->warning("Payment failed", [
        'payment_id' => $paymentId,
        'reason' => $reason
    ]);

    // TODO: Update your database
    // TODO: Notify customer
}

function handlePaymentPending(array $data): void
{
    global $logger;

    $paymentId = $data['id'] ?? 'unknown';
    $status = $data['status'] ?? 'pending';

    $logger->info("Payment pending", [
        'payment_id' => $paymentId,
        'status' => $status
    ]);

    // TODO: Update your database
}

function handleTransferCompleted(array $data): void
{
    global $logger;

    $transferId = $data['id'] ?? 'unknown';
    $amount = $data['amount'] ?? 0;
    $currency = $data['currency'] ?? 'NGN';
    $recipient = $data['recipient']['account_number'] ?? 'unknown';

    $logger->info("Transfer completed", [
        'transfer_id' => $transferId,
        'amount' => $amount,
        'currency' => $currency,
        'recipient' => $recipient
    ]);

    // TODO: Update your database
    // TODO: Notify recipient
    // TODO: Update balance
}

function handleTransferFailed(array $data): void
{
    global $logger;

    $transferId = $data['id'] ?? 'unknown';
    $reason = $data['failure_reason'] ?? 'Unknown reason';

    $logger->warning("Transfer failed", [
        'transfer_id' => $transferId,
        'reason' => $reason
    ]);

    // TODO: Update your database
    // TODO: Notify sender
    // TODO: Reverse funds if needed
}

function handleTransferReversed(array $data): void
{
    global $logger;

    $transferId = $data['id'] ?? 'unknown';
    $originalTransferId = $data['original_transfer_id'] ?? 'unknown';
    $reason = $data['reason'] ?? 'Unknown reason';

    $logger->warning("Transfer reversed", [
        'transfer_id' => $transferId,
        'original_transfer_id' => $originalTransferId,
        'reason' => $reason
    ]);

    // TODO: Update your database
    // TODO: Notify sender
}

function handleVerificationCompleted(array $data): void
{
    global $logger;

    $verificationId = $data['id'] ?? 'unknown';
    $status = $data['status'] ?? 'unknown';
    $type = $data['type'] ?? 'unknown';

    $logger->info("Verification completed", [
        'verification_id' => $verificationId,
        'status' => $status,
        'type' => $type
    ]);

    // TODO: Update your database
    // TODO: Update user verification status
}

/**
 * Send JSON response
 */
function respond(int $statusCode, array $data): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Validate webhook IP (optional security measure)
 */
function isValidWebhookIp(string $ip): bool
{
    $allowedIps = [
        '52.31.139.42',
        '52.214.165.118',
        // Add all Pandascrow IPs here
    ];

    return in_array($ip, $allowedIps);
}

// Optional IP validation
// $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
// if (!isValidWebhookIp($clientIp)) {
//     respond(403, ['error' => 'Unauthorized IP']);
// }