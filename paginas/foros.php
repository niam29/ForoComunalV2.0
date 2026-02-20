<?php
require_once 'includes/config.php';

// Obtener foros con estadísticas reales
if ($pdo) {
    $foros = $pdo->query("
        SELECT f.*, 
               COUNT(DISTINCT h.id) as total_hilos,
               COUNT(DISTINCT r.id) as total_respuestas,
               MAX(h.fecha_actualizacion) as ultima_actividad
        FROM foros f 
        LEFT JOIN hilos h ON f.id = h.foro_id 
        LEFT JOIN respuestas r ON h.id = r.hilo_id 
        WHERE f.activo = 1
        GROUP BY f.id 
        ORDER BY f.orden, f.nombre
    ")->fetchAll();
} else {
    // Datos de demostración si no hay BD
    $foros = [
        [
            'id' => 1,
            'nombre' => 'Tecnología Libre',
            'descripcion' => 'Software libre y soberanía tecnológica',
            'responsable' => 'Coordinador CTI',
            'icono' => 'tech',
            'color' => '#CC0000',
            'permisos' => 'todos',
            'total_hilos' => 5,
            'total_respuestas' => 23,
            'ultima_actividad' => date('Y-m-d H:i:s')
        ]
    ];
}

// Mapeo de iconos a emojis (USANDO HTML ENTITIES PARA SEGURIDAD)
$iconos_map = [
    'folder' => '&#128193;',
    'science' => '&#128300;',
    'tech' => '&#128187;',
    'comms' => '&#128226;',
    'community' => '&#127968;',
    'education' => '&#128218;',
    'ecology' => '&#127793;',
    'energy' => '&#9889;',
    'workshop' => '&#128296;'
];

// Mapeo de permisos a texto con emojis seguros
$permisos_texto = [
    'todos' => '&#128101; Público',
    'registrados' => '&#128273; Registrados',
    'tecnicos' => '&#128296; Técnicos+',
    'coordinadores' => '&#128737; Coordinadores'
];
?>

<!-- Panel de Coordinador -->
<?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'coordinador'): ?>
<div class="card" style="background: linear-gradient(135deg, #FFD100, #E65100); color: white; border: none;">
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <div>
            <h3 style="margin: 0; color: white;">&#128737; Panel de Coordinador</h3>
            <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Gestiona los espacios de diálogo de la comunidad</p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="?pagina=crear_foro" class="btn" style="background: white; color: #E65100; font-weight: bold;">
                &#127959; Crear Nuevo Foro
            </a>
            <a href="?pagina=gestion_foros" class="btn" style="background: rgba(255,255,255,0.2); color: white;">
                &#9881; Gestionar Foros
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Encabezado de Foros -->
<div class="card fade-in">
    <div style="text-align: center;">
        <h2>&#128203; Foros Comunitarios</h2>
        <p>Participa en los debates del poder popular</p>
        
        <?php if (isset($_SESSION['usuario_id'])): ?>
        <div style="margin-top: 1rem;">
            <a href="?pagina=crear_hilo" class="btn btn-success" style="display: inline-block; width: auto; padding: 1rem 2rem;">
                &#9999; Crear Nuevo Hilo
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Lista de Foros -->
<?php if (empty($foros)): ?>
<div class="card fade-in">
    <div style="text-align: center; padding: 3rem;">
        <h3>&#128203; No hay foros disponibles</h3>
        <p>Los foros se están configurando. Vuelve pronto.</p>
        <?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'coordinador'): ?>
        <a href="?pagina=crear_foro" class="btn" style="margin-top: 1rem;">&#128640; Crear Primer Foro</a>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
    <?php foreach ($foros as $foro): ?>
    <?php
    // Verificar permisos de acceso al foro
    $puede_acceder = true;
    if ($foro['permisos'] === 'registrados' && !isset($_SESSION['usuario_id'])) {
        $puede_acceder = false;
    } elseif ($foro['permisos'] === 'tecnicos' && (!isset($_SESSION['usuario_rol']) || ($_SESSION['usuario_rol'] !== 'tecnico' && $_SESSION['usuario_rol'] !== 'coordinador'))) {
        $puede_acceder = false;
    } elseif ($foro['permisos'] === 'coordinadores' && (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'coordinador')) {
        $puede_acceder = false;
    }
    
    $icono_mostrar = $iconos_map[$foro['icono']] ?? '&#128193;';
    ?>
    
    <div class="card foro-item fade-in" style="border-left: 4px solid <?php echo $foro['color']; ?>;">
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <!-- Header del foro -->
            <div>
                <h3 style="margin: 0 0 0.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
                    <?php echo $icono_mostrar . ' ' . htmlspecialchars($foro['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php if (!$puede_acceder): ?>
                    <span class="badge badge-warning">&#128274; Restringido</span>
                    <?php endif; ?>
                </h3>
                <p style="margin: 0 0 0.5rem 0; color: #666;"><?php echo htmlspecialchars($foro['descripcion'], ENT_QUOTES, 'UTF-8'); ?></p>
                
                <div style="display: flex; flex-wrap: wrap; gap: 1rem; font-size: 0.9rem; color: #888;">
                    <span><strong>&#128100; Responsable:</strong> <?php echo htmlspecialchars($foro['responsable'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <span>
                        <strong>&#127919; Acceso:</strong> 
                        <?php echo $permisos_texto[$foro['permisos']] ?? $foro['permisos']; ?>
                    </span>
                </div>
            </div>
            
            <!-- Estadísticas -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div style="text-align: center; padding: 0.5rem; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: #CC0000;"><?php echo $foro['total_hilos']; ?></div>
                    <div style="font-size: 0.8rem; color: #666;">Hilos</div>
                </div>
                
                <div style="text-align: center; padding: 0.5rem; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: #0033A0;"><?php echo $foro['total_respuestas']; ?></div>
                    <div style="font-size: 0.8rem; color: #666;">Respuestas</div>
                </div>
            </div>
        </div>
        
        <!-- Footer del foro -->
        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee; display: flex; flex-direction: column; gap: 1rem;">
            <div style="font-size: 0.9rem; color: #666;">
                <?php if ($foro['ultima_actividad']): ?>
                <strong>&#128337; Última actividad:</strong> <?php echo date('d/m/Y H:i', strtotime($foro['ultima_actividad'])); ?>
                <?php else: ?>
                <span>&#128203; Sin actividad aún</span>
                <?php endif; ?>
            </div>
            
            <div style="text-align: center;">
                <?php if ($puede_acceder): ?>
                <a href="?pagina=ver_foro&id=<?php echo $foro['id']; ?>" class="btn">
                    <?php echo $foro['total_hilos'] > 0 ? '&#128269; Ver Foro' : '&#128640; Iniciar Debate'; ?>
                </a>
                <?php else: ?>
                <div style="background: #fff3e0; color: #E65100; padding: 1rem; border-radius: 8px; border-left: 4px solid #FF9800;">
                    <p style="margin: 0; font-size: 0.9rem;">
                        <?php if (!isset($_SESSION['usuario_id'])): ?>
                        &#128274; <a href="?pagina=login" style="color: #E65100; font-weight: bold;">Inicia sesión</a> para acceder a este foro.
                        <?php elseif ($foro['permisos'] === 'tecnicos'): ?>
                        &#128296; Este foro es solo para técnicos y coordinadores.
                        <?php elseif ($foro['permisos'] === 'coordinadores'): ?>
                        &#128737; Este foro es solo para coordinadores.
                        <?php endif; ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Estadísticas generales -->
<?php if ($pdo && !empty($foros)): ?>
<?php
// Obtener estadísticas generales
$estadisticas = $pdo->query("
    SELECT 
        COUNT(DISTINCT h.id) as total_hilos_global,
        COUNT(DISTINCT r.id) as total_respuestas_global,
        COUNT(DISTINCT u.id) as total_usuarios_activos,
        MAX(h.fecha_creacion) as ultimo_hilo
    FROM foros f 
    LEFT JOIN hilos h ON f.id = h.foro_id 
    LEFT JOIN respuestas r ON h.id = r.hilo_id 
    LEFT JOIN usuarios u ON u.activo = 1
    WHERE f.activo = 1
")->fetch();

if ($estadisticas):
?>
<div class="card fade-in" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none;">
    <h3 style="margin: 0 0 1rem 0; color: white; text-align: center;">&#128202; Estadísticas de la Comunidad</h3>
    <div class="stats-grid">
        <div class="stat-card" style="background: rgba(255,255,255,0.1); color: white;">
            <span class="stat-number"><?php echo count($foros); ?></span>
            <span class="stat-label">Foros Activos</span>
        </div>
        <div class="stat-card" style="background: rgba(255,255,255,0.1); color: white;">
            <span class="stat-number"><?php echo $estadisticas['total_hilos_global']; ?></span>
            <span class="stat-label">Debates Abiertos</span>
        </div>
        <div class="stat-card" style="background: rgba(255,255,255,0.1); color: white;">
            <span class="stat-number"><?php echo $estadisticas['total_respuestas_global']; ?></span>
            <span class="stat-label">Participaciones</span>
        </div>
        <div class="stat-card" style="background: rgba(255,255,255,0.1); color: white;">
            <span class="stat-number"><?php echo $estadisticas['total_usuarios_activos']; ?></span>
            <span class="stat-label">Comunes Activos</span>
        </div>
    </div>
    
    <?php if ($estadisticas['ultimo_hilo']): ?>
    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.2); text-align: center;">
        <small>&#128337; Última actividad: <?php echo date('d/m/Y H:i', strtotime($estadisticas['ultimo_hilo'])); ?></small>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>
