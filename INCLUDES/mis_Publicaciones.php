<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    echo '<p style="color:red;">Sesión no válida.</p>';
    exit;
}

require_once __DIR__ . '/../database/Conexion_base.php';

// Artículos del usuario
$stmt = $conn->prepare("
    SELECT p.id, p.titulo, p.categoria, p.imagen, p.fecha_creacion, p.estado, p.observacion,
           (SELECT COUNT(*) FROM likes WHERE id_publicacion = p.id) AS likes,
           (SELECT COUNT(*) FROM comentarios WHERE id_publicacion = p.id) AS comentarios
    FROM publicaciones p
    WHERE p.id_autor = ?
    ORDER BY p.fecha_creacion DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$articulos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Videos del usuario
$stmt2 = $conn->prepare("
    SELECT v.id, v.titulo, 
           (SELECT GROUP_CONCAT(vc.categoria) FROM video_categorias vc WHERE vc.video_id = v.id) AS categoria,
           v.video_url, v.fecha_publicacion, v.related_publicacion_id
    FROM videos v
    WHERE v.id_autor = ?
    ORDER BY v.fecha_publicacion DESC
");
$stmt2->bind_param("i", $_SESSION['user_id']);
$stmt2->execute();
$videos = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<style>
/* Ajustes específicos para Mis Publicaciones */
.posts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
    margin: 1rem 0;
}
.post-card {
    background: var(--card-bg);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 0.2s;
    display: flex;
    flex-direction: column;
    height: 100%;
}
.post-card:hover { transform: translateY(-4px); }
.post-card img { width: 100%; height: 160px; object-fit: cover; }
.post-content { padding: 1rem; flex: 1; display: flex; flex-direction: column; }
.post-category {
    display: inline-block;
    background: rgba(0,119,190,0.1);
    color: var(--ocean);
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
}
.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
}
.bg-secondary { background: #6c757d; color: white; }
.bg-warning { background: #ffc107; color: #212529; }
.bg-success { background: #28a745; color: white; }
.bg-danger { background: #dc3545; color: white; }
.post-title {
    font-size: 1.1rem;
    font-weight: 800;
    margin: 0.5rem 0;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.post-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.75rem;
    color: var(--muted);
    margin: 0.5rem 0;
}
.post-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-top: auto;
    padding-top: 0.5rem;
}
.btn-small {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    border: none;
    cursor: pointer;
    background: var(--ocean);
    color: white;
    text-decoration: none;
    display: inline-block;
}
.btn-small.danger { background: #dc3545; }
.btn-small.danger:hover { background: #b02a37; }
.text-danger { color: #dc3545; font-size: 0.7rem; }
</style>

<h1 class="section-title">Mis Publicaciones</h1>

<!-- TABS -->
<div style="display:flex; gap:1rem; margin-bottom:2rem;">
    <button onclick="switchTabPub('articulos')" id="pub-btn-articulos" class="news-category-btn active">
        📰 Artículos (<?php echo count($articulos); ?>)
    </button>
    <button onclick="switchTabPub('videos')" id="pub-btn-videos" class="news-category-btn">
        🎬 Videos (<?php echo count($videos); ?>)
    </button>
</div>

<!-- ARTÍCULOS -->
<div id="pub-articulos">
<?php if (empty($articulos)): ?>
    <div style="text-align:center;padding:3rem;background:var(--card-bg);border-radius:16px;">
        <p style="font-size:3rem;">📝</p>
        <h3>Aún no has publicado artículos</h3>
        <button onclick="cargar('crear_contenido')" class="btn" style="margin-top:1rem;">Crear mi primer artículo</button>
    </div>
<?php else: ?>
    <div class="posts-grid">
        <?php foreach ($articulos as $art): ?>
            <div class="post-card" data-id="<?php echo $art['id']; ?>">
                <?php if (!empty($art['imagen'])): ?>
                    <img src="../<?php echo htmlspecialchars($art['imagen']); ?>" alt="<?php echo htmlspecialchars($art['titulo']); ?>">
                <?php else: ?>
                    <div style="height:140px;background:linear-gradient(135deg,#e6f3ff,#b3e0ff);display:flex;align-items:center;justify-content:center;font-size:2.5rem;">🌊</div>
                <?php endif; ?>
                <div class="post-content">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <span class="post-category"><?php echo htmlspecialchars($art['categoria']); ?></span>
                        <span class="badge <?php 
                            echo match($art['estado']) {
                                'borrador' => 'bg-secondary',
                                'pendiente' => 'bg-warning',
                                'aprobado' => 'bg-success',
                                'rechazado' => 'bg-danger',
                                default => 'bg-light'
                            };
                        ?>"><?php echo ucfirst($art['estado']); ?></span>
                    </div>
                    <?php if ($art['estado'] === 'rechazado' && !empty($art['observacion'])): ?>
                        <div class="text-danger"><strong>Motivo:</strong> <?php echo htmlspecialchars($art['observacion']); ?></div>
                    <?php endif; ?>
                    <h3 class="post-title"><?php echo htmlspecialchars($art['titulo']); ?></h3>
                    <div class="post-meta">
                        <span>❤️ <?php echo $art['likes']; ?></span>
                        <span>💬 <?php echo $art['comentarios']; ?></span>
                        <span>📅 <?php echo date('d/m/Y', strtotime($art['fecha_creacion'])); ?></span>
                    </div>
                    <div class="post-actions">
                        <a href="index.php?section=articulos&post=<?php echo $art['id']; ?>" class="btn-small" target="_blank">Ver</a>
                        <a href="javascript:void(0)" onclick="cargar('editar_contenido?id=<?php echo $art['id']; ?>')" class="btn-small">Editar</a>
                        <?php if ($art['estado'] === 'borrador' || $art['estado'] === 'rechazado'): ?>
                            <button class="btn-small" onclick="enviarRevision(<?php echo $art['id']; ?>)">📨 Enviar a revisión</button>
                        <?php endif; ?>
                        <button class="btn-small danger" onclick="eliminarArticulo(<?php echo $art['id']; ?>)">Eliminar</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
</div>

<!-- VIDEOS (simplificado, ajusta según necesites) -->
<div id="pub-videos" style="display:none;">
    <div style="text-align:center;padding:3rem;background:var(--card-bg);border-radius:16px;">
        <p style="font-size:3rem;">🎬</p>
        <p>Módulo de videos en desarrollo</p>
    </div>
</div>

<script>
function switchTabPub(tab) {
    document.getElementById('pub-articulos').style.display = tab === 'articulos' ? 'block' : 'none';
    document.getElementById('pub-videos').style.display = tab === 'videos' ? 'block' : 'none';
    document.getElementById('pub-btn-articulos').classList.toggle('active', tab === 'articulos');
    document.getElementById('pub-btn-videos').classList.toggle('active', tab === 'videos');
}

function eliminarArticulo(id) {
    if (!confirm('¿Eliminar este artículo permanentemente?')) return;
    const formData = new FormData();
    formData.append('id_publicacion', id);
    formData.append('accion', 'eliminar_articulo');
    fetch('database/procesar_crear_contenido.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(data => {
        if (data.trim() === 'deleted') {
            cargar('mis_Publicaciones');
        } else {
            alert('Error: ' + data);
        }
    })
    .catch(() => alert('Error de conexión'));
}

function enviarRevision(id) {
    if (!confirm('¿Enviar este artículo a revisión? Un editor lo evaluará.')) return;
    fetch('database/cambiar_estado.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id + '&estado=pendiente'
    })
    .then(res => res.text())
    .then(data => {
        if (data.trim() === 'ok') {
            cargar('mis_Publicaciones');
        } else {
            alert('Error: ' + data);
        }
    })
    .catch(() => alert('Error de conexión'));
}
</script>