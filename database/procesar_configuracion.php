<?php
session_start();
require_once __DIR__ . '/Conexion_base.php';

header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    echo json_encode(['ok' => false, 'msg' => 'No autorizado']);
    exit;
}

$errores = [];
foreach ($_POST as $clave => $valor) {
    if ($clave === 'accion') continue;
    $valor = trim($valor);
    $stmt = $conn->prepare("UPDATE configuracion SET valor = ? WHERE clave = ?");
    $stmt->bind_param("ss", $valor, $clave);
    if (!$stmt->execute()) {
        $errores[] = "Error en $clave: " . $stmt->error;
    }
    $stmt->close();
}

if (empty($errores)) {
    // Limpiar caché de sesión si existe
    unset($_SESSION['configuracion']);
    echo json_encode(['ok' => true, 'msg' => '✅ Configuración actualizada.']);
} else {
    echo json_encode(['ok' => false, 'msg' => implode('<br>', $errores)]);
}