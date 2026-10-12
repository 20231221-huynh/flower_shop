<?php
// core/Router.php

class Router {
    private array $routes = [];

    public function get(string $path, string $controller, string $method): void {
        $this->routes[] = ['GET', $path, $controller, $method];
    }

    public function post(string $path, string $controller, string $method): void {
        $this->routes[] = ['POST', $path, $controller, $method];
    }

    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Loại bỏ base path (ví dụ: /flower_shop/public)
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $uri      = '/' . ltrim(substr($uri, strlen($basePath)), '/');
        $uri      = $uri === '' ? '/' : $uri;

        foreach ($this->routes as [$routeMethod, $path, $controllerPath, $action]) {
            $pattern = $this->pathToRegex($path);
            if ($method === $routeMethod && preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                $params = array_values($matches);

                // 1. Nạp file controller (Ví dụ: customer/HomeController.php)
                $file = ROOT_PATH . '/app/controllers/' . $controllerPath . '.php';
                
                if (file_exists($file)) {
                    require_once $file;
                } else {
                    die("Lỗi: Không tìm thấy file controller tại $file");
                }

                // 2. Lấy tên Class thực tế (Ví dụ: "customer/HomeController" -> "HomeController")
                $className = basename($controllerPath);

                // 3. Khởi tạo đối tượng từ tên Class đã tách
                if (class_exists($className)) {
                    $obj = new $className();
                    call_user_func_array([$obj, $action], $params);
                    return;
                } else {
                    die("Lỗi: File tồn tại nhưng class '$className' không được định nghĩa.");
                }
            }
        }

        http_response_code(404);
        if (file_exists(ROOT_PATH . '/views/layouts/404.php')) {
            require ROOT_PATH . '/views/layouts/404.php';
        } else {
            echo "404 - Page Not Found";
        }
    }

    private function pathToRegex(string $path): string {
        $pattern = preg_replace('/\{[a-z_]+\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
}