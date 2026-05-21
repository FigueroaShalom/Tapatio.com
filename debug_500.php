<?php
// ⚠️ ARCHIVO TEMPORAL DE DIAGNÓSTICO v2 - ELIMINAR DESPUÉS
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Diagnóstico v2 - Buscando el error exacto</h2><pre>";

// Simular lo que hace index.php paso a paso

// PASO 1: config.php
echo "--- PASO 1: config.php ---\n";
try {
    ob_start();
    require_once __DIR__ . '/config.php';
    $out = ob_get_clean();
    echo "✅ config.php cargó OK\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "❌ config.php FALLÓ: " . $e->getMessage() . " en línea " . $e->getLine() . "\n";
    die("</pre><p>🛑 Detenido en config.php</p>");
}

// PASO 2: Conexion_base.php
echo "\n--- PASO 2: database/Conexion_base.php ---\n";
try {
    ob_start();
    require_once __DIR__ . '/database/Conexion_base.php';
    $out = ob_get_clean();
    echo "✅ Conexion_base.php cargó OK\n";
    if (isset($conn)) echo "✅ \$conn disponible\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "❌ Conexion_base.php FALLÓ: " . $e->getMessage() . " en línea " . $e->getLine() . "\n";
    die("</pre><p>🛑 Detenido en Conexion_base.php</p>");
}

// PASO 3: INCLUDES/functions.php
echo "\n--- PASO 3: INCLUDES/functions.php ---\n";
try {
    ob_start();
    require_once __DIR__ . '/INCLUDES/functions.php';
    $out = ob_get_clean();
    echo "✅ functions.php cargó OK\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "❌ functions.php FALLÓ: " . $e->getMessage() . " en línea " . $e->getLine() . "\n";
    die("</pre><p>🛑 Detenido en functions.php</p>");
}

// PASO 4: cargar_configuracion()
echo "\n--- PASO 4: cargar_configuracion() ---\n";
try {
    $config = cargar_configuracion();
    echo "✅ cargar_configuracion() OK\n";
    echo "   site_name: " . ($config['site_name'] ?? '❌ no existe') . "\n";
    echo "   site_description: " . ($config['site_description'] ?? '❌ no existe') . "\n";
} catch (Throwable $e) {
    echo "❌ cargar_configuracion() FALLÓ: " . $e->getMessage() . " en línea " . $e->getLine() . "\n";
    die("</pre><p>🛑 Detenido en cargar_configuracion()</p>");
}

// PASO 5: header.php
echo "\n--- PASO 5: header.php ---\n";
try {
    ob_start();
    $_GET['section'] = 'inicio';
    include __DIR__ . '/header.php';
    $out = ob_get_clean();
    echo "✅ header.php cargó OK (" . strlen($out) . " bytes generados)\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "❌ header.php FALLÓ: " . $e->getMessage() . " en línea " . $e->getLine() . " del archivo: " . $e->getFile() . "\n";
    die("</pre><p>🛑 Detenido en header.php</p>");
}

// PASO 6: INCLUDES/inicio.php
echo "\n--- PASO 6: INCLUDES/inicio.php ---\n";
try {
    ob_start();
    include __DIR__ . '/INCLUDES/inicio.php';
    $out = ob_get_clean();
    echo "✅ inicio.php cargó OK (" . strlen($out) . " bytes)\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "❌ inicio.php FALLÓ: " . $e->getMessage() . " en línea " . $e->getLine() . "\n";
    die("</pre><p>🛑 Detenido en inicio.php</p>");
}

// PASO 7: footer.php
echo "\n--- PASO 7: footer.php ---\n";
try {
    ob_start();
    include __DIR__ . '/footer.php';
    $out = ob_get_clean();
    echo "✅ footer.php cargó OK (" . strlen($out) . " bytes)\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "❌ footer.php FALLÓ: " . $e->getMessage() . " en línea " . $e->getLine() . "\n";
    die("</pre><p>🛑 Detenido en footer.php</p>");
}

echo "\n✅✅✅ TODOS LOS PASOS PASARON - El problema puede ser de permisos o .htaccess\n";
echo "</pre>";
