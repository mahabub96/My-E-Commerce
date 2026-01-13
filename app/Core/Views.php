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
     */
    public static function render(string $view, array $data = [], $layout = null): string
    {
        $viewsPath = self::basePath();
        $viewFile = $viewsPath . '/' . str_replace('.', '/', ltrim($view, '/')) . '.php';

        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: {$viewFile}");
        }

        extract($data, EXTR_SKIP);

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
     * Include a partial view directly (useful inside other views)
     */
    public static function partial(string $partial, array $data = []): void
    {
        $viewsPath = self::basePath();
        $file = $viewsPath . '/' . str_replace('.', '/', ltrim($partial, '/')) . '.php';
        if (!file_exists($file)) {
            throw new \Exception("Partial not found: {$file}");
        }
        extract($data, EXTR_SKIP);
        include $file;
    }
}
