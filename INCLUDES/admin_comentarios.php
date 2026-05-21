<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    echo "<div class='alert alert-danger'>Acceso no autorizado</div>";
    exit;
}
require_once __DIR__ . '/../database/Conexion_base.php';

$mensaje = '';
$tipo = $_GET['tipo'] ?? 'articulos';

// Eliminar comentario
if (isset($_GET['eliminar']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($tipo === 'articulos') {
        $conn->query("DELETE FROM comentarios WHERE id = $id");
    } else {
        $conn->query("DELETE FROM comentarios_videos WHERE id = $id");
    }
    $mensaje = "Comentario eliminado.";
}

// Obtener comentarios de artículos
$comentarios_articulos = [];
$comentarios_videos = [];

if ($tipo === 'articulos') {
    $result = $conn->query("
        SELECT c.id, c.comentario, c.fecha, u.user as autor, p.titulo as contenido_titulo
        FROM comentarios c
        JOIN usuarios u ON c.id_usuario = u.id
        JOIN publicaciones p ON c.id_publicacion = p.id
        ORDER BY c.fecha DESC
        LIMIT 100
    ");
    $comentarios_articulos = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $result = $conn->query("
        SELECT cv.id, cv.contenido, cv.fecha, u.user as autor, v.titulo as contenido_titulo
        FROM comentarios_videos cv
        JOIN usuarios u ON cv.usuario_id = u.id
        JOIN videos v ON cv.video_id = v.id
        ORDER BY cv.fecha DESC
        LIMIT 100
    ");
    $comentarios_videos = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="admin-comentarios">
    <h3 class="fw-bold mb-4">💬 Moderar comentarios</h3>
    
    <?php if ($mensaje): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>
    
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $tipo === 'articulos' ? 'active' : ''; ?>" href="?section=dashboard&modulo=admin_comentarios&tipo=articulos">Comentarios en artículos</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tipo === 'videos' ? 'active' : ''; ?>" href="?section=dashboard&modulo=admin_comentarios&tipo=videos">Comentarios en videos</a>
        </li>
    </ul>
    
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Contenido asociado</th>
                    <th>Autor</th>
                    <th>Comentario</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($tipo === 'articulos'): ?>
                    <?php foreach ($comentarios_articulos as $com): ?>
                    <tr>
                        <td><?php echo $com['id']; ?></td>
                        <td><?php echo htmlspecialchars($com['contenido_titulo']); ?></td>
                        <td><?php echo htmlspecialchars($com['autor']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars(substr($com['comentario'], 0, 100))); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($com['fecha'])); ?></td>
                        <td>
                            <a href="?section=dashboard&modulo=admin_comentarios&tipo=articulos&eliminar=1&id=<?php echo $com['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este comentario?')">Eliminar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach ($comentarios_videos as $com): ?>
                    <tr>
                        <td><?php echo $com['id']; ?></td>
                        <td><?php echo htmlspecialchars($com['contenido_titulo']); ?></td>
                        <td><?php echo htmlspecialchars($com['autor']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars(substr($com['contenido'], 0, 100))); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($com['fecha'])); ?></td>
                        <td>
                            <a href="?section=dashboard&modulo=admin_comentarios&tipo=videos&eliminar=1&id=<?php echo $com['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este comentario?')">Eliminar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>