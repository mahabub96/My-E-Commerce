<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\PaymentService;

/**
 * Webhook Controller
 * 
 * Handles payment gateway webhooks (Stripe, PayPal)
 * with signature verification and idempotent processing.
 */
class WebhookController extends Controller
{
    /**
     * Handle Stripe webhooks
     */
    public function stripe(Request $request)
    {
        $payload = $request->rawBody();
        $headers = getallheaders();

        $paymentService = new PaymentService($this->db(), 'stripe');

        // Verify webhook signature
        if (!$paymentService->verifyWebhook($payload, $headers)) {
            $this->logWebhook('stripe', 'INVALID_SIGNATURE', $payload);
            return $this->json(['error' => 'Invalid signature'], 400);
        }

        $event = json_decode($payload, true);
        
        // Check for duplicate webhook (idempotency)
        if ($this->isDuplicateWebhook($event['id'])) {
            return $this->json(['status' => 'already_processed'], 200);
        }

        $this->logWebhook('stripe', $event['type'], $payload, $event['id']);

        try {
            $this->processStripeEvent($event, $paymentService);
            $this->markWebhookProcessed($event['id']);
            return $this->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            error_log("Stripe webhook processing error: " . $e->getMessage());
            return $this->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Handle PayPal webhooks
     */
    public function paypal(Request $request)
    {
        $payload = $request->rawBody();
        $headers = getallheaders();

        $paymentService = new PaymentService($this->db(), 'paypal');

        // Verify webhook signature
        if (!$paymentService->verifyWebhook($payload, $headers)) {
            $this->logWebhook('paypal', 'INVALID_SIGNATURE', $payload);
            return $this->json(['error' => 'Invalid signature'], 400);
        }

        $event = json_decode($payload, true);
        
        // Check for duplicate webhook (idempotency)
        if ($this->isDuplicateWebhook($event['id'])) {
            return $this->json(['status' => 'already_processed'], 200);
        }

        $this->logWebhook('paypal', $event['event_type'], $payload, $event['id']);

        try {
            $this->processPayPalEvent($event, $paymentService);
            $this->markWebhookProcessed($event['id']);
            return $this->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            error_log("PayPal webhook processing error: " . $e->getMessage());
            return $this->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Process Stripe webhook event
     */
    private function processStripeEvent(array $event, PaymentService $paymentService): void
    {
        $type = $event['type'];
        $data = $event['data']['object'];

        match ($type) {
            'payment_intent.succeeded' => $this->handlePaymentSuccess($data['id'], 'succeeded', $paymentService),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($data['id'], $paymentService),
            'payment_intent.canceled' => $this->handlePaymentCanceled($data['id'], $paymentService),
            'charge.refunded' => $this->handleRefund($data['payment_intent'], $paymentService),
            'charge.dispute.created' => $this->handleDispute($data['payment_intent'], $paymentService),
            default => null, // Log unhandled events
        };
    }

    /**
     * Process PayPal webhook event
     */
    private function processPayPalEvent(array $event, PaymentService $paymentService): void
    {
        $type = $event['event_type'];
        $resource = $event['resource'];

        match ($type) {
            'CHECKOUT.ORDER.APPROVED' => $this->handlePayPalOrderApproved($resource['id'], $paymentService),
            'PAYMENT.CAPTURE.COMPLETED' => $this->handlePaymentSuccess($resource['id'], 'completed', $paymentService),
            'PAYMENT.CAPTURE.DENIED' => $this->handlePaymentFailed($resource['id'], $paymentService),
            'PAYMENT.CAPTURE.REFUNDED' => $this->handleRefund($resource['id'], $paymentService),
            default => null, // Log unhandled events
        };
    }

    /**
     * Handle successful payment
     */
    private function handlePaymentSuccess(string $paymentId, string $status, PaymentService $paymentService): void
    {
        $paymentService->updatePaymentStatus($paymentId, $status);
        
        // Send order confirmation email
        $this->queueOrderConfirmationEmail($paymentId);
    }

    /**
     * Handle failed payment
     */
    private function handlePaymentFailed(string $paymentId, PaymentService $paymentService): void
    {
        $paymentService->updatePaymentStatus($paymentId, 'failed');
        
        // Optionally: Send payment failed notification
    }

    /**
     * Handle canceled payment
     */
    private function handlePaymentCanceled(string $paymentId, PaymentService $paymentService): void
    {
        $paymentService->updatePaymentStatus($paymentId, 'canceled');
    }

    /**
     * Handle refund
     */
    private function handleRefund(string $paymentId, PaymentService $paymentService): void
    {
        $paymentService->updatePaymentStatus($paymentId, 'refunded');
        
        // Send refund confirmation email
        $this->queueRefundEmail($paymentId);
    }

    /**
     * Handle dispute/chargeback
     */
    private function handleDispute(string $paymentId, PaymentService $paymentService): void
    {
        $paymentService->updatePaymentStatus($paymentId, 'disputed');
        
        // Alert admin about dispute
        $this->alertAdminAboutDispute($paymentId);
    }

    /**
     * Handle PayPal order approved (ready for capture)
     */
    private function handlePayPalOrderApproved(string $orderId, PaymentService $paymentService): void
    {
        // PayPal orders need to be captured after approval
        try {
            $result = $paymentService->capturePayment($orderId);
            $paymentService->updatePaymentStatus($orderId, $result['status']);
        } catch (\Exception $e) {
            error_log("PayPal capture after approval failed: " . $e->getMessage());
        }
    }

    /**
     * Check if webhook was already processed (idempotency)
     */
    private function isDuplicateWebhook(string $webhookId): bool
    {
        $stmt = $this->db()->prepare("SELECT id FROM webhook_logs WHERE webhook_id = ? AND processed = 1");
        $stmt->execute([$webhookId]);
        return $stmt->fetch() !== false;
    }

    /**
     * Mark webhook as processed
     */
    private function markWebhookProcessed(string $webhookId): void
    {
        $stmt = $this->db()->prepare("UPDATE webhook_logs SET processed = 1, processed_at = NOW() WHERE webhook_id = ?");
        $stmt->execute([$webhookId]);
    }

    /**
     * Log webhook for debugging and audit trail
     */
    private function logWebhook(string $gateway, string $eventType, string $payload, ?string $webhookId = null): void
    {
        $stmt = $this->db()->prepare("
            INSERT INTO webhook_logs (gateway, event_type, webhook_id, payload, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$gateway, $eventType, $webhookId, $payload]);
    }

    /**
     * Queue order confirmation email
     */
    private function queueOrderConfirmationEmail(string $paymentId): void
    {
        // Will be implemented with EmailService
        $stmt = $this->db()->prepare("
            INSERT INTO job_queue (type, payload, status, created_at)
            VALUES ('order_confirmation', ?, 'pending', NOW())
        ");
        $stmt->execute([json_encode(['payment_id' => $paymentId])]);
    }

    /**
     * Queue refund email
     */
    private function queueRefundEmail(string $paymentId): void
    {
        $stmt = $this->db()->prepare("
            INSERT INTO job_queue (type, payload, status, created_at)
            VALUES ('refund_confirmation', ?, 'pending', NOW())
        ");
        $stmt->execute([json_encode(['payment_id' => $paymentId])]);
    }

    /**
     * Alert admin about dispute
     */
    private function alertAdminAboutDispute(string $paymentId): void
    {
        $stmt = $this->db()->prepare("
            INSERT INTO job_queue (type, payload, status, created_at)
            VALUES ('admin_alert', ?, 'pending', NOW())
        ");
        $stmt->execute([json_encode([
            'type' => 'dispute',
            'payment_id' => $paymentId,
            'message' => "Payment dispute received for payment: $paymentId"
        ])]);
    }
}
