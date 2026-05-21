<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['id'])) {
    echo '<p style="color:red;">Sesión no válida.</p>';
    exit;
}

require_once __DIR__ . '/../database/Conexion_base.php';

// Asegurar columna estado
$check_col = $conn->query("SHOW COLUMNS FROM publicaciones LIKE 'estado'");
if ($check_col && $check_col->num_rows == 0) {
    $conn->query("ALTER TABLE publicaciones ADD COLUMN estado ENUM('publicado','borrador') NOT NULL DEFAULT 'publicado'");
}

// Cargar lista de borradores
$stmt = $conn->prepare("
    SELECT id, titulo, contenido, categoria, imagen, fecha_creacion
    FROM publicaciones
    WHERE id_autor = ? AND estado = 'borrador'
    ORDER BY fecha_creacion DESC
");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$borradores = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Borrador a editar (si viene por GET)
$editando = null;
if (isset($_GET['editar_borrador'])) {
    $eid = (int)$_GET['editar_borrador'];
    $est = $conn->prepare("SELECT * FROM publicaciones WHERE id=? AND id_autor=? AND estado='borrador'");
    $est->bind_param("ii", $eid, $_SESSION['id']);
    $est->execute();
    $editando = $est->get_result()->fetch_assoc();
}

$cats = ['peces', 'mamiferos', 'moluscos', 'crustaceos', 'conservacion'];
?>

<h1 class="section-title">Mis Borradores</h1>
<div id="mensajesBorradores"></div>

<div class="dashboard-grid">

    <?php if ($editando): ?>
    <div class="dashboard-card">
        <h2>✏️ Editar borrador</h2>
        <form id="formEditarBorrador">
            <input type="hidden" name="id_borrador" value="<?php echo $editando['id']; ?>">
            <div class="form-group">
                <label>Título</label>
                <input type="text" name="titulo" required value="<?php echo htmlspecialchars($editando['titulo']); ?>">
            </div>
            <div class="form-group">
                <label>Categoría</label>
                <select name="categoria">
                    <?php foreach ($cats as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo ($editando['categoria'] === $cat) ? 'selected' : ''; ?>><?php echo ucfirst($cat); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Contenido</label>
                <textarea name="contenido" rows="6"><?php echo htmlspecialchars($editando['contenido']); ?></textarea>
            </div>
            <div class="form-group">
                <label>URL de imagen (opcional)</label>
                <input type="url" name="imagen" value="<?php echo htmlspecialchars($editando['imagen'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn">💾 Guardar cambios</button>
            <button type="button" onclick="cargar('mis_borradores')" class="btn" style="background:#95a5a6;">Cancelar</button>
        </form>
    </div>
    <?php endif; ?>

    <div class="dashboard-card">
        <h2>📂 Borradores guardados (<?php echo count($borradores); ?>)</h2>
        <?php if (empty($borradores)): ?>
            <p>No tienes borradores guardados.</p>
        <?php else: ?>
            <?php foreach ($borradores as $b): ?>
                <div class="post-item">
                    <h3><?php echo htmlspecialchars($b['titulo']); ?></h3>
                    <div class="post-meta">
                        <span>🏷️ <?php echo htmlspecialchars($b['categoria']); ?></span>
                        <span>📅 <?php echo date('d/m/Y', strtotime($b['fecha_creacion'])); ?></span>
                        <span style="background:rgba(255,180,0,0.15); color:#f39c12; padding:2px 8px; border-radius:50px; font-size:.75rem;">
    Borrador
</span>
                    </div>
                    <div class="post-actions">
                        <button onclick="cargar('mis_borradores?editar_borrador=<?php echo $b['id']; ?>')" class="btn-small">Editar</button>
                        <button onclick="ejecutarAccion(<?php echo $b['id']; ?>, 'publicar')" class="btn-small" style="background:#27ae60;color:white;">Publicar</button>
                        <button onclick="ejecutarAccion(<?php echo $b['id']; ?>, 'eliminar')" class="btn-small danger">Eliminar</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function mostrarMensaje(msg, tipo) {
    const contenedor = document.getElementById('mensajesBorradores');
    contenedor.innerHTML = `<div class="alert alert-${tipo === 'success' ? 'success' : 'error'}">${msg}</div>`;
    setTimeout(() => contenedor.innerHTML = '', 3000);
}

function ejecutarAccion(id, accion) {
    if (accion === 'eliminar' && !confirm('¿Eliminar este borrador?')) return;
    if (accion === 'publicar' && !confirm('¿Publicar este borrador?')) return;

    const formData = new FormData();
    formData.append('id_borrador', id);
    formData.append('accion', accion);

    fetch('database/procesar_borrador.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        mostrarMensaje(data.msg, data.ok ? 'success' : 'error');
        if (data.ok) {
            cargar('mis_borradores');
        }
    })
    .catch(err => console.error(err));
}

document.getElementById('formEditarBorrador')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('accion', 'actualizar');
    fetch('database/procesar_borrador.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        mostrarMensaje(data.msg, data.ok ? 'success' : 'error');
        if (data.ok) {
            cargar('mis_borradores');
        }
    })
    .catch(err => console.error(err));
});
</script>