<?php

namespace App\Core;

class Views
{
    protected static ?string $viewsPath = null;

    protected static function basePath(): string
    {
        if (self::$viewsPath !== null) {
            return self::$viewsPath;
        }

        $path = realpath(__DIR__ . '/../../resources/views');
        if ($path === false) {
            throw new \Exception('Views directory not found: resources/views');
        }

        self::$viewsPath = $path;
        return self::$viewsPath;
    }

    /**
     * Render a view and optionally wrap it in a layout.
     * View names may use dot notation like "shop.index" or path "shop/index".
     * If $layout is null the default layout is chosen based on view namespace (admin vs app).
     * 
     * Auto-escapes all variables by default. Use raw() helper to output unescaped content.
     */
    public static function render(string $view, array $data = [], $layout = null): string
    {
        $viewsPath = self::basePath();
        $viewFile = $viewsPath . '/' . str_replace('.', '/', ltrim($view, '/')) . '.php';

        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: {$viewFile}");
        }

        // Wrap data in escaping proxy
        $escapedData = self::escapeData($data);
        extract($escapedData, EXTR_SKIP);

        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        if ($layout === false) {
            echo $content;
            return $content;
        }

        if ($layout === null) {
            if (strpos($view, 'admin/') === 0 || strpos($view, 'admin.') === 0) {
                $layout = 'layouts.admin';
            } else {
                $layout = 'layouts.app';
            }
        }

        $layoutFile = $viewsPath . '/' . str_replace('.', '/', ltrim($layout, '/')) . '.php';
        if (!file_exists($layoutFile)) {
            // If layout missing, just return content
            echo $content;
            return $content;
        }

        // expose variables to layout
        $content = $content; // $content stays available in layout

        ob_start();
        include $layoutFile;
        $final = ob_get_clean();

        echo $final;
        return $final;
    }

    /**
     * Escape data for safe output in views
     * Recursively escapes arrays and converts objects to strings
     */
    protected static function escapeData(mixed $data): mixed
    {
        if (is_array($data)) {
            return array_map([self::class, 'escapeData'], $data);
        }
        
        if (is_string($data)) {
            return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        
        if (is_object($data)) {
            return htmlspecialchars((string)$data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        
        // Numbers, booleans, null pass through
        return $data;
    }

    /**
     * Output raw unescaped content (use sparingly, only for trusted HTML)
     * 
     * @param mixed $value The value to output without escaping
     * @return string The raw value
     */
    public static function raw(mixed $value): string
    {
        return (string)$value;
    }

    /**
     * Include a partial view directly (useful inside other views)
     * Auto-escapes data by default
     */
    public static function partial(string $partial, array $data = []): void
    {
        $viewsPath = self::basePath();
        $file = $viewsPath . '/' . str_replace('.', '/', ltrim($partial, '/')) . '.php';
        if (!file_exists($file)) {
            throw new \Exception("Partial not found: {$file}");
        }
        
        // Escape data for partials too
        $escapedData = self::escapeData($data);
        extract($escapedData, EXTR_SKIP);
        include $file;
    }
}
