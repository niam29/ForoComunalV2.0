<?php
require_once 'includes/config.php';

$foro_id = intval($_GET['id'] ?? 0);

if ($pdo) {
    // Obtener información del foro
    $foro = $pdo->prepare("SELECT * FROM foros WHERE id = ?");
    $foro->execute([$foro_id]);
    $foro = $foro->fetch();
    
    if (!$foro) {
        echo "<div class='card'><p>❌ Foro no encontrado</p></div>";
        return;
    }
    
    // Obtener hilos de este foro
    $hilos = $pdo->prepare("
        SELECT h.*, 
               u.usuario as autor_usuario,
               u.nombre as autor_nombre,
               COUNT(r.id) as total_respuestas,
               MAX(r.fecha_respuesta) as ultima_respuesta
        FROM hilos h 
        LEFT JOIN usuarios u ON h.usuario_id = u.id 
        LEFT JOIN respuestas r ON h.id = r.hilo_id 
        WHERE h.foro_id = ? 
        GROUP BY h.id 
        ORDER BY h.es_importante DESC, h.fecha_actualizacion DESC
    ");
    $hilos->execute([$foro_id]);
    $hilos = $hilos->fetchAll();
} else {
    $foro = ['nombre' => 'Foro Demo', 'descripcion' => 'Descripción de demostración'];
    $hilos = [];
}
?>

<div class="card">
    <div style="display: flex; justify-content: between; align-items: center;">
        <div>
            <h2><?php echo $foro['nombre']; ?></h2>
            <p><?php echo $foro['descripcion']; ?></p>
        </div>
        <?php if (isset($_SESSION['usuario_id'])): ?>
        <a href="?pagina=crear_hilo&foro_id=<?php echo $foro_id; ?>" class="btn">📝 Nuevo Hilo</a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_SESSION['usuario_id'])): ?>
<div style="text-align: right; margin-bottom: 1rem;">
    <a href="?pagina=crear_hilo&foro_id=<?php echo $foro_id; ?>" class="btn">➕ Crear Nuevo Hilo</a>
</div>
<?php else: ?>
<div class="card" style="background: #fff3e0; border-left: 4px solid #ff9800;">
    <p>⚠️ Debes <a href="?pagina=login">iniciar sesión</a> para participar en los debates.</p>
</div>
<?php endif; ?>

<?php if (empty($hilos)): ?>
<div class="card">
    <div style="text-align: center; padding: 3rem;">
        <h3>📝 No hay hilos en este foro</h3>
        <p>¡Sé el primero en iniciar una discusión!</p>
        <?php if (isset($_SESSION['usuario_id'])): ?>
        <a href="?pagina=crear_hilo&foro_id=<?php echo $foro_id; ?>" class="btn" style="margin-top: 1rem;">🚀 Crear Primer Hilo</a>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
    <?php foreach ($hilos as $hilo): ?>
    <div class="card hilo-item <?php echo $hilo['es_importante'] ? 'hilo-importante' : ''; ?>">
        <div style="display: flex; gap: 1rem;">
        <div style="text-align: center; min-width: 60px;">
            <div style="font-size: 1.2rem; font-weight: bold;"><?php echo $hilo['total_respuestas']; ?></div>
            <div style="font-size: 0.7rem; color: #666;">respuestas</div>
        </div>
        
        <div style="flex: 1;">
            <h3 style="margin: 0 0 0.5rem 0;">
                <a href="?pagina=ver_hilo&id=<?php echo $hilo['id']; ?>" style="text-decoration: none; color: inherit;">
                    <?php echo $hilo['titulo']; ?>
                    <?php if ($hilo['es_importante']): ?>
                    <span style="background: #FFD100; color: #000; padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.7rem; margin-left: 0.5rem;">⭐ Importante</span>
                    <?php endif; ?>
                    <?php if ($hilo['es_cerrado']): ?>
                    <span style="background: #666; color: white; padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.7rem; margin-left: 0.5rem;">🔒 Cerrado</span>
                    <?php endif; ?>
                </a>
            </h3>
            <p style="margin: 0; color: #666; font-size: 0.9rem;">
                Por: <strong><?php echo $hilo['autor_nombre'] . ' (@' . $hilo['autor_usuario'] . ')'; ?></strong> 
                • 📅 <?php echo date('d/m/Y H:i', strtotime($hilo['fecha_creacion'])); ?>
                <?php if ($hilo['visitas'] > 0): ?>
                • 👁️ <?php echo $hilo['visitas']; ?> visitas
                <?php endif; ?>
            </p>
        </div>
        
        <div style="text-align: right; min-width: 150px;">
            <?php if ($hilo['ultima_respuesta']): ?>
            <div style="font-size: 0.8rem;">
                <strong>Última respuesta:</strong><br>
                <?php echo date('d/m/Y H:i', strtotime($hilo['ultima_respuesta'])); ?>
            </div>
            <?php else: ?>
            <div style="font-size: 0.8rem; color: #666;">
                Sin respuestas aún
            </div>
            <?php endif; ?>
        </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>