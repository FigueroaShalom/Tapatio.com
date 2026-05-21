<?php
session_start();

// Cargar variables de entorno
$env_path = __DIR__ . '/.env';
$env_vars = [];
if (file_exists($env_path)) {
    $env_vars = parse_ini_file($env_path);
}

// Headers de seguridad HTTP
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
    
define('SITE_NAME', 'HYDRON');
define('SITE_URL', 'http://localhost/HYDRON/'); // Esto podría ir al .env luego
define('UPLOADS_DIR', __DIR__ . '/uploads/');
define('ADMIN_USER', $env_vars['ADMIN_USER'] ?? 'admin');
define('ADMIN_PASS', $env_vars['ADMIN_PASS'] ?? 'admin123');

// Google OAuth
$is_local = ($_SERVER['SERVER_NAME'] === 'localhost' || ($_SERVER['SERVER_ADDR'] ?? '') === '127.0.0.1');

define('GOOGLE_CLIENT_ID',     $env_vars['GOOGLE_CLIENT_ID'] ?? getenv('GOOGLE_CLIENT_ID') ?? '');
define('GOOGLE_CLIENT_SECRET', $env_vars['GOOGLE_CLIENT_SECRET'] ?? getenv('GOOGLE_CLIENT_SECRET') ?? '');
define('GOOGLE_REDIRECT_URI',  $is_local
    ? 'http://localhost/Life-Below/auth/google_callback.php'
    : 'https://life-bel0w.mx/auth/google_callback.php'
);

// Crear carpeta uploads si no existe
if (!file_exists(UPLOADS_DIR)) {
    mkdir(UPLOADS_DIR, 0777, true);
}
?>