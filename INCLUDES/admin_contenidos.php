<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    echo "<div class='alert alert-danger'>Acceso no autorizado</div>";
    exit;
}
require_once __DIR__ . '/../database/Conexion_base.php';

$mensaje = '';
$tipo = $_GET['tipo'] ?? 'articulos';

// Eliminar contenido
if (isset($_GET['eliminar']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($tipo === 'articulos') {
        $conn->query("DELETE FROM publicaciones WHERE id = $id");
        $mensaje = "Artículo eliminado.";
    } elseif ($tipo === 'videos') {
        // También eliminar sus categorías
        $conn->query("DELETE FROM video_categorias WHERE video_id = $id");
        $conn->query("DELETE FROM videos WHERE id = $id");
        $mensaje = "Video eliminado.";
    }
}

// Listar artículos
$articulos = [];
$videos = [];

if ($tipo === 'articulos') {
    $result = $conn->query("
        SELECT p.id, p.titulo, p.categoria, p.estado, p.fecha_creacion, u.user as autor
        FROM publicaciones p
        JOIN usuarios u ON p.id_autor = u.id
        ORDER BY p.fecha_creacion DESC
    ");
    $articulos = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $result = $conn->query("
        SELECT v.id, v.titulo, v.fecha_publicacion, u.user as autor
        FROM videos v
        JOIN usuarios u ON v.id_autor = u.id
        ORDER BY v.fecha_publicacion DESC
    ");
    $videos = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="admin-contenidos">
    <h3 class="fw-bold mb-4">📄 Supervisar contenidos</h3>
    
    <?php if ($mensaje): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>
    
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $tipo === 'articulos' ? 'active' : ''; ?>" href="?section=dashboard&modulo=admin_contenidos&tipo=articulos">Artículos</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tipo === 'videos' ? 'active' : ''; ?>" href="?section=dashboard&modulo=admin_contenidos&tipo=videos">Videos</a>
        </li>
    </ul>
    
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Autor</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($tipo === 'articulos'): ?>
                    <?php foreach ($articulos as $art): ?>
                    <tr>
                        <td><?php echo $art['id']; ?></td>
                        <td><?php echo htmlspecialchars($art['titulo']); ?></td>
                        <td><?php echo htmlspecialchars($art['autor']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($art['fecha_creacion'])); ?></td>
                        <td><?php echo ucfirst($art['estado']); ?></td>
                        <td>
                            <a href="index.php?section=articulos&post=<?php echo $art['id']; ?>" class="btn btn-sm btn-info" target="_blank">Ver</a>
                            <a href="javascript:void(0)" onclick="cargar('editar_contenido?id=<?php echo $art['id']; ?>')" class="btn btn-sm btn-warning">Editar</a>
                            <a href="?section=dashboard&modulo=admin_contenidos&tipo=articulos&eliminar=1&id=<?php echo $art['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este artículo?')">Eliminar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach ($videos as $vid): ?>
                    <tr>
                        <td><?php echo $vid['id']; ?></td>
                        <td><?php echo htmlspecialchars($vid['titulo']); ?></td>
                        <td><?php echo htmlspecialchars($vid['autor']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($vid['fecha_publicacion'])); ?></td>
                        <td>-</td>
                        <td>
                            <a href="index.php?section=watch&video=<?php echo $vid['id']; ?>" class="btn btn-sm btn-info" target="_blank">Ver</a>
                            <a href="crear_contenido.php?editar_vid=<?php echo $vid['id']; ?>" class="btn btn-sm btn-warning">Editar</a>
                            <a href="?section=dashboard&modulo=admin_contenidos&tipo=videos&eliminar=1&id=<?php echo $vid['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este video?')">Eliminar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>