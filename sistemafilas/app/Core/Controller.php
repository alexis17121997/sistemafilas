<?php

abstract class Controller {

    protected function view(string $view, array $data = [], string $layout = 'main'): void {
        extract($data, EXTR_SKIP);
        $viewFile   = APP_PATH . '/app/Views/' . $view . '.php';
        $layoutFile = APP_PATH . '/app/Views/layouts/' . $layout . '.php';

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require $layoutFile;
    }

    protected function json(mixed $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect(string $url): void {
        header('Location: ' . APP_URL . $url);
        exit;
    }

    protected function back(): void {
        $ref = $_SERVER['HTTP_REFERER'] ?? APP_URL . '/';
        header('Location: ' . $ref);
        exit;
    }

    protected function requireRole(string ...$roles): void {
        Auth::requireRole(...$roles);
    }

    protected function requireAuth(): void {
        Auth::check();
    }

    protected function currentUser(): ?array {
        return Auth::user();
    }

    protected function flash(string $type, string $message): void {
        $_SESSION['flash'] = compact('type', 'message');
    }

    protected function getFlash(): ?array {
        if (isset($_SESSION['flash'])) {
            $f = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $f;
        }
        return null;
    }

    protected function input(string $key, mixed $default = null): mixed {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function post(string $key, mixed $default = null): mixed {
        return $_POST[$key] ?? $default;
    }

    protected function get(string $key, mixed $default = null): mixed {
        return $_GET[$key] ?? $default;
    }

    protected function sanitize(string $value): string {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }
}
