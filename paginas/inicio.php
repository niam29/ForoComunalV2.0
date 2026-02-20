<?php
require_once 'includes/config.php';

// Obtener estadísticas reales de la base de datos
if ($pdo) {
    try {
        // Contar foros activos
        $stmt_foros = $pdo->query("SELECT COUNT(*) as total FROM foros WHERE activo = 1");
        $total_foros = $stmt_foros->fetch()['total'];
        
        // Contar usuarios activos
        $stmt_usuarios = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
        $total_usuarios = $stmt_usuarios->fetch()['total'];
        
        // Contar total de hilos (debates)
        $stmt_hilos = $pdo->query("SELECT COUNT(*) as total FROM hilos");
        $total_hilos = $stmt_hilos->fetch()['total'];
        
        // Contar total de respuestas (participaciones)
        $stmt_respuestas = $pdo->query("SELECT COUNT(*) as total FROM respuestas");
        $total_respuestas = $stmt_respuestas->fetch()['total'];
        
        // Obtener actividad reciente
        $stmt_reciente = $pdo->query("
            SELECT h.titulo, u.usuario, f.nombre as foro_nombre, h.fecha_creacion 
            FROM hilos h 
            JOIN usuarios u ON h.usuario_id = u.id 
            JOIN foros f ON h.foro_id = f.id 
            WHERE f.activo = 1 
            ORDER BY h.fecha_creacion DESC 
            LIMIT 3
        ");
        $actividad_reciente = $stmt_reciente->fetchAll();
        
    } catch (PDOException $e) {
        // En caso de error, usar valores por defecto
        $total_foros = 0;
        $total_usuarios = 0;
        $total_hilos = 0;
        $total_respuestas = 0;
        $actividad_reciente = [];
    }
} else {
    // Si no hay conexión a BD, usar valores de demostración
    $total_foros = 0;
    $total_usuarios = 0;
    $total_hilos = 0;
    $total_respuestas = 0;
    $actividad_reciente = [];
}
?>

<div class="card fade-in">
    <h2 class="text-center">¡Bienvenid@ al Foro Comunal CTI! &#127981;</h2>
    <p class="text-center">Espacio de diálogo para la Ciencia, Tecnología e Innovación del Poder Popular</p>
    
    <div class="grid grid-2 mt-1">
        <a href="index.php?pagina=foros" class="btn">&#128203; Ver Foros</a>
        <?php if (!isset($_SESSION['usuario_id'])): ?>
            <a href="index.php?pagina=registro" class="btn btn-success">&#128100; Registrarse</a>
        <?php else: ?>
            <a href="index.php?pagina=perfil" class="btn btn-success">&#128100; Mi Perfil</a>
        <?php endif; ?>
    </div>
</div>

<!-- Estadísticas reales de la comunidad -->
<div class="card fade-in">
    <h3 class="text-center">&#128202; Comunidad Activa</h3>
    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-number"><?php echo $total_foros; ?></span>
            <span class="stat-label">Foros Activos</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?php echo $total_usuarios; ?></span>
            <span class="stat-label">Usuarios Registrados</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?php echo $total_hilos; ?></span>
            <span class="stat-label">Debates</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?php echo $total_respuestas; ?></span>
            <span class="stat-label">Participaciones</span>
        </div>
    </div>
    
    <?php if ($total_foros == 0 && $pdo): ?>
    <div style="text-align: center; margin-top: 1rem; padding: 1rem; background: #fff3e0; border-radius: 8px;">
        <p style="margin: 0; color: #E65100;">
            &#128161; <strong>¡Aún no hay foros creados!</strong><br>
            <?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'coordinador'): ?>
            <a href="?pagina=crear_foro" style="color: #E65100; font-weight: bold;">Crea el primer foro</a> para comenzar.
            <?php else: ?>
            Los coordinadores están configurando los primeros espacios de diálogo.
            <?php endif; ?>
        </p>
    </div>
    <?php endif; ?>
</div>

<!-- Espacios destacados -->
<div class="card fade-in">
    <h3>&#11088; Nuestros Espacios</h3>
    <div class="mt-1">
        <div class="card" style="border-left-color: #2196F3;">
            <h4>&#128300; Ciencia, Tecnología e Innovación</h4>
            <p>Soberanía tecnológica y desarrollo comunitario. Espacio para debatir sobre software libre, hardware abierto y soluciones tecnológicas populares.</p>
        </div>
        
        <div class="card" style="border-left-color: #4CAF50;">
            <h4>&#128226; Comunicación Popular</h4>
            <p>Método Calles y medios comunitarios. Estrategias de comunicación alternativa y organización popular.</p>
        </div>
        
        <div class="card" style="border-left-color: #FF9800;">
            <h4>&#127968; Organización Comunitaria</h4>
            <p>Espacios de coordinación y trabajo colectivo. Planificación de actividades y proyectos comunitarios.</p>
        </div>
    </div>
</div>

<!-- Actividad reciente -->
<?php if (!empty($actividad_reciente)): ?>
<div class="card fade-in">
    <h3>&#128293; Actividad Reciente</h3>
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <?php foreach ($actividad_reciente as $actividad): ?>
        <div style="display: flex; flex-direction: column; gap: 0.5rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
            <div style="font-weight: bold; display: flex; align-items: center; gap: 0.5rem;">
                &#128172; 
                <a href="index.php?pagina=foros" style="text-decoration: none; color: inherit;">
                    <?php echo htmlspecialchars($actividad['titulo'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.9rem; color: #666; flex-wrap: wrap;">
                <div>
                    <strong>Foro:</strong> <?php echo htmlspecialchars($actividad['foro_nombre'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div>
                    <strong>Por:</strong> @<?php echo htmlspecialchars($actividad['usuario'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div>
                    <strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($actividad['fecha_creacion'])); ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div style="text-align: center; margin-top: 1rem;">
        <a href="index.php?pagina=foros" class="btn btn-info">&#128269; Ver Más Actividad</a>
    </div>
</div>
<?php elseif ($pdo && $total_hilos == 0): ?>
<div class="card fade-in">
    <div style="text-align: center; padding: 2rem;">
        <h4>&#128203; Aún no hay actividad</h4>
        <p>¡Sé el primero en iniciar un debate en la comunidad!</p>
        <?php if (isset($_SESSION['usuario_id'])): ?>
        <a href="index.php?pagina=crear_hilo" class="btn btn-success" style="margin-top: 1rem;">
            &#128640; Crear Primer Debate
        </a>
        <?php else: ?>
        <a href="index.php?pagina=registro" class="btn btn-success" style="margin-top: 1rem;">
            &#128100; Únete para Participar
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Información para nuevos usuarios -->
<?php if (!isset($_SESSION['usuario_id'])): ?>
<div class="card fade-in" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
    <div style="text-align: center;">
        <h3 style="color: white;">&#129309; Únete a la Comunidad</h3>
        <p>Forma parte del diálogo colectivo para la transformación social</p>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
            <div>
                <h4 style="color: white;">&#128100; Regístrate</h4>
                <p style="font-size: 0.9rem; opacity: 0.9;">Crea tu cuenta en 2 minutos</p>
            </div>
            <div>
                <h4 style="color: white;">&#128172; Participa</h4>
                <p style="font-size: 0.9rem; opacity: 0.9;">Únete a los debates comunitarios</p>
            </div>
        </div>
        <a href="index.php?pagina=registro" class="btn-panel" style="margin-top: 1rem;">
            &#128100; Comenzar Ahora
        </a>
    </div>
</div>
<?php endif; ?>