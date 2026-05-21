<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>✅ Verificando a todos los usuarios</h2><pre>";

require_once __DIR__ . '/database/Conexion_base.php';

if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

$sql = "UPDATE usuarios SET verified = 1";

if ($conn->query($sql) === TRUE) {
    echo "¡ÉXITO! 🎉\n";
    echo "Se han actualizado " . $conn->affected_rows . " usuarios.\n";
    echo "Ahora TODOS los usuarios pueden iniciar sesión sin problemas.\n";
} else {
    echo "❌ Error al actualizar usuarios: " . $conn->error . "\n";
}

echo "</pre>";
