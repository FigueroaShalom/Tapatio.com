<?php
/**
 * Proxy PHP para GNews API - Carousel del Home
 * Consulta noticias del mar / vida submarina en servidor para evitar CORS
 */
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

define('GNEWS_API_KEY', 'd5b5320a0accff00272ab27733ba94ce');
define('CACHE_TTL_HOME', 3600); // 1 hora

$cache_dir  = __DIR__ . '/../data';
$cache_file = $cache_dir . '/gnews_home_carousel.json';

// Servir desde caché si existe y es reciente
if (file_exists($cache_file) && (time() - filemtime($cache_file)) < CACHE_TTL_HOME) {
    echo file_get_contents($cache_file);
    exit;
}

// Crear directorio de caché si no existe
if (!file_exists($cache_dir)) {
    @mkdir($cache_dir, 0777, true);
}

// Agrupamos las palabras en comillas para búsquedas exactas (funciona mejor en GNews)
$query = '"vida submarina" OR "océano" OR "conservación marina" OR "especies marinas" OR "arrecife"';

// Usamos formato de fecha ISO 8601 y máximo 30 días (límite del plan free)
$url = 'https://gnews.io/api/v4/search?' . http_build_query([
    'q'      => $query,
    'lang'   => 'es',
    'in'     => 'title,description',
    'sortby' => 'publishedAt',
    'max'    => 5,
    'from'   => date('Y-m-d\T00:00:00\Z', strtotime('-30 days')),
    'apikey' => GNEWS_API_KEY,
]);

// Añadimos ignore_errors para atrapar la respuesta aunque sea código 400/403
$ctx = stream_context_create(['http' => [
    'timeout'       => 12,
    'header'        => 'User-Agent: HYDRON/1.0',
    'ignore_errors' => true 
]]);

$response = @file_get_contents($url, false, $ctx);
$datos = json_decode($response, true);

// Verificamos si hubo un fallo de conexión o si la API devolvió un mensaje de error
if (!$response || isset($datos['errors'])) {
    // Retornamos el error sin guardarlo en caché para que intente de nuevo luego
    echo json_encode([
        'articles' => [], 
        'error' => true, 
        'message' => 'Fallo en la API: ' . ($datos['errors'][0] ?? 'No hay conexión')
    ]);
    exit;
}

// Si trae noticias y no hay errores, se guarda en caché y se muestra
file_put_contents($cache_file, $response);
echo $response;