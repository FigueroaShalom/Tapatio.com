<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    echo "<div class='alert alert-danger'>Acceso no autorizado</div>";
    exit;
}
require_once __DIR__ . '/../database/Conexion_base.php';

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $clave => $valor) {
        if ($clave === 'accion') continue;
        $valor = trim($valor);
        $stmt = $conn->prepare("UPDATE configuracion SET valor = ? WHERE clave = ?");
        $stmt->bind_param("ss", $valor, $clave);
        $stmt->execute();
        $stmt->close();
    }
    $mensaje = "Configuración actualizada.";
}

// Obtener todas las configuraciones
$result = $conn->query("SELECT * FROM configuracion");
$config = [];
while ($row = $result->fetch_assoc()) {
    $config[$row['clave']] = $row;
}
?>

<div class="admin-config">
    <h3 class="fw-bold mb-4">⚙️ Configuración general</h3>
    
    <?php if ($mensaje): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>
    
    <form method="POST">
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