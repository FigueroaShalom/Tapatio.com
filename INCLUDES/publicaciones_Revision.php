<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// Solo editores y administradores pueden acceder
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'editor' && $_SESSION['rol'] !== 'administrador')) {
    echo "<div class='alert alert-danger'>Acceso no autorizado</div>";
    exit;
}
require_once __DIR__ . '/../database/Conexion_base.php';

$mensaje = '';
$error = '';

// Procesar acciones (aprobar/rechazar/editar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_revision'])) {
    $id_publicacion = (int)$_POST['id_publicacion'];
    $accion = $_POST['accion_revision'];
    $observacion = trim($_POST['observacion'] ?? '');

    if ($accion === 'aprobar') {
        $stmt = $conn->prepare("UPDATE publicaciones SET estado = 'aprobado' WHERE id = ?");
        $stmt->bind_param("i", $id_publicacion);
        if ($stmt->execute()) {
            $mensaje = "✅ Artículo aprobado y publicado.";
        } else {
            $error = "❌ Error al aprobar: " . $conn->error;
        }
        $stmt->close();
    } 
    elseif ($accion === 'rechazar') {
        // Guardar observación (usaremos un campo `observacion_revision` si existe, o creamos uno)
        // Por simplicidad, puedes agregar una columna `observacion` en publicaciones.
        // Si no existe, ejecuta: ALTER TABLE publicaciones ADD COLUMN observacion TEXT DEFAULT NULL;
        $stmt = $conn->prepare("UPDATE publicaciones SET estado = 'rechazado', observacion = ? WHERE id = ?");
        $stmt->bind_param("si", $observacion, $id_publicacion);
        if ($stmt->execute()) {
            $mensaje = "❌ Artículo rechazado. Se ha enviado una observación al autor.";
        } else {
            $error = "❌ Error al rechazar: " . $conn->error;
        }
        $stmt->close();
    }
    elseif ($accion === 'editar') {
        // Redirigir a la página de edición
        header("Location: index.php?section=dashboard&modulo=editar_contenido&id=$id_publicacion");
        exit;
    }
}

// Obtener contenidos pendientes (solo artículos por ahora, luego también videos)
$query = "
    SELECT p.id, p.titulo, p.contenido, p.categoria, p.fecha_creacion, u.user AS autor, 
           p.estado, p.observacion
    FROM publicaciones p
    JOIN usuarios u ON p.id_autor = u.id
    WHERE p.estado = 'pendiente'
    ORDER BY p.fecha_creacion ASC
";
$result = $conn->query($query);
$pendientes = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="revision-container">
    <h3 class="fw-bold mb-4">📝 Contenidos pendientes de revisión</h3>
    
    <?php if ($mensaje): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (empty($pendientes)): ?>
        <div class="alert alert-info">No hay contenidos pendientes de revisión.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Autor</th>
                        <th>Categoría</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendientes as $item): ?>
                    <tr>
                        <td><?php echo $item['id']; ?></td>
                        <td><?php echo htmlspecialchars($item['titulo']); ?></td>
                        <td><?php echo htmlspecialchars($item['autor']); ?></td>
                        <td><?php echo htmlspecialchars($item['categoria']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($item['fecha_creacion'])); ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="id_publicacion" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="accion_revision" value="aprobar">
                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('¿Aprobar este contenido?')">✅ Aprobar</button>
                                </form>
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rechazarModal" data-id="<?php echo $item['id']; ?>" data-titulo="<?php echo htmlspecialchars($item['titulo']); ?>">❌ Rechazar</button>
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="id_publicacion" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="accion_revision" value="editar">
                                    <button type="submit" class="btn btn-sm btn-warning">✏️ Editar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para rechazar con observación -->
<div class="modal fade" id="rechazarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rechazar contenido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="id_publicacion" id="rechazar_id" value="">
                <input type="hidden" name="accion_revision" value="rechazar">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Motivo del rechazo (visible para el autor)</label>
                        <textarea name="observacion" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Rechazar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Pasar el id al modal
    var rechazarModal = document.getElementById('rechazarModal');
    if (rechazarModal) {
        rechazarModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var inputId = rechazarModal.querySelector('#rechazar_id');
            inputId.value = id;
        });
    }
</script>