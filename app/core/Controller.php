<?php
/**
 * SIGAP-POLRI: Base Controller
 */

namespace App\Core;

use App\Config\Config;

abstract class Controller
{
    /**
     * Render View dengan Master Layout
     */
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        // Ekstrak data variabel ke view scope
        extract($data);

        // Helper variabel global
        $baseUrl = Config::getBaseUrl();
        $authUser = Auth::user();
        $csrfToken = Session::generateCsrf();

        // Buffer konten view spesifik
        $viewFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $view) . '.php';
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View file tidak ditemukan: {$viewFile}");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Render master layout
        if ($layout === 'none') {
            echo $content;
            return;
        }

        $layoutFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . $layout . '.php';
        if (!file_exists($layoutFile)) {
            throw new \RuntimeException("Layout file tidak ditemukan: {$layoutFile}");
        }

        require $layoutFile;
    }

    /**
     * Render view tanpa layout menjadi string (mis. HTML sumber PDF)
     */
    protected function renderToString(string $view, array $data = []): string
    {
        ob_start();
        try {
            $this->render($view, $data, 'none');
            return (string)ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * Return RESTful JSON Response
     */
    protected function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Redirect URL Helper
     */
    protected function redirect(string $path): void
    {
        $baseUrl = Config::getBaseUrl();
        $target = str_starts_with($path, 'http') ? $path : "{$baseUrl}/" . ltrim($path, '/');
        header("Location: {$target}");
        exit;
    }

    /**
     * Input Helper: nilai mentah (hanya di-trim). Data disimpan apa adanya;
     * escape HTML WAJIB dilakukan di view saat output (htmlspecialchars).
     */
    protected function post(string $key, mixed $default = null): mixed
    {
        if (!isset($_POST[$key])) {
            return $default;
        }

        $val = $_POST[$key];
        return is_string($val) ? trim($val) : $val;
    }

    protected function get(string $key, mixed $default = null): mixed
    {
        if (!isset($_GET[$key])) {
            return $default;
        }

        $val = $_GET[$key];
        return is_string($val) ? trim($val) : $val;
    }

    protected function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    protected function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    protected function validateCsrf(): bool
    {
        $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        return Session::validateCsrf($token);
    }
}
