<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    echo "<div class='alert alert-danger'>Acceso no autorizado</div>";
    exit;
}
require_once __DIR__ . '/../database/Conexion_base.php';

$tipo = $_GET['tipo'] ?? 'articulos';
$mensaje = '';

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
    <div id="mensajeContenidos" class="mb-3"></div>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $tipo === 'articulos' ? 'active' : ''; ?>" href="javascript:void(0)" onclick="cambiarTipo('articulos')">Artículos</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tipo === 'videos' ? 'active' : ''; ?>" href="javascript:void(0)" onclick="cambiarTipo('videos')">Videos</a>
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
            <tbody id="tablaContenidos">
                <?php if ($tipo === 'articulos'): ?>
                    <?php foreach ($articulos as $art): ?>
                    <tr id="fila-<?php echo $art['id']; ?>">
                        <td><?php echo $art['id']; ?></td>
                        <td><?php echo htmlspecialchars($art['titulo']); ?></td>
                        <td><?php echo htmlspecialchars($art['autor']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($art['fecha_creacion'])); ?></td>
                        <td><?php echo ucfirst($art['estado']); ?></td>
                        <td>
                            <a href="index.php?section=articulos&post=<?php echo $art['id']; ?>" class="btn btn-sm btn-info" target="_blank">Ver</a>
                            <a href="javascript:void(0)" onclick="cargar('editar_contenido?id=<?php echo $art['id']; ?>')" class="btn btn-sm btn-warning">Editar</a>
                            <button class="btn btn-sm btn-danger" onclick="eliminarContenido(<?php echo $art['id']; ?>, 'articulos')">Eliminar</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach ($videos as $vid): ?>
                    <tr id="fila-<?php echo $vid['id']; ?>">
                        <td><?php echo $vid['id']; ?></td>
                        <td><?php echo htmlspecialchars($vid['titulo']); ?></td>
                        <td><?php echo htmlspecialchars($vid['autor']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($vid['fecha_publicacion'])); ?></td>
                        <td>-</td>
                        <td>
                            <a href="index.php?section=watch&video=<?php echo $vid['id']; ?>" class="btn btn-sm btn-info" target="_blank">Ver</a>
                            <a href="javascript:void(0)" onclick="cargar('editar_contenido_admin?id=<?php echo $art['id']; ?>')" class="btn btn-sm btn-warning">Editar</a>
                            <button class="btn btn-sm btn-danger" onclick="eliminarContenido(<?php echo $vid['id']; ?>, 'videos')">Eliminar</button>
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
    const contenedor = document.getElementById('mensajeContenidos');
    contenedor.innerHTML = `<div class="alert alert-${tipo === 'success' ? 'success' : 'danger'}">${msg}</div>`;
    setTimeout(() => contenedor.innerHTML = '', 4000);
}

function cambiarTipo(tipo) {
    cargar('admin_contenidos&tipo=' + tipo);
}

function eliminarContenido(id, tipo) {
    if (!confirm('¿Eliminar este contenido permanentemente?')) return;

    const formData = new FormData();
    formData.append('accion', 'eliminar');
    formData.append('tipo', tipo);
    formData.append('id', id);

    fetch('database/procesar_contenidos_admin.php', {
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