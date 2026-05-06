<?php
// ─── Application Configuration ───────────────────────────────────────────────

define('APP_NAME',    'Sistema de Filas – Hospital');
define('APP_VERSION', '1.0.0');

// ── AUTO-DETECT base URL and path ────────────────────────────────────────────
// Works regardless of where the folder is placed in XAMPP htdocs.
// No manual changes needed when you rename or move the folder.

define('APP_PATH', dirname(__DIR__));

if (!defined('APP_URL')) {
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Detect the subfolder path (e.g. /desarrollo/sistemafilas)
    $script   = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    // Remove /index.php from the end to get the base path
    $basePath = rtrim(dirname($script), '/');
    define('APP_URL', $scheme . '://' . $host . $basePath);
}

// ── Also auto-fix .htaccess RewriteBase if needed ────────────────────────────
// (Only runs once, when APP_URL is first detected)
$htaccess = APP_PATH . '/.htaccess';
if (file_exists($htaccess)) {
    $basePath = parse_url(APP_URL, PHP_URL_PATH) . '/';
    $content  = file_get_contents($htaccess);
    // Update RewriteBase to match current location
    $updated  = preg_replace('/RewriteBase\s+\S+/', 'RewriteBase ' . $basePath, $content);
    if ($updated !== $content) {
        file_put_contents($htaccess, $updated);
    }
}

// Session
define('SESSION_NAME',     'cq_session');
define('SESSION_LIFETIME', 28800);   // 8 hours

// Timezone
date_default_timezone_set('America/Mexico_City');

// Error reporting (1 = development, 0 = production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
