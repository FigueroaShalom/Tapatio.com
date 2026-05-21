<?php
// ⚠️ ARCHIVO TEMPORAL DE DIAGNÓSTICO v3 - ELIMINAR DESPUÉS
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Diagnóstico v3</h2><pre>";

$env_path = __DIR__ . '/.env';
if (!file_exists($env_path)) {
    die("❌ ERROR FATAL: El archivo .env no existe en el servidor.");
}

$env_vars = parse_ini_file($env_path);
$host     = $env_vars['DB_HOST'] ?? 'localhost';
$usuario  = $env_vars['DB_USER'] ?? 'root';
$password = $env_vars['DB_PASS'] ?? '';
$base     = $env_vars['DB_NAME'] ?? 'life_below_blog';

echo "Intentando conectar a BD...\n";
echo "Host: " . $host . "\n";
echo "User: " . $usuario . "\n";
echo "Pass: " . (empty($password) ? 'VACÍA' : 'OK (tiene contenido)') . "\n";
echo "DB:   " . $base . "\n\n";

mysqli_report(MYSQLI_REPORT_OFF); // Evita que arroje una excepción fatal para poder capturar el error
$conn = @new mysqli($host, $usuario, $password, $base);

if ($conn->connect_error) {
    echo "❌ LA CONEXIÓN A LA BASE DE DATOS SIGUE FALLANDO:\n";
    echo $conn->connect_error . "\n";
    die("\nPor favor, verifica que la contraseña en GitHub Secrets coincida EXACTAMENTE con la de IONOS.");
} else {
    echo "✅ Conexión a BD EXITOSA.\n";
    
    // Si la conexión es exitosa, veamos qué más puede estar fallando al cargar la página principal.
    echo "\nProbando cargar el resto de la página principal paso a paso:\n";
    
    try {
        ob_start();
        $_GET['section'] = 'inicio';
        require_once __DIR__ . '/config.php';
        require_once __DIR__ . '/database/Conexion_base.php';
        require_once __DIR__ . '/INCLUDES/functions.php';
        $config = cargar_configuracion();
        include __DIR__ . '/header.php';
        include __DIR__ . '/INCLUDES/inicio.php';
        include __DIR__ . '/footer.php';
        ob_end_clean();
        echo "✅ La página principal carga correctamente a nivel PHP.\n";
    } catch (Throwable $e) {
        ob_end_clean();
        echo "❌ ERROR FATAL DURANTE LA CARGA: " . $e->getMessage() . " en " . $e->getFile() . " línea " . $e->getLine() . "\n";
    }
}
echo "</pre>";
