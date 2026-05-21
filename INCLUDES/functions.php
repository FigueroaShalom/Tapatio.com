<?php
function cargar_configuracion() {
    global $conn;

    // Valores por defecto en caso de que la tabla no exista aún
    $defaults = [
        'site_name'        => 'Life Below',
        'site_description' => 'Comunidad de conservación marina',
    ];

    // Crear tabla si no existe
    $conn->query("CREATE TABLE IF NOT EXISTS `configuracion` (
        `clave` VARCHAR(100) NOT NULL PRIMARY KEY,
        `valor` TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Insertar valores por defecto si la tabla está vacía
    foreach ($defaults as $clave => $valor) {
        $conn->query("INSERT IGNORE INTO `configuracion` (`clave`, `valor`) VALUES ('$clave', '$valor')");
    }

    $result = $conn->query("SELECT clave, valor FROM configuracion");
    $config = $defaults; // Siempre parte con los defaults
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $config[$row['clave']] = $row['valor'];
        }
    }
    return $config;
}