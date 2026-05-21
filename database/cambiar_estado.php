<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo 'no_session';
    exit;
}
require_once 'Conexion_base.php';

$id = (int)$_POST['id'];
$nuevo_estado = $_POST['estado'] ?? '';
if (!in_array($nuevo_estado, ['pendiente', 'borrador'])) {
    echo 'estado_invalido';
    exit;
}

$stmt = $conn->prepare("UPDATE publicaciones SET estado = ? WHERE id = ? AND id_autor = ?");
$stmt->bind_param("sii", $nuevo_estado, $id, $_SESSION['user_id']);
if ($stmt->execute()) {
    echo 'ok';
} else {
    echo 'error';
}
?>