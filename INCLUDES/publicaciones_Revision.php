<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['editor', 'administrador'])) {
    echo "<div class='alert alert-danger'>Acceso no autorizado</div>";
    exit;
}
require_once __DIR__ . '/../database/Conexion_base.php';

// Obtener contenidos pendientes (solo artículos)
$query = "
    SELECT p.id, p.titulo, p.contenido, p.categoria, p.fecha_creacion, u.user AS autor, p.estado, p.observacion
    FROM publicaciones p
    JOIN usuarios u ON p.id_autor = u.id
    WHERE p.estado = 'pendiente'
    ORDER BY p.fecha_creacion ASC
";
$result = $conn->query($query);
$pendientes = $result->fetch_all(MYSQLI_ASSOC);
?>

<style>
.revision-table { width: 100%; border-collapse: collapse; }
.revision-table th, .revision-table td { padding: 12px; border-bottom: 1px solid var(--border); text-align: left; }
.revision-table th { background: var(--ocean); color: #fff; }
.btn-group { display: flex; gap: 6px; flex-wrap: wrap; }
.btn-sm { padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; border: none; cursor: pointer; }
.btn-success { background: #28a745; color: #fff; }
.btn-danger { background: #dc3545; color: #fff; }
.btn-warning { background: #ffc107; color: #000; }
.modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); justify-content: center; align-items: center; z-index: 1000; }
.modal-content { background: var(--card-bg); padding: 2rem; border-radius: 16px; width: 90%; max-width: 500px; }
</style>

<h3 class="fw-bold mb-4">📝 Contenidos pendientes de revisión</h3>
<div id="mensajesRevision"></div>

<?php if (empty($pendientes)): ?>
    <div class="alert alert-info">No hay contenidos pendientes de revisión.</div>
<?php else: ?>
    <table class="revision-table">
        <thead>
            <tr><th>ID</th><th>Título</th><th>Autor</th><th>Categoría</th><th>Fecha</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            <?php foreach ($pendientes as $item): ?>
            <tr id="fila-<?php echo $item['id']; ?>">
                <td><?php echo $item['id']; ?></td>
                <td><?php echo htmlspecialchars($item['titulo']); ?></td>
                <td><?php echo htmlspecialchars($item['autor']); ?></td>
                <td><?php echo htmlspecialchars($item['categoria']); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($item['fecha_creacion'])); ?></td>
                <td class="btn-group">
                    <button class="btn-sm btn-success" onclick="revisarAccion(<?php echo $item['id']; ?>, 'aprobar')">Aprobar</button>
                    <button class="btn-sm btn-danger" onclick="abrirModalRechazo(<?php echo $item['id']; ?>)">Rechazar</button>
                    <a href="index.php?section=articulos&post=<?php echo $item['id']; ?>" target="_blank" class="btn-sm btn-warning" style="text-decoration:none; display:inline-block;">Ver</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Modal para rechazar -->
    <div id="modalRechazo" class="modal">
        <div class="modal-content">
            <h4>Rechazar artículo</h4>
            <textarea id="observacion" placeholder="Motivo del rechazo (visible para el autor)" rows="3" style="width:100%; margin: 1rem 0;"></textarea>
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button class="btn-sm" onclick="cerrarModal()">Cancelar</button>
                <button class="btn-sm btn-danger" id="btnConfirmarRechazo">Rechazar</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
function mostrarMensaje(msg, tipo) {
    const contenedor = document.getElementById('mensajesRevision');
    contenedor.innerHTML = `<div class="alert alert-${tipo === 'success' ? 'success' : 'error'}">${msg}</div>`;
    setTimeout(() => contenedor.innerHTML = '', 3000);
}

let articuloIdRechazo = null;

function abrirModalRechazo(id) {
    articuloIdRechazo = id;
    document.getElementById('modalRechazo').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalRechazo').style.display = 'none';
    articuloIdRechazo = null;
}

function revisarAccion(id, accion) {
    if (accion === 'aprobar' && !confirm('¿Aprobar este artículo?')) return;
    if (accion === 'rechazar') {
        const observacion = document.getElementById('observacion').value.trim();
        if (!observacion) {
            mostrarMensaje('Debes escribir un motivo de rechazo.', 'error');
            return;
        }
        var formData = new FormData();
        formData.append('id_publicacion', id);
        formData.append('accion', 'rechazar');
        formData.append('observacion', observacion);
        enviarPeticion(formData);
        cerrarModal();
        return;
    }
    // Aprobar
    var formData = new FormData();
    formData.append('id_publicacion', id);
    formData.append('accion', 'aprobar');
    enviarPeticion(formData);
}

function enviarPeticion(formData) {
    fetch('database/procesar_revision.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        mostrarMensaje(data.msg, data.ok ? 'success' : 'error');
        if (data.ok) {
            cargar('publicaciones_Revision'); // recarga la lista
        }
    })
    .catch(err => console.error(err));
}

// Asignar evento al botón confirmar rechazo
document.getElementById('btnConfirmarRechazo')?.addEventListener('click', function() {
    if (articuloIdRechazo) revisarAccion(articuloIdRechazo, 'rechazar');
});
</script>