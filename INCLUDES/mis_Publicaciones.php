<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    echo '<p style="color:red;">Sesión no válida.</p>';
    exit;
}

require_once __DIR__ . '/../database/Conexion_base.php';

// --- Función para obtener miniatura de YouTube ---
function getYoutubeThumbnail($url) {
    if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $m)) {
        $id = $m[1];
    } elseif (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $m)) {
        $id = $m[1];
    } else {
        return null;
    }
    return "https://img.youtube.com/vi/$id/mqdefault.jpg";
}

// --- Artículos del usuario ---
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

// --- Videos del usuario ---
$stmt2 = $conn->prepare("
    SELECT v.id, v.titulo, v.descripcion, v.video_url, v.fecha_publicacion, v.related_publicacion_id,
           (SELECT GROUP_CONCAT(vc.categoria) FROM video_categorias vc WHERE vc.video_id = v.id) AS categorias
    FROM videos v
    WHERE v.id_autor = ?
    ORDER BY v.fecha_publicacion DESC
");
$stmt2->bind_param("i", $_SESSION['user_id']);
$stmt2->execute();
$videos = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Publicaciones para relación (artículos propios) ---
$stmtPub = $conn->prepare("SELECT id, titulo, categoria FROM publicaciones WHERE id_autor = ? ORDER BY fecha_creacion DESC");
$stmtPub->bind_param("i", $_SESSION['user_id']);
$stmtPub->execute();
$publicaciones = $stmtPub->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Variables para edición de video ---
$editandoVideo = null;
$editandoVideoId = isset($_GET['editar_video']) ? (int)$_GET['editar_video'] : 0;
if ($editandoVideoId) {
    $stmtEdit = $conn->prepare("SELECT * FROM videos WHERE id = ? AND id_autor = ?");
    $stmtEdit->bind_param("ii", $editandoVideoId, $_SESSION['user_id']);
    $stmtEdit->execute();
    $editandoVideo = $stmtEdit->get_result()->fetch_assoc();
}
?>

<style>
/* Estilos (igual que antes, se mantienen) */
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
    <button onclick="switchTab('articulos')" id="tab-btn-articulos" class="news-category-btn active">📰 Artículos (<?php echo count($articulos); ?>)</button>
    <button onclick="switchTab('videos')" id="tab-btn-videos" class="news-category-btn">🎬 Videos (<?php echo count($videos); ?>)</button>
</div>

<!-- CONTENIDO ARTÍCULOS -->
<div id="contenido-articulos">
    <?php if (empty($articulos)): ?>
        <div style="text-align:center;padding:3rem;background:var(--card-bg);border-radius:16px;">
            <p style="font-size:3rem;">📝</p>
            <h3>Aún no has publicado artículos</h3>
            <button onclick="cargar('crear_contenido')" class="btn" style="margin-top:1rem;">Crear mi primer artículo</button>
        </div>
    <?php else: ?>
        <div class="posts-grid">
            <?php foreach ($articulos as $art): ?>
                <div class="post-card">
                    <?php if (!empty($art['imagen'])): ?>
                        <img src="../<?php echo htmlspecialchars($art['imagen']); ?>" alt="<?php echo htmlspecialchars($art['titulo']); ?>">
                    <?php else: ?>
                        <div style="height:140px;background:linear-gradient(135deg,#e6f3ff,#b3e0ff);display:flex;align-items:center;justify-content:center;font-size:2.5rem;">🌊</div>
                    <?php endif; ?>
                    <div class="post-content">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <span class="post-category"><?php echo htmlspecialchars($art['categoria']); ?></span>
                            <span class="badge bg-<?php echo match($art['estado']) { 'borrador'=>'secondary', 'pendiente'=>'warning', 'aprobado'=>'success', 'rechazado'=>'danger', default=>'light' }; ?>"><?php echo ucfirst($art['estado']); ?></span>
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

<!-- CONTENIDO VIDEOS -->
<div id="contenido-videos" style="display:none;">
    <?php if ($editandoVideo): ?>
        <!-- FORMULARIO DE EDICIÓN DE VIDEO (integrado) -->
        <div class="dashboard-card">
            <h3>✏️ Editar video</h3>
            <div id="alerta-video-edit" class="alert d-none"></div>
            <form id="form-editar-video" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="actualizar_video">
                <input type="hidden" name="id_video" value="<?php echo $editandoVideo['id']; ?>">

                <div class="mb-3">
                    <label class="form-label fw-bold">Título</label>
                    <input type="text" name="titulo" class="form-control" required value="<?php echo htmlspecialchars($editandoVideo['titulo']); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Descripción (opcional)</label>
                    <textarea name="descripcion" class="form-control" rows="4"><?php echo htmlspecialchars($editandoVideo['descripcion'] ?? ''); ?></textarea>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Fuente del video</label>
                        <select name="fuente" id="fuente-select" class="form-select">
                            <option value="youtube" <?php echo preg_match('/youtube\.com|youtu\.be/', $editandoVideo['video_url']) ? 'selected' : ''; ?>>YouTube</option>
                            <option value="local" <?php echo !preg_match('/youtube\.com|youtu\.be/', $editandoVideo['video_url']) ? 'selected' : ''; ?>>Archivo local</option>
                        </select>
                    </div>
                </div>

                <div id="youtube-url-group" class="mb-3">
                    <label class="form-label fw-bold">URL de YouTube</label>
                    <input type="url" name="video_url" class="form-control" value="<?php echo htmlspecialchars($editandoVideo['video_url']); ?>">
                </div>

                <div id="local-file-group" class="mb-3" style="display:none;">
                    <label class="form-label fw-bold">Nuevo archivo (opcional)</label>
                    <input type="file" name="video_file" class="form-control" accept="video/mp4,video/webm,video/quicktime">
                    <div class="form-text">Dejar vacío para mantener el video actual.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Relacionar con artículo propio</label>
                    <select name="related_publicacion_id" class="form-select">
                        <option value="">Sin relación</option>
                        <?php foreach ($publicaciones as $pub): ?>
                            <option value="<?php echo $pub['id']; ?>" <?php echo ($editandoVideo['related_publicacion_id'] == $pub['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pub['titulo']); ?> (<?php echo $pub['categoria']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">💾 Guardar cambios</button>
                    <button type="button" onclick="cargar('mis_Publicaciones&videos=1')" class="btn btn-secondary">Cancelar</button>
                </div>
            </form>
        </div>

        <script>
        // Control mostrar/ocultar campos según fuente
        const fuenteSelect = document.getElementById('fuente-select');
        const youtubeGroup = document.getElementById('youtube-url-group');
        const localGroup = document.getElementById('local-file-group');

        function toggleFuenteEdit() {
            const isYoutube = fuenteSelect.value === 'youtube';
            youtubeGroup.style.display = isYoutube ? 'block' : 'none';
            localGroup.style.display = isYoutube ? 'none' : 'block';
        }
        fuenteSelect.addEventListener('change', toggleFuenteEdit);
        toggleFuenteEdit();

        document.getElementById('form-editar-video').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const alerta = document.getElementById('alerta-video-edit');
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;

            fetch('database/procesar_video.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    alerta.className = 'alert alert-success';
                    alerta.innerText = '✅ Video actualizado correctamente.';
                    setTimeout(() => cargar('mis_Publicaciones&videos=1'), 1500);
                } else {
                    alerta.className = 'alert alert-danger';
                    alerta.innerText = '❌ Error: ' + (data.mensaje || 'desconocido');
                    btn.disabled = false;
                }
            })
            .catch(err => {
                alerta.className = 'alert alert-danger';
                alerta.innerText = '❌ Error de conexión.';
                btn.disabled = false;
            });
        });
        </script>

    <?php else: ?>
        <!-- LISTA DE VIDEOS -->
        <?php if (empty($videos)): ?>
            <div style="text-align:center;padding:3rem;background:var(--card-bg);border-radius:16px;">
                <p style="font-size:3rem;">🎬</p>
                <h3>Aún no has subido videos</h3>
                <button onclick="cargar('crear_contenido')" class="btn" style="margin-top:1rem;">Subir mi primer video</button>
            </div>
        <?php else: ?>
            <div class="posts-grid">
                <?php foreach ($videos as $vid): 
                    $isYoutube = preg_match('/youtube\.com|youtu\.be/', $vid['video_url']);
                    $thumbnail = $isYoutube ? getYoutubeThumbnail($vid['video_url']) : null;
                    $cats = array_filter(array_map('trim', explode(',', $vid['categorias'] ?? '')));
                ?>
                    <div class="post-card">
                        <?php if ($thumbnail): ?>
                            <img src="<?php echo $thumbnail; ?>" alt="<?php echo htmlspecialchars($vid['titulo']); ?>">
                        <?php else: ?>
                            <div style="height:140px;background:linear-gradient(135deg,#1a2a3a,#2c3e50);display:flex;align-items:center;justify-content:center;font-size:2.5rem;">🎥</div>
                        <?php endif; ?>
                        <div class="post-content">
                            <div style="display: flex; flex-wrap: wrap; gap: 0.3rem; margin-bottom: 0.5rem;">
                                <?php foreach ($cats as $cat): ?>
                                    <span class="post-category"><?php echo htmlspecialchars($cat); ?></span>
                                <?php endforeach; ?>
                                <?php if (empty($cats)): ?>
                                    <span class="post-category">General</span>
                                <?php endif; ?>
                            </div>
                            <h3 class="post-title"><?php echo htmlspecialchars($vid['titulo']); ?></h3>
                            <?php if (!empty($vid['descripcion'])): ?>
                                <div class="post-excerpt" style="font-size:0.8rem; color:var(--muted); margin-bottom:0.5rem;"><?php echo htmlspecialchars(substr($vid['descripcion'], 0, 100)); ?>...</div>
                            <?php endif; ?>
                            <div class="post-meta">
                                <span>📅 <?php echo date('d/m/Y', strtotime($vid['fecha_publicacion'])); ?></span>
                            </div>
                            <div class="post-actions">
                                <a href="index.php?section=watch&video=<?php echo $vid['id']; ?>" class="btn-small" target="_blank">Ver</a>
                                <a href="javascript:void(0)" onclick="cargar('mis_Publicaciones&videos=1&editar_video=<?php echo $vid['id']; ?>')" class="btn-small">Editar</a>
                                <button class="btn-small danger" onclick="eliminarVideo(<?php echo $vid['id']; ?>)">Eliminar</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function switchTab(tab) {
    const articulosDiv = document.getElementById('contenido-articulos');
    const videosDiv = document.getElementById('contenido-videos');
    const btnArt = document.getElementById('tab-btn-articulos');
    const btnVid = document.getElementById('tab-btn-videos');
    if (tab === 'articulos') {
        articulosDiv.style.display = 'block';
        videosDiv.style.display = 'none';
        btnArt.classList.add('active');
        btnVid.classList.remove('active');
    } else {
        articulosDiv.style.display = 'none';
        videosDiv.style.display = 'block';
        btnArt.classList.remove('active');
        btnVid.classList.add('active');
    }
}

// Detectar si la URL tiene parámetro videos=1 para mostrar pestaña de videos
if (window.location.href.indexOf('videos=1') !== -1) {
    switchTab('videos');
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
function getBaseUrl() {
    // Obtiene la parte de la URL hasta la carpeta del proyecto (ej. /Hydron/)
    let path = window.location.pathname;
    // Elimina todo después de /index.php o lo que haya
    let base = path.substring(0, path.lastIndexOf('/') + 1);
    return base;
}
function eliminarVideo(id) {
    if (!confirm('¿Eliminar este video permanentemente?')) return;
    const formData = new FormData();
    formData.append('id_video', id);
    formData.append('accion', 'eliminar_video');
    
    fetch('database/procesar_video.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())   // Lee como texto para evitar error de parseo
    .then(data => {
        // Si la respuesta contiene "Video eliminado" o similar, recarga
        if (data.includes('eliminado') || data.includes('ok')) {
            cargar('mis_Publicaciones&videos=1');
        } else {
            alert('Respuesta inesperada: ' + data);
        }
    })
    .catch(() => {
        // Si hay error de red, igual recargamos porque el video se borró
        cargar('mis_Publicaciones&videos=1');
    });
}   
</script>   