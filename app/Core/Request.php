<?php

namespace App\Core;

class Request
{
    protected array $get;
    protected array $post;
    protected array $server;
    protected array $jsonBody;

    public function __construct()
    {
        $this->get = $_GET ?? [];
        $this->post = $_POST ?? [];
        $this->server = $_SERVER ?? [];
        $this->jsonBody = [];
        $this->parseJsonBody();
    }

    protected function parseJsonBody(): void
    {
        $input = @file_get_contents('php://input');
        if (!$input) {
            return;
        }

        $contentType = $this->server['CONTENT_TYPE'] ?? $this->server['HTTP_CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $data = json_decode($input, true);
            if (is_array($data)) {
                $this->jsonBody = $data;
            }
        }
    }

    /**
     * HTTP method (GET, POST, PUT, DELETE, etc.)
     */
    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Return merged request data: GET + POST + JSON body (POST overrides GET)
     */
    public function all(): array
    {
        return array_merge($this->get, $this->jsonBody, $this->post);
    }

    /**
     * Get a value from query string (GET)
     */
    public function get(string $key, $default = null)
    {
        return $this->get[$key] ?? $default;
    }

    /**
     * Get a value from POST body (form-encoded) or JSON body
     */
    public function post(string $key, $default = null)
    {
        if (array_key_exists($key, $this->post)) {
            return $this->post[$key];
        }

        if (array_key_exists($key, $this->jsonBody)) {
            return $this->jsonBody[$key];
        }

        return $default;
    }

    /**
     * Detect AJAX request (X-Requested-With) or JSON Accept header
     */
    public function isAjax(): bool
    {
        $xhr = $this->server['HTTP_X_REQUESTED_WITH'] ?? $this->server['X-Requested-With'] ?? '';
        if (strtolower($xhr) === 'xmlhttprequest') {
            return true;
        }

        $accept = $this->server['HTTP_ACCEPT'] ?? '';
        if (stripos($accept, 'application/json') !== false) {
            return true;
        }

        return false;
    }
}
