<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Revisión de la Tabla de Usuarios</h2><pre>";

require_once __DIR__ . '/database/Conexion_base.php';

if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

// 1. Ver columnas de la tabla usuarios
echo "--- COLUMNAS EN LA TABLA 'usuarios' ---\n";
$result = $conn->query("DESCRIBE usuarios");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo str_pad($row['Field'], 20) . " | " . $row['Type'] . "\n";
    }
} else {
    echo "❌ Error al leer tabla usuarios: " . $conn->error . "\n";
}

// 2. Ver los primeros 5 usuarios (sin mostrar contraseñas completas por seguridad)
echo "\n--- PRIMEROS 5 USUARIOS ---\n";
$result2 = $conn->query("SELECT id, user, email, verified FROM usuarios LIMIT 5");
if ($result2) {
    if ($result2->num_rows === 0) {
        echo "⚠️ La tabla usuarios está VACÍA.\n";
    } else {
        while ($row = $result2->fetch_assoc()) {
            echo "ID: " . str_pad($row['id'], 3) . " | User: " . str_pad($row['user'], 15) . " | Verified: " . ($row['verified'] ?? 'NULL') . "\n";
        }
    }
} else {
    echo "❌ Error al leer datos de usuarios: " . $conn->error . "\n";
}

echo "</pre>";
