<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../database/Conexion_base.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    die("No autorizado.");
}

$id_post = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id_post) {
    echo "<div class='alert alert-danger'>No se proporcionó un ID válido.</div>";
    exit;
}

// Obtener el artículo (sin restricción de autor porque es admin)
$stmt = $conn->prepare("SELECT * FROM publicaciones WHERE id = ?");
$stmt->bind_param("i", $id_post);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) {
    echo "<div class='alert alert-danger'>El artículo no existe.</div>";
    exit;
}

$cats = ['peces', 'mamiferos', 'moluscos', 'crustaceos', 'conservacion'];
?>

<div class="dashboard-card">
    <h2>✏️ Editar artículo (Administrador)</h2>
    <div id="alerta-editar-admin" class="alert d-none"></div>

    <form id="form-editar-admin">
        <input type="hidden" name="id_publicacion" value="<?php echo $post['id']; ?>">
        <input type="hidden" name="accion" value="editar_articulo">

        <div class="form-group">
            <label>Título</label>
            <input type="text" name="titulo" class="form-control" required value="<?php echo htmlspecialchars($post['titulo']); ?>">
        </div>

        <div class="form-group">
            <label>Categoría</label>
            <select name="categoria" class="form-control">
                <?php foreach ($cats as $cat): ?>
                    <option value="<?php echo $cat; ?>" <?php echo $post['categoria'] === $cat ? 'selected' : ''; ?>><?php echo ucfirst($cat); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>URL de imagen (opcional)</label>
            <input type="text" name="imagen" class="form-control" value="<?php echo htmlspecialchars($post['imagen'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Contenido</label>
            <textarea name="contenido" rows="10" class="form-control" required><?php echo htmlspecialchars($post['contenido']); ?></textarea>
        </div>

        <div style="display:flex; gap:10px; margin-top:1rem;">
            <button type="submit" class="btn">✅ Guardar Cambios</button>
            <button type="button" onclick="cargar('admin_contenidos')" class="btn" style="background:#95a5a6;">Cancelar</button>
        </div>
    </form>
</div>

<script>
document.getElementById('form-editar-admin').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const alerta = document.getElementById('alerta-editar-admin');
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;

    fetch('../database/procesar_crear_contenido.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(data => {
        if (data.includes('Cambios guardados')) {
            alerta.className = 'alert alert-success';
            alerta.innerText = '✅ ¡Cambios guardados correctamente!';
            setTimeout(() => cargar('admin_contenidos'), 1500);
        } else {
            alerta.className = 'alert alert-danger';
            alerta.innerText = '❌ Error al guardar: ' + data;
            btn.disabled = false;
        }
    })
    .catch(err => {
        alerta.className = 'alert alert-danger';
        alerta.innerText = '❌ Error de conexión';
        btn.disabled = false;
    });
});
</script>   