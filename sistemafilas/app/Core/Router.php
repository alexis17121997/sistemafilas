<?php

class Router {
    private array $routes = [];

    public function get(string $path, $handler): void  { $this->addRoute('GET',  $path, $handler); }
    public function post(string $path, $handler): void { $this->addRoute('POST', $path, $handler); }

    private function addRoute(string $method, string $path, $handler): void {
        $this->routes[] = compact('method', 'path', 'handler');
    }

    public function dispatch(): void {
        $method  = $_SERVER['REQUEST_METHOD'];
        $uri     = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Strip base path (e.g. /clinic-queue)
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if ($base !== '' && strpos($uri, $base) === 0) {
            $uri = substr($uri, strlen($base));
        }
        $uri = '/' . trim($uri, '/');
        if ($uri === '') $uri = '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            $pattern = preg_replace('/\{[a-zA-Z_]+\}/', '([^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                $handler = $route['handler'];

                if (is_callable($handler)) {
                    call_user_func_array($handler, $matches);
                } elseif (is_string($handler)) {
                    [$class, $method2] = explode('@', $handler);
                    $obj = new $class();
                    call_user_func_array([$obj, $method2], $matches);
                }
                return;
            }
        }

        http_response_code(404);
        require APP_PATH . '/app/Views/errors/404.php';
    }
}
