<?php
// INCLUDES/admin_categorias.php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    echo "<div class='alert alert-danger'>Acceso no autorizado</div>";
    exit;
}
require_once __DIR__ . '/../database/Conexion_base.php';

$mensaje = '';
$error = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre']);
        $slug = trim($_POST['slug']) ?: strtolower(str_replace(' ', '-', $nombre));
        $descripcion = trim($_POST['descripcion']);
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        if (empty($nombre)) {
            $error = "El nombre es obligatorio.";
        } else {
            $stmt = $conn->prepare("INSERT INTO categorias (nombre, slug, descripcion, activo) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $nombre, $slug, $descripcion, $activo);
            if ($stmt->execute()) {
                $mensaje = "Categoría creada correctamente.";
            } else {
                $error = "Error al crear: " . $conn->error;
            }
            $stmt->close();
        }
    }
    
    elseif ($accion === 'editar') {
        $id = (int)$_POST['id'];
        $nombre = trim($_POST['nombre']);
        $slug = trim($_POST['slug']);
        $descripcion = trim($_POST['descripcion']);
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        if (empty($nombre)) {
            $error = "El nombre es obligatorio.";
        } else {
            $stmt = $conn->prepare("UPDATE categorias SET nombre=?, slug=?, descripcion=?, activo=? WHERE id=?");
            $stmt->bind_param("sssii", $nombre, $slug, $descripcion, $activo, $id);
            if ($stmt->execute()) {
                $mensaje = "Categoría actualizada.";
            } else {
                $error = "Error al actualizar: " . $conn->error;
            }
            $stmt->close();
        }
    }
    
    elseif ($accion === 'eliminar') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM categorias WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $mensaje = "Categoría eliminada.";
        } else {
            $error = "Error al eliminar: " . $conn->error;
        }
        $stmt->close();
    }
}

// Obtener todas las categorías
$result = $conn->query("SELECT * FROM categorias ORDER BY nombre");
$categorias = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="admin-categorias">
    <h3 class="fw-bold mb-4">📁 Gestión de categorías</h3>
    
    <?php if ($mensaje): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <!-- Formulario para nueva categoría -->
    <div class="card mb-4">
        <div class="card-header">Nueva categoría</div>
        <div class="card-body">
            <form method="POST">
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
                <tbody>
                    <?php foreach ($categorias as $cat): ?>
                    <tr>
                        <td><?php echo $cat['id']; ?></td>
                        <td><?php echo htmlspecialchars($cat['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($cat['slug']); ?></td>
                        <td><?php echo htmlspecialchars($cat['descripcion']); ?></td>
                        <td><?php echo $cat['activo'] ? '✅ Sí' : '❌ No'; ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="editarCategoria(<?php echo htmlspecialchars(json_encode($cat)); ?>)">Editar</button>
                            <button class="btn btn-sm btn-danger" onclick="eliminarCategoria(<?php echo $cat['id']; ?>)">Eliminar</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para editar categoría -->
<div class="modal fade" id="modalEditarCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" id="edit-id">
                <div class="modal-body">
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarCategoria(cat) {
    document.getElementById('edit-id').value = cat.id;
    document.getElementById('edit-nombre').value = cat.nombre;
    document.getElementById('edit-slug').value = cat.slug;
    document.getElementById('edit-descripcion').value = cat.descripcion || '';
    document.getElementById('edit-activo').checked = cat.activo == 1;
    new bootstrap.Modal(document.getElementById('modalEditarCategoria')).show();
}

function eliminarCategoria(id) {
    if (confirm('¿Eliminar esta categoría? Esto no eliminará los artículos, pero quedarán sin categoría.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="${id}">`;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>