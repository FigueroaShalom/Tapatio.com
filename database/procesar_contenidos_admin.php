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
        $stmt = $conn->prepare("DELETE FROM publicaciones WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $respuesta = ['ok' => true, 'msg' => '✅ Artículo eliminado.'];
        } else {
            $respuesta['msg'] = '❌ Error al eliminar artículo: ' . $conn->error;
        }
        $stmt->close();
    } elseif ($tipo === 'videos') {
        // Eliminar categorías asociadas
        $conn->query("DELETE FROM video_categorias WHERE video_id = $id");
        $stmt = $conn->prepare("DELETE FROM videos WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $respuesta = ['ok' => true, 'msg' => '✅ Video eliminado.'];
        } else {
            $respuesta['msg'] = '❌ Error al eliminar video: ' . $conn->error;
        }
        $stmt->close();
    } else {
        $respuesta['msg'] = 'Tipo de contenido no válido.';
    }
} else {
    $respuesta['msg'] = 'Acción no válida.';
}

echo json_encode($respuesta);