<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    echo "<div class='alert alert-danger'>Acceso no autorizado</div>";
    exit;
}
require_once __DIR__ . '/../database/Conexion_base.php';

$tipo = $_GET['tipo'] ?? 'articulos';
$mensaje = '';

// Obtener comentarios según el tipo
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
    <div id="mensajeComentarios" class="mb-3"></div>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $tipo === 'articulos' ? 'active' : ''; ?>" href="javascript:void(0)" onclick="cambiarTipo('articulos')">Comentarios en artículos</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tipo === 'videos' ? 'active' : ''; ?>" href="javascript:void(0)" onclick="cambiarTipo('videos')">Comentarios en videos</a>
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
            <tbody id="tablaComentarios">
                <?php if ($tipo === 'articulos'): ?>
                    <?php foreach ($comentarios_articulos as $com): ?>
                    <tr id="fila-<?php echo $com['id']; ?>">
                        <td><?php echo $com['id']; ?></td>
                        <td><?php echo htmlspecialchars($com['contenido_titulo']); ?></td>
                        <td><?php echo htmlspecialchars($com['autor']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars(substr($com['comentario'], 0, 100))); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($com['fecha'])); ?></td>
                        <td>
                            <button class="btn btn-sm btn-danger" onclick="eliminarComentario(<?php echo $com['id']; ?>, 'articulos')">Eliminar</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach ($comentarios_videos as $com): ?>
                    <tr id="fila-<?php echo $com['id']; ?>">
                        <td><?php echo $com['id']; ?></td>
                        <td><?php echo htmlspecialchars($com['contenido_titulo']); ?></td>
                        <td><?php echo htmlspecialchars($com['autor']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars(substr($com['contenido'], 0, 100))); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($com['fecha'])); ?></td>
                        <td>
                            <button class="btn btn-sm btn-danger" onclick="eliminarComentario(<?php echo $com['id']; ?>, 'videos')">Eliminar</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function mostrarMensaje(msg, tipo) {
    const contenedor = document.getElementById('mensajeComentarios');
    contenedor.innerHTML = `<div class="alert alert-${tipo === 'success' ? 'success' : 'danger'}">${msg}</div>`;
    setTimeout(() => contenedor.innerHTML = '', 4000);
}

function cambiarTipo(tipo) {
    cargar('admin_comentarios&tipo=' + tipo);
}

function eliminarComentario(id, tipo) {
    if (!confirm('¿Eliminar este comentario permanentemente?')) return;

    const formData = new FormData();
    formData.append('accion', 'eliminar');
    formData.append('tipo', tipo);
    formData.append('id', id);

    fetch('database/procesar_comentarios.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        mostrarMensaje(data.msg, data.ok ? 'success' : 'error');
        if (data.ok) {
            const fila = document.getElementById('fila-' + id);
            if (fila) fila.remove();
        }
    })
    .catch(err => mostrarMensaje('Error de conexión', 'error'));
}
</script>