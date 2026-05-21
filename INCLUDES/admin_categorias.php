<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    echo "<div class='alert alert-danger'>Acceso no autorizado</div>";
    exit;
}
require_once __DIR__ . '/../database/Conexion_base.php';

// Obtener todas las categorías
$result = $conn->query("SELECT * FROM categorias ORDER BY nombre");
$categorias = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="admin-categorias">
    <h3 class="fw-bold mb-4">📁 Gestión de categorías</h3>
    <div id="mensajeCategorias" class="mb-3"></div>

    <!-- Formulario para nueva categoría -->
    <div class="card mb-4">
        <div class="card-header">Nueva categoría</div>
        <div class="card-body">
            <form id="formCrearCategoria">
                <input type="hidden" name="accion" value="crear">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Slug (URL amigable)</label>
                        <input type="text" name="slug" class="form-control" placeholder="dejar vacío para auto-generar">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-12 mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="activo" class="form-check-input" checked>
                            <label class="form-check-label">Activa</label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Guardar categoría</button>
            </form>
        </div>
    </div>

    <!-- Listado de categorías -->
    <div class="card">
        <div class="card-header">Categorías existentes</div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Slug</th>
                        <th>Descripción</th>
                        <th>Activa</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaCategorias">
                    <?php foreach ($categorias as $cat): ?>
                    <tr id="fila-<?php echo $cat['id']; ?>">
                        <td><?php echo $cat['id']; ?></td>
                        <td><?php echo htmlspecialchars($cat['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($cat['slug']); ?></td>
                        <td><?php echo htmlspecialchars($cat['descripcion']); ?></td>
                        <td><?php echo $cat['activo'] ? '✅ Sí' : '❌ No'; ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="editarCategoria(<?php echo htmlspecialchars(json_encode($cat)); ?>)">Editar</button>
                            <button class="btn btn-sm btn-danger" onclick="eliminarCategoria(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars($cat['nombre']); ?>')">Eliminar</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para editar categoría (manual, sin Bootstrap) -->
<div id="modalEditarCategoria" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:1000;">
    <div style="background: var(--card-bg); border-radius:16px; padding:2rem; width:90%; max-width:500px;">
        <h4 class="mb-3">Editar categoría</h4>
        <form id="formEditarCategoria">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" id="edit-id">
            <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" id="edit-nombre" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Slug</label>
                <input type="text" name="slug" id="edit-slug" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" id="edit-descripcion" class="form-control" rows="2"></textarea>
            </div>
            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" name="activo" id="edit-activo" class="form-check-input">
                    <label class="form-check-label">Activa</label>
                </div>
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalEditar()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
function mostrarMensaje(msg, tipo) {
    const contenedor = document.getElementById('mensajeCategorias');
    contenedor.innerHTML = `<div class="alert alert-${tipo === 'success' ? 'success' : 'danger'}">${msg}</div>`;
    setTimeout(() => contenedor.innerHTML = '', 4000);
}

function cerrarModalEditar() {
    document.getElementById('modalEditarCategoria').style.display = 'none';
}

function editarCategoria(cat) {
    document.getElementById('edit-id').value = cat.id;
    document.getElementById('edit-nombre').value = cat.nombre;
    document.getElementById('edit-slug').value = cat.slug || '';
    document.getElementById('edit-descripcion').value = cat.descripcion || '';
    document.getElementById('edit-activo').checked = cat.activo == 1;
    document.getElementById('modalEditarCategoria').style.display = 'flex';
}

function eliminarCategoria(id, nombre) {
    if (!confirm(`¿Eliminar la categoría "${nombre}"? Los artículos quedarán sin categoría.`)) return;
    
    const formData = new FormData();
    formData.append('accion', 'eliminar');
    formData.append('id', id);

    fetch('database/procesar_categorias.php', {
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

// Nueva categoría
document.getElementById('formCrearCategoria').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('database/procesar_categorias.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        mostrarMensaje(data.msg, data.ok ? 'success' : 'error');
        if (data.ok) {
            cargar('admin_categorias'); // recarga la tabla
        }
    })
    .catch(err => mostrarMensaje('Error de conexión', 'error'));
});

// Editar categoría
document.getElementById('formEditarCategoria').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('database/procesar_categorias.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        mostrarMensaje(data.msg, data.ok ? 'success' : 'error');
        if (data.ok) {
            cerrarModalEditar();
            cargar('admin_categorias');
        }
    })
    .catch(err => mostrarMensaje('Error de conexión', 'error'));
});

// Cerrar modal al hacer clic fuera del contenido
document.getElementById('modalEditarCategoria').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalEditar();
});
</script>