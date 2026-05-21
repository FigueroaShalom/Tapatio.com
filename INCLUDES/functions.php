<?php
// Cargar configuración desde la BD y guardar en sesión
function cargar_configuracion() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['configuracion'])) {
        global $conn;
        $result = $conn->query("SELECT clave, valor FROM configuracion");
        $config = [];
        while ($row = $result->fetch_assoc()) {
            $config[$row['clave']] = $row['valor'];
        }
        $_SESSION['configuracion'] = $config;
    }
    return $_SESSION['configuracion'];
}
?>