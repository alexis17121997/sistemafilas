<?php

class Auth {

    public static function attempt(string $username, string $password): bool {

        // ── Buscar usuario activo ──────────────────────────────────────────
        $row = Database::queryOne(
            'SELECT u.*, r.name AS role_name, b.name AS branch_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN branches b ON b.id = u.branch_id
             WHERE u.username = $1 AND u.active = TRUE',
            [$username]
        );

        if (!$row) return false;

        // ── Verificar contraseña (PHP bcrypt) ─────────────────────────────
        if (!password_verify($password, $row['password_hash'])) return false;

        // ── Guardar sesión ────────────────────────────────────────────────
        $_SESSION['user'] = [
            'id'          => (int)$row['id'],
            'username'    => $row['username'],
            'full_name'   => $row['full_name'],
            'role'        => $row['role_name'],
            'role_id'     => (int)$row['role_id'],
            'branch_id'   => $row['branch_id'] ? (int)$row['branch_id'] : null,
            'branch_name' => $row['branch_name'] ?? '',
        ];

        Database::execute(
            'UPDATE users SET last_login = NOW() WHERE id = $1',
            [$row['id']]
        );

        return true;
    }

    public static function logout(): void {
        session_destroy();
    }

    public static function user(): ?array {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): void {
        if (!isset($_SESSION['user'])) {
            header('Location: ' . APP_URL . '/login');
            exit;
        }
    }

    public static function requireRole(string ...$roles): void {
        self::check();
        $user = self::user();
        if (!in_array($user['role'], $roles, true)) {
            http_response_code(403);
            die('<div style="font-family:sans-serif;padding:2rem;color:#c00">
                 <h2>Acceso denegado</h2>
                 <p>No tiene permiso para esta sección.</p>
                 <a href="' . APP_URL . '/login">Volver al login</a></div>');
        }
    }

    public static function isRole(string $role): bool {
        return (self::user()['role'] ?? '') === $role;
    }

    public static function hasRole(string ...$roles): bool {
        return in_array(self::user()['role'] ?? '', $roles, true);
    }
}
