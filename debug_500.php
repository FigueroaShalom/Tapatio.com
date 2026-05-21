<?php
// ⚠️ ARCHIVO TEMPORAL DE DIAGNÓSTICO - ELIMINAR DESPUÉS
// Accede a: https://life-bel0w.mx/debug_500.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Diagnóstico Life Below</h2>";
echo "<pre>";

// 1. Versión de PHP
echo "✅ PHP versión: " . PHP_VERSION . "\n\n";

// 2. ¿Existe el .env?
$env_path = __DIR__ . '/.env';
if (file_exists($env_path)) {
    echo "✅ .env ENCONTRADO\n";
    $env = parse_ini_file($env_path);
    echo "   DB_HOST: " . ($env['DB_HOST'] ?? '❌ no definido') . "\n";
    echo "   DB_USER: " . ($env['DB_USER'] ?? '❌ no definido') . "\n";
    echo "   DB_PASS: " . (isset($env['DB_PASS']) ? (strlen($env['DB_PASS']) > 0 ? '✅ tiene valor' : '⚠️ está vacío') : '❌ no definido') . "\n";
    echo "   DB_NAME: " . ($env['DB_NAME'] ?? '❌ no definido') . "\n";
} else {
    echo "❌ .env NO ENCONTRADO - Este es probablemente el problema principal\n";
}

echo "\n";

// 3. Probar conexión a BD
echo "--- Prueba de conexión a BD ---\n";
$env_vars = file_exists($env_path) ? parse_ini_file($env_path) : [];
$host     = $env_vars['DB_HOST'] ?? 'localhost';
$usuario  = $env_vars['DB_USER'] ?? 'root';
$password = $env_vars['DB_PASS'] ?? '';
$base     = $env_vars['DB_NAME'] ?? 'life_below_blog';

$conn = @new mysqli($host, $usuario, $password, $base);
if ($conn->connect_error) {
    echo "❌ Error de BD: " . $conn->connect_error . "\n";
} else {
    echo "✅ Conexión a BD exitosa\n";
    $conn->close();
}

echo "\n";

// 4. ¿Existe INCLUDES/functions.php?
echo "--- Archivos críticos ---\n";
$archivos = [
    'INCLUDES/functions.php',
    'database/Conexion_base.php',
    'config.php',
    '.env',
];
foreach ($archivos as $archivo) {
    $existe = file_exists(__DIR__ . '/' . $archivo);
    echo ($existe ? "✅" : "❌") . " $archivo\n";
}

echo "</pre>";
echo "<p><strong>⚠️ Elimina este archivo del servidor cuando termines de diagnosticar.</strong></p>";
