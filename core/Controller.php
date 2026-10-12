<?php
// core/Controller.php

class Controller {
    protected function render(string $view, array $data = []): void {
        extract($data);
        $viewFile = ROOT_PATH . '/views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            die("View not found: $view");
        }
        require $viewFile;
    }

    protected function redirect(string $url): void {
        header('Location: ' . BASE_URL . $url);
        exit;
    }

    protected function json(mixed $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function requireLogin(): void {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/auth/login');
        }
    }

    protected function requireRole(string ...$roles): void {
        $this->requireLogin();
        if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
            http_response_code(403);
            die('Bạn không có quyền truy cập trang này.');
        }
    }

    protected function isPost(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function input(string $key, mixed $default = ''): mixed {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function flash(string $key, string $msg): void {
        $_SESSION['flash'][$key] = $msg;
    }

    protected function getFlash(string $key): ?string {
        $msg = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
}
