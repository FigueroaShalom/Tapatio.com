<?php
session_start();
require_once __DIR__ . '/Conexion_base.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['ok' => false, 'msg' => 'Sesión no válida']);
    exit;
}

$accion = $_POST['accion'] ?? '';
$id = (int)($_POST['id_borrador'] ?? 0);
$respuesta = ['ok' => false, 'msg' => ''];

if ($accion === 'publicar') {
    // Si aún quieres conservar publicación directa (opcional)
    $stmt = $conn->prepare("UPDATE publicaciones SET estado='publicado' WHERE id=? AND id_autor=? AND estado='borrador'");
    $stmt->bind_param("ii", $id, $_SESSION['id']);
    if ($stmt->execute()) {
        $respuesta = ['ok' => true, 'msg' => '✅ Borrador publicado.'];
    } else {
        $respuesta['msg'] = '❌ Error al publicar: ' . $conn->error;
    }
}
elseif ($accion === 'revisar') {
    // Nueva acción: enviar a revisión (estado pendiente)
    $stmt = $conn->prepare("UPDATE publicaciones SET estado='pendiente' WHERE id=? AND id_autor=? AND estado='borrador'");
    $stmt->bind_param("ii", $id, $_SESSION['id']);
    if ($stmt->execute()) {
        $respuesta = ['ok' => true, 'msg' => '✅ Borrador enviado a revisión.'];
    } else {
        $respuesta['msg'] = '❌ Error al enviar a revisión: ' . $conn->error;
    }
}
elseif ($accion === 'eliminar') {
    $stmt = $conn->prepare("DELETE FROM publicaciones WHERE id=? AND id_autor=? AND estado='borrador'");
    $stmt->bind_param("ii", $id, $_SESSION['id']);
    if ($stmt->execute()) {
        $respuesta = ['ok' => true, 'msg' => '✅ Borrador eliminado.'];
    } else {
        $respuesta['msg'] = '❌ Error al eliminar: ' . $conn->error;
    }
}
elseif ($accion === 'actualizar') {
    $titulo    = trim($_POST['titulo'] ?? '');
    $contenido = trim($_POST['contenido'] ?? '');
    $categoria = $_POST['categoria'] ?? 'peces';
    $imagen    = trim($_POST['imagen'] ?? '');
    if (empty($titulo)) {
        $respuesta['msg'] = 'El título es obligatorio.';
    } else {
        $stmt = $conn->prepare("UPDATE publicaciones SET titulo=?, contenido=?, categoria=?, imagen=? WHERE id=? AND id_autor=? AND estado='borrador'");
        $stmt->bind_param("ssssii", $titulo, $contenido, $categoria, $imagen, $id, $_SESSION['id']);
        if ($stmt->execute()) {
            $respuesta = ['ok' => true, 'msg' => '✅ Borrador actualizado.'];
        } else {
            $respuesta['msg'] = '❌ Error al actualizar: ' . $conn->error;
        }
    }
}
else {
    $respuesta['msg'] = 'Acción no válida';
}

echo json_encode($respuesta);