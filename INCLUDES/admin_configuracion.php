<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    echo "<div class='alert alert-danger'>Acceso no autorizado</div>";
    exit;
}
require_once __DIR__ . '/../database/Conexion_base.php';

// Obtener todas las configuraciones directamente de la BD
$result = $conn->query("SELECT * FROM configuracion");
$config = [];
while ($row = $result->fetch_assoc()) {
    $config[$row['clave']] = $row;
}
?>

<div class="admin-config">
    <h3 class="fw-bold mb-4">⚙️ Configuración general</h3>
    <div id="mensajeConfig" class="mb-3"></div>

    <form id="formConfiguracion">
        <div class="card">
            <div class="card-body">
                <?php foreach ($config as $clave => $item): ?>
                <div class="mb-3">
                    <label class="form-label fw-bold"><?php echo ucfirst(str_replace('_', ' ', $clave)); ?></label>
                    <?php if ($item['tipo'] === 'textarea'): ?>
                        <textarea name="<?php echo $clave; ?>" class="form-control" rows="3"><?php echo htmlspecialchars($item['valor']); ?></textarea>
                    <?php elseif ($item['tipo'] === 'number'): ?>
                        <input type="number" name="<?php echo $clave; ?>" class="form-control" value="<?php echo htmlspecialchars($item['valor']); ?>">
                    <?php else: ?>
                        <input type="text" name="<?php echo $clave; ?>" class="form-control" value="<?php echo htmlspecialchars($item['valor']); ?>">
                    <?php endif; ?>
                    <small class="text-muted">Configuración del sistema</small>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('formConfiguracion').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const mensajeDiv = document.getElementById('mensajeConfig');
    
    fetch('database/procesar_configuracion.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        mensajeDiv.innerHTML = `<div class="alert alert-${data.ok ? 'success' : 'danger'}">${data.msg}</div>`;
        if (data.ok) {
            // Recargar el módulo para mostrar los nuevos valores
            setTimeout(() => cargar('admin_configuracion'), 1500);
        }
    })
    .catch(err => {
        mensajeDiv.innerHTML = '<div class="alert alert-danger">Error de conexión</div>';
    });
});
</script>