<?php

namespace App\Core;

class Response
{
    protected int $status = 200;

    /**
     * Set HTTP status code
     */
    public function status(int $code): self
    {
        $this->status = $code;
        http_response_code($code);
        return $this;
    }

    /**
     * Redirect to a URL and exit
     */
    public function redirect(string $url): void
    {
        if (!headers_sent()) {
            header('Location: ' . $url, true, 302);
        }
        exit;
    }

    /**
     * Return JSON response and exit
     */
    public function json(array $data, int $status = 200): void
    {
        $this->status($status);
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
