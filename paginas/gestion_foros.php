<?php
require_once 'includes/config.php';

// Verificar si el usuario es coordinador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'coordinador') {
    echo "<div class='card'><p>🚫 Acceso restringido para coordinadores</p></div>";
    return;
}

// Procesar acciones rápidas
if (isset($_GET['accion']) && isset($_GET['id'])) {
    $foro_id = intval($_GET['id']);
    $accion = $_GET['accion'];
    
    switch ($accion) {
        case 'activar':
            $pdo->prepare("UPDATE foros SET activo = 1 WHERE id = ?")->execute([$foro_id]);
            $_SESSION['mensaje'] = "✅ Foro activado correctamente";
        echo "<script>window.location.href = '?pagina=gestion_foros';</script>";
            break;
            
        case 'desactivar':
            $pdo->prepare("UPDATE foros SET activo = 0 WHERE id = ?")->execute([$foro_id]);
            $_SESSION['mensaje'] = "⏸️ Foro desactivado correctamente";
        echo "<script>window.location.href = '?pagina=gestion_foros';</script>";
            break;
            
        case 'eliminar':
            // Solo eliminar si no tiene hilos
            $hilos = $pdo->prepare("SELECT COUNT(*) as total FROM hilos WHERE foro_id = ?");
            $hilos->execute([$foro_id]);
            $total_hilos = $hilos->fetch()['total'];
            
            if ($total_hilos == 0) {
                $pdo->prepare("DELETE FROM foros WHERE id = ?")->execute([$foro_id]);
                $_SESSION['mensaje'] = "🗑️ Foro eliminado correctamente";
            } else {
                $_SESSION['error'] = "❌ No se puede eliminar un foro que contiene hilos";
            }
        echo "<script>window.location.href = '?pagina=gestion_foros';</script>";
            break;
    }
    
    header("Location: ?pagina=gestion_foros");
    exit();
}

// Mostrar mensajes
if (isset($_SESSION['mensaje'])) {
    echo "<div class='card' style='background: #e8f5e8; border-left: 4px solid #4CAF50;'>";
    echo "<p>" . $_SESSION['mensaje'] . "</p>";
    echo "</div>";
    unset($_SESSION['mensaje']);
}

if (isset($_SESSION['error'])) {
    echo "<div class='card' style='background: #ffebee; border-left: 4px solid #f44336;'>";
    echo "<p>" . $_SESSION['error'] . "</p>";
    echo "</div>";
    unset($_SESSION['error']);
}

// Obtener todos los foros
$foros = $pdo->query("
    SELECT f.*, 
           COUNT(DISTINCT h.id) as total_hilos,
           COUNT(DISTINCT r.id) as total_respuestas,
           MAX(h.fecha_actualizacion) as ultima_actividad
    FROM foros f 
    LEFT JOIN hilos h ON f.id = h.foro_id 
    LEFT JOIN respuestas r ON h.id = r.hilo_id 
    GROUP BY f.id 
    ORDER BY f.orden, f.nombre
")->fetchAll();
?>

<div class="card">
    <h2>⚙️ Gestión de Foros</h2>
    <p>Administra los espacios de diálogo de la comunidad</p>
    
    <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem;">
        <a href="?pagina=crear_foro" class="btn">🏗️ Crear Nuevo Foro</a>
        <a href="?pagina=foros" class="btn" style="background: #666;">📋 Ver Foros Públicos</a>
    </div>
    
    <!-- Estadísticas rápidas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin: 1rem 0;">
        <?php
        $total_foros = count($foros);
        $foros_activos = array_filter($foros, function($f) { return $f['activo']; });
        $foros_inactivos = $total_foros - count($foros_activos);
        ?>
        <div style="background: #e3f2fd; padding: 1rem; border-radius: 8px; text-align: center;">
            <div style="font-size: 2rem; font-weight: bold; color: #2196F3;"><?php echo $total_foros; ?></div>
            <div style="font-size: 0.8rem;">Total Foros</div>
        </div>
        <div style="background: #e8f5e8; padding: 1rem; border-radius: 8px; text-align: center;">
            <div style="font-size: 2rem; font-weight: bold; color: #4CAF50;"><?php echo count($foros_activos); ?></div>
            <div style="font-size: 0.8rem;">Activos</div>
        </div>
        <div style="background: #fff3e0; padding: 1rem; border-radius: 8px; text-align: center;">
            <div style="font-size: 2rem; font-weight: bold; color: #FF9800;"><?php echo $foros_inactivos; ?></div>
            <div style="font-size: 0.8rem;">Inactivos</div>
        </div>
    </div>
</div>

<?php if (empty($foros)): ?>
<div class="card">
    <div style="text-align: center; padding: 3rem;">
        <h3>📝 No hay foros creados</h3>
        <p>Comienza creando el primer foro para la comunidad</p>
        <a href="?pagina=crear_foro" class="btn" style="margin-top: 1rem;">🚀 Crear Primer Foro</a>
    </div>
</div>
<?php else: ?>
    <!-- Filtros -->
    <div class="card">
        <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <strong>Filtrar:</strong>
            <a href="?pagina=gestion_foros" class="btn" style="background: <?php echo !isset($_GET['estado']) ? '#CC0000' : '#666'; ?>; padding: 0.5rem 1rem;">Todos</a>
            <a href="?pagina=gestion_foros&estado=activo" class="btn" style="background: <?php echo ($_GET['estado'] ?? '') == 'activo' ? '#4CAF50' : '#666'; ?>; padding: 0.5rem 1rem;">Activos</a>
            <a href="?pagina=gestion_foros&estado=inactivo" class="btn" style="background: <?php echo ($_GET['estado'] ?? '') == 'inactivo' ? '#FF9800' : '#666'; ?>; padding: 0.5rem 1rem;">Inactivos</a>
        </div>
    </div>

    <?php 
    // Aplicar filtro si existe
    $foros_filtrados = $foros;
    if (isset($_GET['estado'])) {
        if ($_GET['estado'] == 'activo') {
            $foros_filtrados = array_filter($foros, function($f) { return $f['activo']; });
        } elseif ($_GET['estado'] == 'inactivo') {
            $foros_filtrados = array_filter($foros, function($f) { return !$f['activo']; });
        }
    }
    ?>
    
    <?php foreach ($foros_filtrados as $foro): ?>
    <div class="card foro-gestion-item <?php echo $foro['activo'] ? '' : 'foro-inactivo'; ?>">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
            <div style="flex: 1;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <span style="font-size: 1.2rem;">
                        <?php
                        $iconos_map = [
                            'folder' => '📁',
                            'science' => '🔬',
                            'tech' => '💻',
                            'comms' => '📢',
                            'community' => '🏘️',
                            'education' => '📚',
                            'ecology' => '🌱',
                            'energy' => '⚡',
                            'workshop' => '🛠️'
                        ];
                        echo $iconos_map[$foro['icono']] ?? '📁';
                        ?>
                    </span>
                    <h3 style="margin: 0; color: <?php echo $foro['activo'] ? 'inherit' : '#666'; ?>;">
                        <?php echo $foro['nombre']; ?>
                        <?php if (!$foro['activo']): ?>
                        <span style="background: #666; color: white; padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.7rem; margin-left: 0.5rem;">⏸️ INACTIVO</span>
                        <?php endif; ?>
                    </h3>
                </div>
                
                <p style="margin: 0 0 0.5rem 0; color: #666;"><?php echo $foro['descripcion']; ?></p>
                
                <div style="display: flex; gap: 1.5rem; font-size: 0.8rem; color: #888; flex-wrap: wrap;">
                    <span><strong>👤 Responsable:</strong> <?php echo $foro['responsable']; ?></span>
                    <span><strong>🎨 Color:</strong> <span style="color: <?php echo $foro['color']; ?>;">■</span> <?php echo $foro['color']; ?></span>
                    <span><strong>🔐 Acceso:</strong> <?php echo ucfirst($foro['permisos']); ?></span>
                    <span><strong>🔢 Orden:</strong> <?php echo $foro['orden']; ?></span>
                </div>
                
                <div style="margin-top: 0.5rem;">
                    <span style="background: #e3f2fd; padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.7rem;">
                        📝 <?php echo $foro['total_hilos']; ?> hilos
                    </span>
                    <span style="background: #e8f5e8; padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.7rem; margin-left: 0.5rem;">
                        💬 <?php echo $foro['total_respuestas']; ?> respuestas
                    </span>
                    <?php if ($foro['ultima_actividad']): ?>
                    <span style="background: #fff3e0; padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.7rem; margin-left: 0.5rem;">
                        🕒 <?php echo date('d/m/Y', strtotime($foro['ultima_actividad'])); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div style="display: flex; gap: 0.5rem; flex-direction: column; min-width: 180px;">
                <!-- Editar -->
                <a href="?pagina=editar_foro&id=<?php echo $foro['id']; ?>" class="btn" style="background: #2196F3; padding: 0.5rem; text-align: center;">
                    ✏️ Editar
                </a>
                
                <!-- Activar/Desactivar -->
                <?php if ($foro['activo']): ?>
                <a href="?pagina=gestion_foros&accion=desactivar&id=<?php echo $foro['id']; ?>" 
                   class="btn" style="background: #FF9800; padding: 0.5rem; text-align: center;">
                    ⏸️ Desactivar
                </a>
                <?php else: ?>
                <a href="?pagina=gestion_foros&accion=activar&id=<?php echo $foro['id']; ?>" 
                   class="btn" style="background: #4CAF50; padding: 0.5rem; text-align: center;">
                    ▶️ Activar
                </a>
                <?php endif; ?>
                
                <!-- Eliminar (solo si no tiene hilos) -->
                <?php if ($foro['total_hilos'] == 0): ?>
                <a href="?pagina=gestion_foros&accion=eliminar&id=<?php echo $foro['id']; ?>" 
                   onclick="return confirm('¿Estás seguro de ELIMINAR permanentemente el foro \"<?php echo $foro['nombre']; ?>\"? Esta acción no se puede deshacer.')"
                   class="btn" style="background: #f44336; padding: 0.5rem; text-align: center;">
                    🗑️ Eliminar
                </a>
                <?php else: ?>
                <span style="background: #f5f5f5; padding: 0.5rem; text-align: center; border-radius: 5px; font-size: 0.8rem; color: #666;">
                    🔒 Con hilos (no eliminable)
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
