<?php
session_start();
require_once __DIR__ . '/Conexion_base.php';

header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    echo json_encode(['ok' => false, 'msg' => 'No autorizado']);
    exit;
}

$accion = $_POST['accion'] ?? '';
$tipo = $_POST['tipo'] ?? '';
$id = (int)($_POST['id'] ?? 0);
$respuesta = ['ok' => false, 'msg' => ''];

if ($accion === 'eliminar') {
    if ($tipo === 'articulos') {
        $stmt = $conn->prepare("DELETE FROM comentarios WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $respuesta = ['ok' => true, 'msg' => '✅ Comentario eliminado.'];
        } else {
            $respuesta['msg'] = '❌ Error al eliminar comentario: ' . $conn->error;
        }
        $stmt->close();
    } elseif ($tipo === 'videos') {
        $stmt = $conn->prepare("DELETE FROM comentarios_videos WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $respuesta = ['ok' => true, 'msg' => '✅ Comentario eliminado.'];
        } else {
            $respuesta['msg'] = '❌ Error al eliminar comentario: ' . $conn->error;
        }
        $stmt->close();
    } else {
        $respuesta['msg'] = 'Tipo de comentario no válido.';
    }
} else {
    $respuesta['msg'] = 'Acción no válida.';
}

echo json_encode($respuesta);