<?php
session_start();
require_once __DIR__ . '/Conexion_base.php';

header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    echo json_encode(['ok' => false, 'msg' => 'No autorizado']);
    exit;
}

$accion = $_POST['accion'] ?? '';
$respuesta = ['ok' => false, 'msg' => ''];

if ($accion === 'crear') {
    $nombre = trim($_POST['nombre']);
    $slug = trim($_POST['slug']) ?: strtolower(str_replace(' ', '-', $nombre));
    $descripcion = trim($_POST['descripcion']);
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    if (empty($nombre)) {
        $respuesta['msg'] = 'El nombre es obligatorio.';
    } else {
        $stmt = $conn->prepare("INSERT INTO categorias (nombre, slug, descripcion, activo) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $nombre, $slug, $descripcion, $activo);
        if ($stmt->execute()) {
            $respuesta = ['ok' => true, 'msg' => '✅ Categoría creada correctamente.'];
        } else {
            $respuesta['msg'] = '❌ Error al crear: ' . $conn->error;
        }
        $stmt->close();
    }
}
elseif ($accion === 'editar') {
    $id = (int)$_POST['id'];
    $nombre = trim($_POST['nombre']);
    $slug = trim($_POST['slug']);
    $descripcion = trim($_POST['descripcion']);
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    if (empty($nombre)) {
        $respuesta['msg'] = 'El nombre es obligatorio.';
    } else {
        $stmt = $conn->prepare("UPDATE categorias SET nombre=?, slug=?, descripcion=?, activo=? WHERE id=?");
        $stmt->bind_param("sssii", $nombre, $slug, $descripcion, $activo, $id);
        if ($stmt->execute()) {
            $respuesta = ['ok' => true, 'msg' => '✅ Categoría actualizada.'];
        } else {
            $respuesta['msg'] = '❌ Error al actualizar: ' . $conn->error;
        }
        $stmt->close();
    }
}
elseif ($accion === 'eliminar') {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM categorias WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $respuesta = ['ok' => true, 'msg' => '✅ Categoría eliminada.'];
    } else {
        $respuesta['msg'] = '❌ Error al eliminar: ' . $conn->error;
    }
    $stmt->close();
}
else {
    $respuesta['msg'] = 'Acción no válida';
}

echo json_encode($respuesta);