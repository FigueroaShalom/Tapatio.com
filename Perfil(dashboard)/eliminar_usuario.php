<?php
session_start();  // ← AGREGAR ESTO
require_once __DIR__ . '/../database/Conexion_base.php';

// 🔒 Solo admin
if (!isset($_SESSION['id']) || $_SESSION['rol'] != "administrador") {
    echo "Acceso no autorizado";
    exit();
}

if(isset($_GET['id'])){
    $idEliminar = intval($_GET['id']);
    $idActual = $_SESSION['id'];

    if($idEliminar == $idActual){
        echo "No puedes eliminar tu propia cuenta";
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $idEliminar);

    if($stmt->execute()){
        echo "ok";
    } else {
        echo "Error al eliminar";
    }
    $stmt->close();
}
?>