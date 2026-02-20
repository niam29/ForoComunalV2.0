<?php
require_once 'includes/config.php';

$termino = trim($_GET['q'] ?? '');
$tipo = $_GET['tipo'] ?? 'todo';
$foro_id = intval($_GET['foro_id'] ?? 0);
$autor = trim($_GET['autor'] ?? '');
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$orden = $_GET['orden'] ?? 'relevancia';

$resultados = [];
$total_resultados = 0;
$mensaje = '';

// Procesar búsqueda si hay término
if (!empty($termino) && $pdo) {
    try {
        // Construir consulta base
        $sql = "";
        $params = [];
        
        switch($tipo) {
            case 'hilos':
                $sql = "
                    SELECT 
                        'hilo' as tipo,
                        h.id,
                        h.titulo,
                        h.contenido,
                        h.fecha_creacion,
                        h.visitas,
                        u.usuario as autor_usuario,
                        u.nombre as autor_nombre,
                        f.nombre as foro_nombre,
                        f.id as foro_id,
                        MATCH(h.titulo, h.contenido) AGAINST(? IN BOOLEAN MODE) as relevancia
                    FROM hilos h
                    JOIN usuarios u ON h.usuario_id = u.id
                    JOIN foros f ON h.foro_id = f.id
                    WHERE f.activo = 1 AND MATCH(h.titulo, h.contenido) AGAINST(? IN BOOLEAN MODE)
                ";
                $params = [$termino, $termino];
                break;
                
            case 'respuestas':
                $sql = "
                    SELECT 
                        'respuesta' as tipo,
                        r.id,
                        NULL as titulo,
                        r.contenido,
                        r.fecha_respuesta as fecha_creacion,
                        NULL as visitas,
                        u.usuario as autor_usuario,
                        u.nombre as autor_nombre,
                        f.nombre as foro_nombre,
                        f.id as foro_id,
                        h.titulo as hilo_titulo,
                        h.id as hilo_id,
                        MATCH(r.contenido) AGAINST(? IN BOOLEAN MODE) as relevancia
                    FROM respuestas r
                    JOIN usuarios u ON r.usuario_id = u.id
                    JOIN hilos h ON r.hilo_id = h.id
                    JOIN foros f ON h.foro_id = f.id
                    WHERE f.activo = 1 AND MATCH(r.contenido) AGAINST(? IN BOOLEAN MODE)
                ";
                $params = [$termino, $termino];
                break;
                
            case 'usuarios':
                $sql = "
                    SELECT 
                        'usuario' as tipo,
                        u.id,
                        u.usuario as titulo,
                        CONCAT(u.nombre, ' ', u.apellido) as contenido,
                        u.fecha_registro as fecha_creacion,
                        NULL as visitas,
                        u.usuario as autor_usuario,
                        CONCAT(u.nombre, ' ', u.apellido) as autor_nombre,
                        NULL as foro_nombre,
                        NULL as foro_id,
                        MATCH(u.usuario, u.nombre, u.apellido, u.habilidades) AGAINST(? IN BOOLEAN MODE) as relevancia
                    FROM usuarios u
                    WHERE u.activo = 1 AND MATCH(u.usuario, u.nombre, u.apellido, u.habilidades) AGAINST(? IN BOOLEAN MODE)
                ";
                $params = [$termino, $termino];
                break;
                
            default: // 'todo'
                $sql = "
                    (SELECT 
                        'hilo' as tipo,
                        h.id,
                        h.titulo,
                        h.contenido,
                        h.fecha_creacion,
                        h.visitas,
                        u.usuario as autor_usuario,
                        u.nombre as autor_nombre,
                        f.nombre as foro_nombre,
                        f.id as foro_id,
                        MATCH(h.titulo, h.contenido) AGAINST(? IN BOOLEAN MODE) as relevancia
                    FROM hilos h
                    JOIN usuarios u ON h.usuario_id = u.id
                    JOIN foros f ON h.foro_id = f.id
                    WHERE f.activo = 1 AND MATCH(h.titulo, h.contenido) AGAINST(? IN BOOLEAN MODE))
                    
                    UNION ALL
                    
                    (SELECT 
                        'respuesta' as tipo,
                        r.id,
                        NULL as titulo,
                        r.contenido,
                        r.fecha_respuesta as fecha_creacion,
                        NULL as visitas,
                        u.usuario as autor_usuario,
                        u.nombre as autor_nombre,
                        f.nombre as foro_nombre,
                        f.id as foro_id,
                        h.titulo as hilo_titulo,
                        h.id as hilo_id,
                        MATCH(r.contenido) AGAINST(? IN BOOLEAN MODE) as relevancia
                    FROM respuestas r
                    JOIN usuarios u ON r.usuario_id = u.id
                    JOIN hilos h ON r.hilo_id = h.id
                    JOIN foros f ON h.foro_id = f.id
                    WHERE f.activo = 1 AND MATCH(r.contenido) AGAINST(? IN BOOLEAN MODE))
                    
                    UNION ALL
                    
                    (SELECT 
                        'usuario' as tipo,
                        u.id,
                        u.usuario as titulo,
                        CONCAT(u.nombre, ' ', u.apellido) as contenido,
                        u.fecha_registro as fecha_creacion,
                        NULL as visitas,
                        u.usuario as autor_usuario,
                        CONCAT(u.nombre, ' ', u.apellido) as autor_nombre,
                        NULL as foro_nombre,
                        NULL as foro_id,
                        MATCH(u.usuario, u.nombre, u.apellido, u.habilidades) AGAINST(? IN BOOLEAN MODE) as relevancia
                    FROM usuarios u
                    WHERE u.activo = 1 AND MATCH(u.usuario, u.nombre, u.apellido, u.habilidades) AGAINST(? IN BOOLEAN MODE))
                ";
                $params = [$termino, $termino, $termino, $termino, $termino, $termino];
                break;
        }
        
        // Aplicar filtros adicionales
        $where_conditions = [];
        
        if ($foro_id > 0) {
            $where_conditions[] = "foro_id = ?";
            $params[] = $foro_id;
        }
        
        if (!empty($autor)) {
            $where_conditions[] = "autor_usuario LIKE ?";
            $params[] = "%$autor%";
        }
        
        if (!empty($fecha_inicio)) {
            $where_conditions[] = "DATE(fecha_creacion) >= ?";
            $params[] = $fecha_inicio;
        }
        
        if (!empty($fecha_fin)) {
            $where_conditions[] = "DATE(fecha_creacion) <= ?";
            $params[] = $fecha_fin;
        }
        
        // Construir consulta final
        if ($tipo === 'todo') {
            // Para UNION, aplicar WHERE después
            $sql = "SELECT * FROM ($sql) as resultados";
            if (!empty($where_conditions)) {
                $sql .= " WHERE " . implode(" AND ", $where_conditions);
            }
        } else {
            // Para consultas simples, agregar WHERE
            if (!empty($where_conditions)) {
                $sql .= " AND " . implode(" AND ", $where_conditions);
            }
        }
        
        // Ordenar resultados
        switch($orden) {
            case 'fecha':
                $sql .= " ORDER BY fecha_creacion DESC";
                break;
            case 'visitas':
                $sql .= " ORDER BY visitas DESC";
                break;
            default: // relevancia
                $sql .= " ORDER BY relevancia DESC, fecha_creacion DESC";
                break;
        }
        
        // Limitar resultados (paginación futura)
        $sql .= " LIMIT 50";
        
        // Ejecutar consulta
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $resultados = $stmt->fetchAll();
        $total_resultados = count($resultados);
        
    } catch (PDOException $e) {
        $mensaje = "❌ Error en la búsqueda: " . $e->getMessage();
    }
}

// Obtener lista de foros para el filtro
$foros = [];
if ($pdo) {
    $foros = $pdo->query("SELECT id, nombre FROM foros WHERE activo = 1 ORDER BY nombre")->fetchAll();
}
?>

<div class="card fade-in">
    <h2>&#128269; Buscar en el Foro</h2>
    <p>Encuentra debates, respuestas y usuarios en la comunidad</p>
</div>

<!-- Formulario de Búsqueda -->
<div class="card fade-in">
    <form method="GET" action="index.php">
        <input type="hidden" name="pagina" value="buscar">
        
        <!-- Búsqueda principal -->
        <div class="form-group">
            <label for="q">&#128269; Término de búsqueda</label>
            <input type="text" id="q" name="q" class="form-control" 
                   value="<?php echo htmlspecialchars($termino, ENT_QUOTES, 'UTF-8'); ?>" 
                   placeholder="Ej: tecnología libre, agricultura urbana, @usuario..."
                   required>
            <small class="text-muted">
                &#128161; <strong>Operadores avanzados:</strong> 
                Use "+" para requerir términos, "-" para excluir, " " para frases exactas
            </small>
        </div>
        
        <!-- Filtros en grid responsive -->
        <div class="grid grid-2" style="gap: 1rem;">
            <!-- Tipo de búsqueda -->
            <div class="form-group">
                <label for="tipo">&#128462; Buscar en</label>
                <select id="tipo" name="tipo" class="form-control">
                    <option value="todo" <?php echo $tipo === 'todo' ? 'selected' : ''; ?>>Todo el contenido</option>
                    <option value="hilos" <?php echo $tipo === 'hilos' ? 'selected' : ''; ?>>Solo hilos</option>
                    <option value="respuestas" <?php echo $tipo === 'respuestas' ? 'selected' : ''; ?>>Solo respuestas</option>
                    <option value="usuarios" <?php echo $tipo === 'usuarios' ? 'selected' : ''; ?>>Solo usuarios</option>
                </select>
            </div>
            
            <!-- Orden de resultados -->
            <div class="form-group">
                <label for="orden">&#8645; Ordenar por</label>
                <select id="orden" name="orden" class="form-control">
                    <option value="relevancia" <?php echo $orden === 'relevancia' ? 'selected' : ''; ?>>Relevancia</option>
                    <option value="fecha" <?php echo $orden === 'fecha' ? 'selected' : ''; ?>>Más reciente</option>
                    <option value="visitas" <?php echo $orden === 'visitas' ? 'selected' : ''; ?>>Más visitado</option>
                </select>
            </div>
        </div>
        
        <!-- Filtros avanzados -->
        <div class="grid grid-3" style="gap: 1rem;">
            <!-- Filtro por foro -->
            <div class="form-group">
                <label for="foro_id">&#128218; Filtrar por foro</label>
                <select id="foro_id" name="foro_id" class="form-control">
                    <option value="">Todos los foros</option>
                    <?php foreach ($foros as $foro): ?>
                    <option value="<?php echo $foro['id']; ?>" 
                            <?php echo $foro_id == $foro['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($foro['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Filtro por autor -->
            <div class="form-group">
                <label for="autor">&#128100; Filtrar por autor</label>
                <input type="text" id="autor" name="autor" class="form-control" 
                       value="<?php echo htmlspecialchars($autor, ENT_QUOTES, 'UTF-8'); ?>" 
                       placeholder="Nombre de usuario...">
            </div>
            
            <!-- Filtro por fecha -->
            <div class="form-group">
                <label for="fecha_inicio">&#128197; Rango de fechas</label>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" 
                           value="<?php echo htmlspecialchars($fecha_inicio, ENT_QUOTES, 'UTF-8'); ?>" 
                           placeholder="Desde...">
                    <input type="date" id="fecha_fin" name="fecha_fin" class="form-control" 
                           value="<?php echo htmlspecialchars($fecha_fin, ENT_QUOTES, 'UTF-8'); ?>" 
                           placeholder="Hasta...">
                </div>
            </div>
        </div>
        
        <!-- Botones de acción -->
        <div style="display: flex; gap: 1rem; margin-top: 1.5rem; flex-wrap: wrap;">
            <button type="submit" class="btn btn-success">&#128269; Buscar</button>
            <a href="?pagina=buscar" class="btn btn-secondary">&#128260; Limpiar filtros</a>
            <button type="button" id="btn-busqueda-avanzada" class="btn btn-info">
                &#9881; Búsqueda avanzada
            </button>
        </div>
    </form>
</div>

<!-- Panel de Búsqueda Avanzada (oculto inicialmente) -->
<div id="panel-avanzado" class="card fade-in" style="display: none; background: #f8f9fa;">
    <h3>&#9881; Búsqueda Avanzada</h3>
    
    <div class="grid grid-2" style="gap: 1rem;">
        <div>
            <h4>&#128161; Operadores de búsqueda</h4>
            <ul style="font-size: 0.9rem; line-height: 1.6;">
                <li><code>tecnología libre</code> - Búsqueda exacta</li>
                <li><code>+tecnología +libre</code> - Ambos términos requeridos</li>
                <li><code>tecnología -software</code> - Excluye "software"</li>
                <li><code>"hardware libre"</code> - Frase exacta</li>
                <li><code>tecnología*</code> - Comodín al final</li>
                <li><code>@usuario</code> - Buscar por mención</li>
            </ul>
        </div>
        
        <div>
            <h4>&#128290; Consejos de búsqueda</h4>
            <ul style="font-size: 0.9rem; line-height: 1.6;">
                <li>Use palabras clave específicas</li>
                <li>Combine filtros para resultados precisos</li>
                <li>Busque por autor para ver toda su actividad</li>
                <li>Use fechas para contenido reciente</li>
                <li>Pruebe diferentes tipos de búsqueda</li>
            </ul>
        </div>
    </div>
</div>

<!-- Resultados de Búsqueda -->
<?php if (!empty($termino)): ?>
<div class="card fade-in">
    <div style="display: flex; justify-content: between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h3>&#128230; Resultados de búsqueda</h3>
            <p style="margin: 0; color: #666;">
                <?php if ($total_resultados > 0): ?>
                    Se encontraron <strong><?php echo $total_resultados; ?></strong> resultados para "<strong><?php echo htmlspecialchars($termino, ENT_QUOTES, 'UTF-8'); ?></strong>"
                <?php else: ?>
                    No se encontraron resultados para "<strong><?php echo htmlspecialchars($termino, ENT_QUOTES, 'UTF-8'); ?></strong>"
                <?php endif; ?>
            </p>
        </div>
        
        <?php if ($total_resultados > 0): ?>
        <div style="font-size: 0.9rem; color: #666;">
            Ordenado por: <strong><?php 
                echo $orden === 'fecha' ? 'Más reciente' : 
                     ($orden === 'visitas' ? 'Más visitado' : 'Relevancia'); 
            ?></strong>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($total_resultados > 0): ?>
    <?php foreach ($resultados as $resultado): ?>
    <div class="card resultado-item fade-in">
        <div style="display: flex; gap: 1rem; align-items: flex-start;">
            <!-- Icono según tipo -->
            <div style="font-size: 2rem; color: #666; min-width: 50px; text-align: center;">
                <?php 
                switch($resultado['tipo']) {
                    case 'hilo': echo '💬'; break;
                    case 'respuesta': echo '↪️'; break;
                    case 'usuario': echo '👤'; break;
                    default: echo '📄';
                }
                ?>
            </div>
            
            <!-- Contenido del resultado -->
            <div style="flex: 1;">
                <!-- Título/Encabezado -->
                <h4 style="margin: 0 0 0.5rem 0;">
                    <?php if ($resultado['tipo'] === 'hilo'): ?>
                        <a href="?pagina=ver_hilo&id=<?php echo $resultado['id']; ?>" 
                           style="text-decoration: none; color: inherit;">
                            <?php echo htmlspecialchars($resultado['titulo'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php elseif ($resultado['tipo'] === 'respuesta'): ?>
                        <a href="?pagina=ver_hilo&id=<?php echo $resultado['hilo_id']; ?>#respuesta-<?php echo $resultado['id']; ?>" 
                           style="text-decoration: none; color: inherit;">
                            Respuesta en: <?php echo htmlspecialchars($resultado['hilo_titulo'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php else: // usuario ?>
                        <a href="?pagina=perfil&id=<?php echo $resultado['id']; ?>" 
                           style="text-decoration: none; color: inherit;">
                            👤 <?php echo htmlspecialchars($resultado['titulo'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php endif; ?>
                </h4>
                
                <!-- Extracto del contenido -->
                <?php if (!empty($resultado['contenido'])): ?>
                <div style="color: #666; margin-bottom: 0.5rem; line-height: 1.4;">
                    <?php 
                    $contenido = htmlspecialchars($resultado['contenido'], ENT_QUOTES, 'UTF-8');
                    // Resaltar términos de búsqueda
                    $palabras = explode(' ', $termino);
                    foreach ($palabras as $palabra) {
                        if (strlen(trim($palabra)) > 2) {
                            $contenido = preg_replace(
                                "/\b(" . preg_quote(trim($palabra), '/') . ")\b/i", 
                                "<mark>$1</mark>", 
                                $contenido
                            );
                        }
                    }
                    echo substr($contenido, 0, 200) . (strlen($contenido) > 200 ? '...' : '');
                    ?>
                </div>
                <?php endif; ?>
                
                <!-- Metadatos -->
                <div style="display: flex; flex-wrap: wrap; gap: 1rem; font-size: 0.8rem; color: #888;">
                    <span>
                        <strong>👤</strong> 
                        <?php echo htmlspecialchars($resultado['autor_nombre'], ENT_QUOTES, 'UTF-8'); ?>
                        (<?php echo htmlspecialchars($resultado['autor_usuario'], ENT_QUOTES, 'UTF-8'); ?>)
                    </span>
                    
                    <?php if (!empty($resultado['foro_nombre'])): ?>
                    <span>
                        <strong>📁</strong> 
                        <?php echo htmlspecialchars($resultado['foro_nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <?php endif; ?>
                    
                    <span>
                        <strong>📅</strong> 
                        <?php echo date('d/m/Y H:i', strtotime($resultado['fecha_creacion'])); ?>
                    </span>
                    
                    <?php if ($resultado['visitas']): ?>
                    <span>
                        <strong>👁️</strong> 
                        <?php echo $resultado['visitas']; ?> visitas
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Badge de tipo -->
            <div style="min-width: 100px; text-align: center;">
                <span style="
                    background: <?php 
                        echo $resultado['tipo'] === 'hilo' ? '#e3f2fd' : 
                               ($resultado['tipo'] === 'respuesta' ? '#e8f5e8' : '#fff3e0'); 
                    ?>; 
                    color: <?php 
                        echo $resultado['tipo'] === 'hilo' ? '#2196F3' : 
                               ($resultado['tipo'] === 'respuesta' ? '#4CAF50' : '#FF9800'); 
                    ?>;
                    padding: 0.3rem 0.6rem; 
                    border-radius: 15px; 
                    font-size: 0.7rem; 
                    font-weight: bold;
                    text-transform: uppercase;
                ">
                    <?php 
                    echo $resultado['tipo'] === 'hilo' ? 'Hilo' : 
                         ($resultado['tipo'] === 'respuesta' ? 'Respuesta' : 'Usuario'); 
                    ?>
                </span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

<?php else: ?>
    <!-- Sin resultados -->
    <div class="card fade-in">
        <div style="text-align: center; padding: 3rem;">
            <h3>🔍 No se encontraron resultados</h3>
            <p>Intenta con otros términos o ajusta los filtros de búsqueda.</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-top: 1rem;">
                <a href="?pagina=buscar" class="btn btn-info">🔄 Intentar nueva búsqueda</a>
                <a href="?pagina=foros" class="btn btn-success">📋 Explorar foros</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php endif; ?>

<!-- Script para búsqueda avanzada -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnAvanzado = document.getElementById('btn-busqueda-avanzada');
    const panelAvanzado = document.getElementById('panel-avanzado');
    
    btnAvanzado.addEventListener('click', function() {
        if (panelAvanzado.style.display === 'none') {
            panelAvanzado.style.display = 'block';
            btnAvanzado.innerHTML = '⬆️ Ocultar avanzado';
        } else {
            panelAvanzado.style.display = 'none';
            btnAvanzado.innerHTML = '⚙️ Búsqueda avanzada';
        }
    });
    
    // Auto-focus en campo de búsqueda
    document.getElementById('q').focus();
    
    // Sugerir búsquedas populares
    const campoBusqueda = document.getElementById('q');
    campoBusqueda.addEventListener('input', function() {
        // Podrías agregar aquí sugerencias AJAX en el futuro
    });
});
</script>
