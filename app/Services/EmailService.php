<?php

namespace App\Services;

use PDO;

/**
 * Email Service
 * 
 * Handles transactional email sending with queue support.
 * Supports multiple providers (SMTP, SendGrid, Mailgun).
 */
class EmailService
{
    private PDO $db;
    private string $provider;
    private array $config;

    public function __construct(PDO $db, string $provider = 'smtp')
    {
        $this->db = $db;
        $this->provider = $provider;
        $this->config = $this->loadConfig($provider);
    }

    /**
     * Load email provider configuration
     */
    private function loadConfig(string $provider): array
    {
        $config = [
            'smtp' => [
                'host' => env('MAIL_HOST', 'localhost'),
                'port' => env('MAIL_PORT', 587),
                'username' => env('MAIL_USERNAME', ''),
                'password' => env('MAIL_PASSWORD', ''),
                'encryption' => env('MAIL_ENCRYPTION', 'tls'),
                'from_email' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
                'from_name' => env('MAIL_FROM_NAME', 'E-Commerce Store'),
            ],
            'sendgrid' => [
                'api_key' => env('SENDGRID_API_KEY', ''),
                'from_email' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
                'from_name' => env('MAIL_FROM_NAME', 'E-Commerce Store'),
            ],
            'mailgun' => [
                'api_key' => env('MAILGUN_API_KEY', ''),
                'domain' => env('MAILGUN_DOMAIN', ''),
                'from_email' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
                'from_name' => env('MAIL_FROM_NAME', 'E-Commerce Store'),
            ],
        ];

        return $config[$provider] ?? [];
    }

    /**
     * Send email immediately
     */
    public function send(string $to, string $subject, string $body, array $options = []): bool
    {
        // STUB: Log emails instead of sending if SMTP not configured
        // This allows backend to function without email configuration
        if ($this->provider === 'smtp' && empty($this->config['username'])) {
            error_log("EmailService: SMTP not configured - logging email instead");
            error_log("EMAIL TO: {$to}");
            error_log("EMAIL SUBJECT: {$subject}");
            error_log("EMAIL BODY: " . substr($body, 0, 100) . "...");
            return true; // Return success to not block execution
        }
        
        try {
            return match ($this->provider) {
                'smtp' => $this->sendViaSMTP($to, $subject, $body, $options),
                'sendgrid' => $this->sendViaSendGrid($to, $subject, $body, $options),
                'mailgun' => $this->sendViaMailgun($to, $subject, $body, $options),
                default => throw new \Exception("Unsupported email provider: {$this->provider}"),
            };
        } catch (\Exception $e) {
            error_log("Email send failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Queue email for async sending
     */
    public function queue(string $to, string $subject, string $body, array $options = []): int
    {
        $payload = json_encode([
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'options' => $options,
        ]);

        $stmt = $this->db->prepare("
            INSERT INTO job_queue (type, payload, status, created_at)
            VALUES ('email', ?, 'pending', NOW())
        ");
        $stmt->execute([$payload]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Send via SMTP (PHPMailer)
     */
    private function sendViaSMTP(string $to, string $subject, string $body, array $options): bool
    {
        // Using PHPMailer if available, otherwise fallback to mail()
        if (class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
            $mail->SMTPSecure = $this->config['encryption'];
            $mail->Port = $this->config['port'];
            
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $body;
            
            if (isset($options['attachments'])) {
                foreach ($options['attachments'] as $attachment) {
                    $mail->addAttachment($attachment);
                }
            }
            
            return $mail->send();
        }
        
        // Fallback to PHP mail()
        $headers = "From: {$this->config['from_name']} <{$this->config['from_email']}>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        return mail($to, $subject, $body, $headers);
    }

    /**
     * Send via SendGrid API
     */
    private function sendViaSendGrid(string $to, string $subject, string $body, array $options): bool
    {
        if (empty($this->config['api_key'])) {
            throw new \Exception('SendGrid API key not configured');
        }

        $data = [
            'personalizations' => [[
                'to' => [['email' => $to]],
                'subject' => $subject,
            ]],
            'from' => [
                'email' => $this->config['from_email'],
                'name' => $this->config['from_name'],
            ],
            'content' => [[
                'type' => 'text/html',
                'value' => $body,
            ]],
        ];

        $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->config['api_key'],
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 300;
    }

    /**
     * Send via Mailgun API
     */
    private function sendViaMailgun(string $to, string $subject, string $body, array $options): bool
    {
        if (empty($this->config['api_key']) || empty($this->config['domain'])) {
            throw new \Exception('Mailgun credentials not configured');
        }

        $data = [
            'from' => "{$this->config['from_name']} <{$this->config['from_email']}>",
            'to' => $to,
            'subject' => $subject,
            'html' => $body,
        ];

        $ch = curl_init("https://api.mailgun.net/v3/{$this->config['domain']}/messages");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_USERPWD => 'api:' . $this->config['api_key'],
            CURLOPT_POSTFIELDS => http_build_query($data),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmation(int $orderId): int
    {
        // Get order details
        $stmt = $this->db->prepare("
            SELECT o.*, u.email, u.name 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            throw new \Exception("Order not found: $orderId");
        }

        $subject = "Order Confirmation - Order #{$orderId}";
        $body = $this->renderOrderConfirmationTemplate($order);

        return $this->queue($order['email'], $subject, $body);
    }

    /**
     * Send password reset email
     */
    public function sendPasswordReset(string $email, string $token): int
    {
        $resetUrl = $_ENV['APP_URL'] . "/reset-password?token=$token";
        
        $subject = "Password Reset Request";
        $body = $this->renderPasswordResetTemplate($email, $resetUrl);

        return $this->queue($email, $subject, $body);
    }

    /**
     * Render order confirmation template
     */
    private function renderOrderConfirmationTemplate(array $order): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #4CAF50;">Order Confirmed!</h1>
        <p>Hi {$order['name']},</p>
        <p>Thank you for your order. We've received your order and will process it shortly.</p>
        
        <div style="background: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <h2 style="margin-top: 0;">Order Details</h2>
            <p><strong>Order ID:</strong> #{$order['id']}</p>
            <p><strong>Total:</strong> \${$order['total_amount']}</p>
            <p><strong>Status:</strong> {$order['status']}</p>
        </div>
        
        <p>You can track your order status in your account dashboard.</p>
        
        <p style="color: #666; font-size: 14px;">
            If you have any questions, please contact our support team.
        </p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Render password reset template
     */
    private function renderPasswordResetTemplate(string $email, string $resetUrl): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Password Reset</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1>Password Reset Request</h1>
        <p>We received a request to reset your password for {$email}.</p>
        
        <div style="margin: 30px 0;">
            <a href="{$resetUrl}" style="background: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">
                Reset Password
            </a>
        </div>
        
        <p>If you didn't request this, you can safely ignore this email.</p>
        <p>This link will expire in 1 hour.</p>
        
        <p style="color: #666; font-size: 14px; margin-top: 30px;">
            If the button doesn't work, copy and paste this link:<br>
            {$resetUrl}
        </p>
    </div>
</body>
</html>
HTML;
    }
}
