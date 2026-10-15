<?php
/**
 * SIGAP-POLDA: Front Controller & URL Routing Engine
 */

namespace App\Core;

class App
{
    protected string $controller = 'HomeController';
    protected string $action = 'index';
    protected array $params = [];

    public function __construct()
    {
        $url = $this->parseUrl();

        // 1. Resolve Controller
        if (!empty($url[0])) {
            $candidate = ucfirst($url[0]) . 'Controller';
            $controllerPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $candidate . '.php';
            if (file_exists($controllerPath)) {
                $this->controller = $candidate;
                unset($url[0]);
            }
        }

        $fullControllerClass = "App\\Controllers\\" . $this->controller;
        if (!class_exists($fullControllerClass)) {
            $this->render404("Controller {$this->controller} tidak ditemukan.");
            return;
        }

        $controllerInstance = new $fullControllerClass();

        // 2. Resolve Action Method
        if (isset($url[1])) {
            $actionCandidate = $url[1];
            // Format camelCase jika ada strip (misal: create-report -> createReport)
            $actionCandidate = lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $actionCandidate))));
            if (method_exists($controllerInstance, $actionCandidate)) {
                $this->action = $actionCandidate;
                unset($url[1]);
            } else {
                $this->render404("Action {$actionCandidate} tidak ditemukan pada {$this->controller}.");
                return;
            }
        }

        // 3. Resolve Parameters
        $this->params = $url ? array_values($url) : [];

        // 4. Dispatch Call
        call_user_func_array([$controllerInstance, $this->action], $this->params);
    }

    private function parseUrl(): array
    {
        // Mendapatkan path URL setelah script entrypoint
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseDir = dirname($scriptName);

        // Bersihkan path
        $path = parse_url($requestUri, PHP_URL_PATH) ?? '/';
        if ($baseDir !== '/' && str_starts_with($path, $baseDir)) {
            $path = substr($path, strlen($baseDir));
        }

        $path = trim($path, '/');
        if (empty($path)) {
            return [];
        }

        return explode('/', filter_var($path, FILTER_SANITIZE_URL));
    }

    private function render404(string $message = 'Halaman Tidak Ditemukan'): void
    {
        http_response_code(404);
        // Detail teknis (nama controller/action) hanya untuk development
        $message = \App\Config\Config::isDevelopment()
            ? htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
            : 'Halaman yang Anda cari tidak tersedia.';
        echo "<!DOCTYPE html><html lang='id'><head><meta charset='utf-8'><title>404 - SIGAP POLDA</title>
              <script src='https://cdn.tailwindcss.com'></script></head>
              <body class='bg-[#070F1E] text-slate-200 flex items-center justify-center min-h-screen p-4'>
                <div class='max-w-md w-full bg-[#0E1E38] p-8 rounded-2xl border border-amber-600/30 text-center shadow-2xl'>
                  <div class='w-16 h-16 bg-red-950/80 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold'>404</div>
                  <h1 class='text-2xl font-black text-amber-400 mb-2'>Halaman Tidak Ditemukan</h1>
                  <p class='text-slate-400 text-sm mb-6'>{$message}</p>
                  <a href='javascript:history.back()' class='inline-block px-5 py-2.5 bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-slate-950 font-bold rounded-xl text-sm transition-all'>Kembali</a>
                </div>
              </body></html>";
        exit;
    }
}
