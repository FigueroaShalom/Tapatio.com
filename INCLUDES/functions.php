<?php
function cargar_configuracion() {
    global $conn;
    $result = $conn->query("SELECT clave, valor FROM configuracion");
    $config = [];
    while ($row = $result->fetch_assoc()) {
        $config[$row['clave']] = $row['valor'];
    }
    return $config;
}