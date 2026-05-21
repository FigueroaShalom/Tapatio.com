<?php
// Script temporal para limpiar archivos antiguos en el servidor de IONOS
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Limpieza de Archivos Antiguos</h2><pre>";

$archivo_problema = __DIR__ . '/database/config_db.php';

if (file_exists($archivo_problema)) {
    if (unlink($archivo_problema)) {
        echo "✅ SE ELIMINÓ EXITOSAMENTE: $archivo_problema\n";
    } else {
        echo "❌ NO SE PUDO ELIMINAR: $archivo_problema (Problemas de permisos)\n";
    }
} else {
    echo "✅ EL ARCHIVO NO EXISTE: $archivo_problema\n";
}

// Probar conexión BD de nuevo usando Conexion_base.php (que ahora sí debería usar el .env)
echo "\nProbando conexión a BD con Conexion_base.php...\n";
try {
    require_once __DIR__ . '/database/Conexion_base.php';
    if (isset($conn) && !$conn->connect_error) {
        echo "✅ CONEXIÓN EXITOSA AL USAR .ENV\n";
    } else {
        echo "❌ FALLÓ CONEXIÓN: " . ($conn->connect_error ?? 'Desconocido');
    }
} catch (Throwable $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
