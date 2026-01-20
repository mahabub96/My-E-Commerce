<?php

namespace App\Core;

class Controller
{
    protected Request $request;
    protected Response $response;

    public function __construct()
    {
        $this->request = new Request();
        $this->response = new Response();
    }

    /**
     * Render a view. If $useLayout is false, render view without the default layout.
     */
    public function view(string $view, array $data = [], bool $useLayout = true): string
    {
        $layout = $useLayout ? null : false;
        return Views::render($view, $data, $layout);
    }

    /**
     * Redirect helper
     */
    public function redirect(string $url): void
    {
        $this->response->redirect($url);
    }

    /**
     * JSON helper
     */
    public function json(array $data, int $status = 200): void
    {
        $this->response->json($data, $status);
    }

    /**
     * Convenience to read input (POST preferred, then GET)
     */
    public function input(string $key, $default = null)
    {
        $val = $this->request->post($key, null);
        if ($val !== null) {
            return $val;
        }

        return $this->request->get($key, $default);
    }

    /**
     * Whether current request is AJAX
     */
    public function isAjax(): bool
    {
        return $this->request->isAjax();
    }

    /**
     * Get PDO database connection
     * 
     * @return \PDO
     */
    protected function db(): \PDO
    {
        return \App\Core\Model::getPDO();
    }
}
