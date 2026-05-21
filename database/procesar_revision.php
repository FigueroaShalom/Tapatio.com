<?php
session_start();
require_once __DIR__ . '/Conexion_base.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || !in_array($_SESSION['rol'], ['editor', 'administrador'])) {
    echo json_encode(['ok' => false, 'msg' => 'No autorizado']);
    exit;
}

$accion = $_POST['accion'] ?? '';
$id = (int)($_POST['id_publicacion'] ?? 0);
$observacion = trim($_POST['observacion'] ?? '');
$respuesta = ['ok' => false, 'msg' => ''];

if ($accion === 'aprobar') {
    $stmt = $conn->prepare("UPDATE publicaciones SET estado='aprobado', observacion=NULL WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $respuesta = ['ok' => true, 'msg' => '✅ Artículo aprobado y publicado.'];
    } else {
        $respuesta['msg'] = '❌ Error al aprobar: ' . $conn->error;
    }
}
elseif ($accion === 'rechazar') {
    $observacion = trim($_POST['observacion'] ?? '');
    if (empty($observacion)) {
        $respuesta['msg'] = 'Debes escribir un motivo de rechazo.';
    } else {
        $stmt = $conn->prepare("UPDATE publicaciones SET estado='rechazado', observacion=? WHERE id=?");
        $stmt->bind_param("si", $observacion, $id);
        if ($stmt->execute()) {
            $respuesta = ['ok' => true, 'msg' => '❌ Artículo rechazado.'];
        } else {
            $respuesta['msg'] = 'Error al rechazar: ' . $conn->error;
        }
    }
}
else {
    $respuesta['msg'] = 'Acción no válida';
}

echo json_encode($respuesta);