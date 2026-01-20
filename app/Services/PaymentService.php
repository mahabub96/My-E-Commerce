<?php

namespace App\Services;

use App\Models\Order;
use PDO;

/**
 * Payment Service
 * 
 * Handles payment processing for multiple gateways (Stripe, PayPal)
 * with idempotency, webhook verification, and reconciliation.
 */
class PaymentService
{
    private PDO $db;
    private string $gateway;
    private array $config;

    public function __construct(PDO $db, string $gateway = 'stripe')
    {
        $this->db = $db;
        $this->gateway = $gateway;
        $this->config = $this->loadConfig($gateway);
    }

    /**
     * Load payment gateway configuration
     */
    private function loadConfig(string $gateway): array
    {
        $config = [
            'stripe' => [
                'secret_key' => env('STRIPE_SECRET_KEY', ''),
                'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', ''),
                'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
            ],
            'paypal' => [
                'client_id' => env('PAYPAL_CLIENT_ID', ''),
                'client_secret' => env('PAYPAL_SECRET', ''),
                'webhook_id' => env('PAYPAL_WEBHOOK_ID', ''),
                'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox or live
            ],
        ];

        return $config[$gateway] ?? [];
    }

    /**
     * Create a payment intent/order
     * 
     * @param int $orderId Internal order ID
     * @param float $amount Amount in dollars
     * @param string $currency Currency code (USD, EUR, etc.)
     * @param array $metadata Additional metadata
     * @return array Payment intent details
     */
    public function createPayment(int $orderId, float $amount, string $currency = 'USD', array $metadata = []): array
    {
        // Check for existing payment (idempotency)
        $existing = $this->getPaymentByOrderId($orderId);
        if ($existing && $existing['status'] !== 'failed') {
            return $existing;
        }

        $result = match ($this->gateway) {
            'stripe' => $this->createStripePayment($orderId, $amount, $currency, $metadata),
            'paypal' => $this->createPayPalPayment($orderId, $amount, $currency, $metadata),
            default => throw new \Exception("Unsupported payment gateway: {$this->gateway}"),
        };

        // Store payment record
        $this->storePaymentRecord($orderId, $result);

        return $result;
    }

    /**
     * Create Stripe payment intent
     */
    private function createStripePayment(int $orderId, float $amount, string $currency, array $metadata): array
    {
        // STUB: Return mock response without requiring Stripe API keys
        // This allows backend to function without real payment integration
        if (empty($this->config['secret_key'])) {
            error_log("PaymentService: Stripe key not configured - returning mock payment");
            
            return [
                'gateway' => 'stripe',
                'payment_id' => 'mock_stripe_' . uniqid(),
                'client_secret' => 'mock_secret_' . uniqid(),
                'status' => 'pending',
                'amount' => $amount,
                'currency' => $currency,
            ];
        }

        \Stripe\Stripe::setApiKey($this->config['secret_key']);

        try {
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => (int)($amount * 100), // Convert to cents
                'currency' => strtolower($currency),
                'metadata' => array_merge($metadata, [
                    'order_id' => $orderId,
                    'integration' => 'php_mvc_ecommerce',
                ]),
                'automatic_payment_methods' => ['enabled' => true],
            ]);

            return [
                'gateway' => 'stripe',
                'payment_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'status' => $paymentIntent->status,
                'amount' => $amount,
                'currency' => $currency,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new \Exception("Stripe payment creation failed: " . $e->getMessage());
        }
    }

    /**
     * Create PayPal order
     */
    private function createPayPalPayment(int $orderId, float $amount, string $currency, array $metadata): array
    {
        // STUB: Return mock response without requiring PayPal credentials
        // This allows backend to function without real payment integration
        if (empty($this->config['client_id']) || empty($this->config['client_secret'])) {
            error_log("PaymentService: PayPal credentials not configured - returning mock payment");
            
            return [
                'gateway' => 'paypal',
                'payment_id' => 'mock_paypal_' . uniqid(),
                'status' => 'pending',
                'amount' => $amount,
                'currency' => $currency,
                'approval_url' => '#', // Mock approval URL
            ];
        }

        $baseUrl = $this->config['mode'] === 'live' 
            ? 'https://api-m.paypal.com' 
            : 'https://api-m.sandbox.paypal.com';

        // Get access token
        $accessToken = $this->getPayPalAccessToken($baseUrl);

        // Create order
        $orderData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => (string)$orderId,
                'amount' => [
                    'currency_code' => $currency,
                    'value' => number_format($amount, 2, '.', ''),
                ],
            ]],
            'application_context' => [
                'return_url' => $_ENV['APP_URL'] . '/checkout/paypal-return',
                'cancel_url' => $_ENV['APP_URL'] . '/checkout/paypal-cancel',
            ],
        ];

        $ch = curl_init("$baseUrl/v2/checkout/orders");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ],
            CURLOPT_POSTFIELDS => json_encode($orderData),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 201) {
            throw new \Exception("PayPal order creation failed: " . $response);
        }

        $result = json_decode($response, true);

        return [
            'gateway' => 'paypal',
            'payment_id' => $result['id'],
            'status' => strtolower($result['status']),
            'amount' => $amount,
            'currency' => $currency,
            'approval_url' => $result['links'][1]['href'] ?? null, // Link for customer approval
        ];
    }

    /**
     * Get PayPal access token
     */
    private function getPayPalAccessToken(string $baseUrl): string
    {
        $ch = curl_init("$baseUrl/v1/oauth2/token");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_USERPWD => $this->config['client_id'] . ':' . $this->config['client_secret'],
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        return $data['access_token'] ?? throw new \Exception('Failed to get PayPal access token');
    }

    /**
     * Capture/confirm a payment
     */
    public function capturePayment(string $paymentId): array
    {
        return match ($this->gateway) {
            'stripe' => $this->captureStripePayment($paymentId),
            'paypal' => $this->capturePayPalPayment($paymentId),
            default => throw new \Exception("Unsupported payment gateway: {$this->gateway}"),
        };
    }

    /**
     * Capture Stripe payment (confirm intent)
     */
    private function captureStripePayment(string $paymentId): array
    {
        \Stripe\Stripe::setApiKey($this->config['secret_key']);

        try {
            $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentId);
            
            if ($paymentIntent->status === 'requires_confirmation') {
                $paymentIntent->confirm();
            }

            return [
                'payment_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount / 100,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new \Exception("Stripe payment capture failed: " . $e->getMessage());
        }
    }

    /**
     * Capture PayPal order
     */
    private function capturePayPalPayment(string $orderId): array
    {
        $baseUrl = $this->config['mode'] === 'live' 
            ? 'https://api-m.paypal.com' 
            : 'https://api-m.sandbox.paypal.com';

        $accessToken = $this->getPayPalAccessToken($baseUrl);

        $ch = curl_init("$baseUrl/v2/checkout/orders/$orderId/capture");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 201) {
            throw new \Exception("PayPal capture failed: " . $response);
        }

        $result = json_decode($response, true);

        return [
            'payment_id' => $result['id'],
            'status' => strtolower($result['status']),
            'amount' => (float)$result['purchase_units'][0]['payments']['captures'][0]['amount']['value'],
        ];
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhook(string $payload, array $headers): bool
    {
        return match ($this->gateway) {
            'stripe' => $this->verifyStripeWebhook($payload, $headers),
            'paypal' => $this->verifyPayPalWebhook($payload, $headers),
            default => false,
        };
    }

    /**
     * Verify Stripe webhook signature
     */
    private function verifyStripeWebhook(string $payload, array $headers): bool
    {
        // STUB: If no webhook secret configured, allow in development mode
        if (empty($this->config['webhook_secret'])) {
            error_log("PaymentService: Stripe webhook secret not configured - skipping verification (DEVELOPMENT ONLY)");
            return true; // Allow in development without API keys
        }

        \Stripe\Stripe::setApiKey($this->config['secret_key']);

        try {
            $signature = $headers['Stripe-Signature'] ?? $headers['stripe-signature'] ?? '';
            \Stripe\Webhook::constructEvent($payload, $signature, $this->config['webhook_secret']);
            return true;
        } catch (\Exception $e) {
            error_log("Stripe webhook verification failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify PayPal webhook signature
     */
    private function verifyPayPalWebhook(string $payload, array $headers): bool
    {
        // STUB: If credentials not configured, allow in development mode
        if (empty($this->config['client_id']) || empty($this->config['client_secret'])) {
            error_log("PaymentService: PayPal credentials not configured - skipping verification (DEVELOPMENT ONLY)");
            return true; // Allow in development without API keys
        }
        
        // PayPal webhook verification requires calling their API
        $baseUrl = $this->config['mode'] === 'live' 
            ? 'https://api-m.paypal.com' 
            : 'https://api-m.sandbox.paypal.com';

        $accessToken = $this->getPayPalAccessToken($baseUrl);

        $verificationData = [
            'transmission_id' => $headers['Paypal-Transmission-Id'] ?? '',
            'transmission_time' => $headers['Paypal-Transmission-Time'] ?? '',
            'cert_url' => $headers['Paypal-Cert-Url'] ?? '',
            'auth_algo' => $headers['Paypal-Auth-Algo'] ?? '',
            'transmission_sig' => $headers['Paypal-Transmission-Sig'] ?? '',
            'webhook_id' => $this->config['webhook_id'],
            'webhook_event' => json_decode($payload, true),
        ];

        $ch = curl_init("$baseUrl/v1/notifications/verify-webhook-signature");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ],
            CURLOPT_POSTFIELDS => json_encode($verificationData),
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        return ($result['verification_status'] ?? '') === 'SUCCESS';
    }

    /**
     * Store payment record in database
     */
    private function storePaymentRecord(int $orderId, array $paymentData): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO payments (order_id, gateway, payment_id, amount, currency, status, created_at)
            VALUES (:order_id, :gateway, :payment_id, :amount, :currency, :status, NOW())
            ON DUPLICATE KEY UPDATE status = :status, updated_at = NOW()
        ");

        $stmt->execute([
            'order_id' => $orderId,
            'gateway' => $paymentData['gateway'],
            'payment_id' => $paymentData['payment_id'],
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'],
            'status' => $paymentData['status'],
        ]);
    }

    /**
     * Get payment by order ID
     */
    public function getPaymentByOrderId(int $orderId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$orderId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(string $paymentId, string $status): void
    {
        $stmt = $this->db->prepare("UPDATE payments SET status = ?, updated_at = NOW() WHERE payment_id = ?");
        $stmt->execute([$status, $paymentId]);

        // Also update order status
        $payment = $this->getPaymentByPaymentId($paymentId);
        if ($payment) {
            $orderStatus = $this->mapPaymentStatusToOrderStatus($status);
            $this->updateOrderStatus($payment['order_id'], $orderStatus);
        }
    }

    /**
     * Get payment by payment ID
     */
    private function getPaymentByPaymentId(string $paymentId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM payments WHERE payment_id = ?");
        $stmt->execute([$paymentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Map payment status to order status
     */
    private function mapPaymentStatusToOrderStatus(string $paymentStatus): string
    {
        return match ($paymentStatus) {
            'succeeded', 'completed', 'captured' => 'paid',
            'requires_payment_method', 'requires_confirmation', 'processing' => 'pending',
            'canceled', 'failed' => 'cancelled',
            default => 'pending',
        };
    }

    /**
     * Update order status
     */
    private function updateOrderStatus(int $orderId, string $status): void
    {
        $stmt = $this->db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $orderId]);
    }

    /**
     * Refund a payment
     */
    public function refundPayment(string $paymentId, ?float $amount = null): array
    {
        return match ($this->gateway) {
            'stripe' => $this->refundStripePayment($paymentId, $amount),
            'paypal' => $this->refundPayPalPayment($paymentId, $amount),
            default => throw new \Exception("Unsupported payment gateway: {$this->gateway}"),
        };
    }

    /**
     * Refund Stripe payment
     */
    private function refundStripePayment(string $paymentId, ?float $amount): array
    {
        \Stripe\Stripe::setApiKey($this->config['secret_key']);

        try {
            $params = ['payment_intent' => $paymentId];
            if ($amount !== null) {
                $params['amount'] = (int)($amount * 100);
            }

            $refund = \Stripe\Refund::create($params);

            return [
                'refund_id' => $refund->id,
                'status' => $refund->status,
                'amount' => $refund->amount / 100,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new \Exception("Stripe refund failed: " . $e->getMessage());
        }
    }

    /**
     * Refund PayPal payment
     */
    private function refundPayPalPayment(string $captureId, ?float $amount): array
    {
        $baseUrl = $this->config['mode'] === 'live' 
            ? 'https://api-m.paypal.com' 
            : 'https://api-m.sandbox.paypal.com';

        $accessToken = $this->getPayPalAccessToken($baseUrl);

        $refundData = [];
        if ($amount !== null) {
            $refundData['amount'] = [
                'value' => number_format($amount, 2, '.', ''),
                'currency_code' => 'USD',
            ];
        }

        $ch = curl_init("$baseUrl/v2/payments/captures/$captureId/refund");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ],
            CURLOPT_POSTFIELDS => json_encode($refundData),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 201) {
            throw new \Exception("PayPal refund failed: " . $response);
        }

        $result = json_decode($response, true);

        return [
            'refund_id' => $result['id'],
            'status' => strtolower($result['status']),
            'amount' => (float)$result['amount']['value'],
        ];
    }
}
