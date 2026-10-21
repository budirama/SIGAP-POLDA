<?php
/**
 * SIGAP-POLRI: Front Controller & URL Routing Engine
 */

namespace App\Core;

class App
{
    // Alias segmen URL ke nama controller yang tidak mengikuti pola {Segmen}Controller
    private const ROUTE_ALIASES = [
        'social' => 'SocialMediaController',
        'posko-laporan' => 'PoskoReportController',
        'posko-jenis' => 'PoskoTypeController',
        'posko-rekap' => 'PoskoRecapController',
        'posko-kepatuhan' => 'PoskoComplianceController',
        'posko-notifikasi' => 'PoskoNotificationController',
        'oneservice' => 'OneServiceController',
        'satunomor' => 'OneServiceController',
    ];

    protected string $controller = 'HomeController';
    protected string $action = 'index';
    protected array $params = [];

    public function __construct()
    {
        $url = $this->parseUrl();

        // 1. Resolve Controller
        if (!empty($url)) {
            $segment = array_shift($url);
            $candidate = $this->resolveControllerName($segment);
            if ($candidate === null) {
                $this->render404("Controller untuk '{$segment}' tidak ditemukan.");
            }
            $this->controller = $candidate;
        }

        $fullControllerClass = "App\Controllers\\" . $this->controller;

        // 2. Resolve Action Method (hanya action publik milik controller itu sendiri)
        if (!empty($url)) {
            $segment = array_shift($url);
            // Format camelCase jika ada strip (misal: create-report -> createReport)
            $this->action = lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $segment))));
        }

        $method = $this->resolveActionMethod($fullControllerClass, $this->action);
        if ($method === null) {
            $this->render404("Action {$this->action} tidak ditemukan pada {$this->controller}.");
        }

        // 3. Resolve & Validasi Parameters
        $params = $this->resolveParams($method, $url);
        if ($params === null) {
            $this->render404("Parameter tidak valid untuk {$this->controller}::{$this->action}.");
        }
        $this->params = $params;

        // 4. Dispatch Call
        $controllerInstance = new $fullControllerClass();
        $method->invokeArgs($controllerInstance, $this->params);
    }

    /**
     * Petakan segmen URL ke nama class controller yang benar-benar ada
     */
    public static function resolveControllerName(string $segment): ?string
    {
        $key = strtolower($segment);
        if (isset(self::ROUTE_ALIASES[$key])) {
            return self::ROUTE_ALIASES[$key];
        }

        // Hanya huruf/angka, cegah traversal path (mis. "..\\") pada pemuatan file controller
        if (!preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $segment)) {
            return null;
        }

        $candidate = ucfirst($key) . 'Controller';
        $controllerPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $candidate . '.php';

        return is_file($controllerPath) && class_exists("App\Controllers\\" . $candidate) ? $candidate : null;
    }

    /**
     * Action valid: method public non-static yang dideklarasikan di controller itu sendiri
     * (bukan helper warisan Controller seperti render/json/redirect, bukan magic method)
     */
    public static function resolveActionMethod(string $controllerClass, string $action): ?\ReflectionMethod
    {
        if (!preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $action) || !class_exists($controllerClass)) {
            return null;
        }

        $class = new \ReflectionClass($controllerClass);
        if (!$class->hasMethod($action)) {
            return null;
        }

        $method = $class->getMethod($action);
        if (!$method->isPublic() || $method->isStatic() || $method->isConstructor()
            || $method->getDeclaringClass()->getName() !== $class->getName()) {
            return null;
        }

        return $method;
    }

    /**
     * Cocokkan segmen URL dengan parameter method; null bila jumlah/tipe tidak sesuai
     */
    public static function resolveParams(\ReflectionMethod $method, array $segments): ?array
    {
        $segments = array_values($segments);
        if (count($segments) < $method->getNumberOfRequiredParameters()
            || count($segments) > $method->getNumberOfParameters()) {
            return null;
        }

        $params = [];
        foreach ($method->getParameters() as $i => $param) {
            if (!array_key_exists($i, $segments)) {
                break;
            }

            $value = $segments[$i];
            $type = $param->getType();
            if ($type instanceof \ReflectionNamedType && $type->getName() === 'int') {
                if (!preg_match('/^[0-9]{1,9}$/', $value)) {
                    return null;
                }
                $value = (int)$value;
            }
            $params[] = $value;
        }

        return $params;
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

    private function render404(string $message = 'Halaman Tidak Ditemukan'): never
    {
        http_response_code(404);
        // Detail teknis (nama controller/action) hanya untuk development
        $message = \App\Config\Config::isDevelopment()
            ? htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
            : 'Halaman yang Anda cari tidak tersedia.';
        echo "<!DOCTYPE html><html lang='id'><head><meta charset='utf-8'><title>404 - SIGAP POLRI</title>
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
