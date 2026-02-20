<?php
require_once 'includes/config.php';

$hilo_id = intval($_GET['id'] ?? 0);

if (!$hilo_id) {
    echo "<div class='card'><p>❌ Hilo no especificado</p></div>";
    return;
}

if ($pdo) {
    // Incrementar contador de visitas
    $pdo->prepare("UPDATE hilos SET visitas = visitas + 1 WHERE id = ?")->execute([$hilo_id]);
    
    // Obtener información del hilo
    $hilo = $pdo->prepare("
        SELECT h.*, 
               u.usuario as autor_usuario,
               u.nombre as autor_nombre,
               u.apellido as autor_apellido,
               u.comuna as autor_comuna,
               f.nombre as foro_nombre,
               f.id as foro_id
        FROM hilos h 
        JOIN usuarios u ON h.usuario_id = u.id 
        JOIN foros f ON h.foro_id = f.id 
        WHERE h.id = ?
    ");
    $hilo->execute([$hilo_id]);
    $hilo = $hilo->fetch();
    
    if (!$hilo) {
        echo "<div class='card'><p>❌ Hilo no encontrado</p></div>";
        return;
    }
    
    // Obtener respuestas del hilo
    $respuestas = $pdo->prepare("
        SELECT r.*,
               u.usuario as autor_usuario,
               u.nombre as autor_nombre,
               u.apellido as autor_apellido,
               u.comuna as autor_comuna
        FROM respuestas r 
        JOIN usuarios u ON r.usuario_id = u.id 
        WHERE r.hilo_id = ? 
        ORDER BY r.fecha_respuesta ASC
    ");
    $respuestas->execute([$hilo_id]);
    $respuestas = $respuestas->fetchAll();
} else {
    echo "<div class='card'><p>❌ Base de datos no disponible</p></div>";
    return;
}
?>

<!-- Navegación -->
<div class="card" style="background: #f8f9fa;">
    <a href="?pagina=foros">📋 Foros</a> 
    > <a href="?pagina=ver_foro&id=<?php echo $hilo['foro_id']; ?>"><?php echo $hilo['foro_nombre']; ?></a>
    > <strong><?php echo $hilo['titulo']; ?></strong>
</div>

<!-- Hilo principal -->
<div class="card hilo-principal <?php echo $hilo['es_importante'] ? 'hilo-importante' : ''; ?>">
    <div style="display: flex; justify-content: between; align-items: flex-start; margin-bottom: 1rem;">
        <div>
            <h1 style="margin: 0 0 0.5rem 0;">
                <?php echo $hilo['titulo']; ?>
                <?php if ($hilo['es_importante']): ?>
                <span style="background: #FFD100; color: #000; padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.8rem; margin-left: 0.5rem;">⭐ Importante</span>
                <?php endif; ?>
                <?php if ($hilo['es_cerrado']): ?>
                <span style="background: #666; color: white; padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.8rem; margin-left: 0.5rem;">🔒 Cerrado</span>
                <?php endif; ?>
            </h1>
            <div style="color: #666; font-size: 0.9rem;">
                <strong><?php echo $hilo['autor_nombre'] . ' ' . $hilo['autor_apellido'] . ' (@' . $hilo['autor_usuario'] . ')'; ?></strong>
                • <?php echo $hilo['autor_comuna']; ?>
                • 📅 <?php echo date('d/m/Y H:i', strtotime($hilo['fecha_creacion'])); ?>
                • 👁️ <?php echo $hilo['visitas'] + 1; ?> visitas
            </div>
        </div>
        
        <?php if (isset($_SESSION['usuario_id']) && !$hilo['es_cerrado']): ?>
        <a href="#responder" class="btn">💬 Responder</a>
        <?php endif; ?>
    </div>
    
    <div style="
        background: white; 
        padding: 1.5rem; 
        border-radius: 8px; 
        border: 1px solid #eee;
        line-height: 1.6;
        white-space: pre-wrap;
    ">
        <?php echo nl2br(htmlspecialchars($hilo['contenido'])); ?>
    </div>
    
    <?php if ($hilo['fecha_actualizacion'] != $hilo['fecha_creacion']): ?>
    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee; font-size: 0.8rem; color: #666;">
        📝 Editado por última vez: <?php echo date('d/m/Y H:i', strtotime($hilo['fecha_actualizacion'])); ?>
    </div>
    <?php endif; ?>
</div>

<!-- Respuestas -->
<div class="card">
    <h3>💬 Respuestas (<?php echo count($respuestas); ?>)</h3>
    
    <?php if (empty($respuestas)): ?>
    <div style="text-align: center; padding: 2rem; color: #666;">
        <p>📝 Aún no hay respuestas. ¡Sé el primero en participar!</p>
    </div>
    <?php else: ?>
        <?php foreach ($respuestas as $index => $respuesta): ?>
        <div class="respuesta-item" style="
            padding: 1.5rem; 
            margin: 1rem 0; 
            border: 1px solid #eee; 
            border-radius: 8px;
            background: <?php echo $index % 2 === 0 ? '#f8f9fa' : 'white'; ?>;
        ">
            <div style="display: flex; justify-content: between; align-items: flex-start; margin-bottom: 1rem;">
                <div>
                    <strong><?php echo $respuesta['autor_nombre'] . ' ' . $respuesta['autor_apellido'] . ' (@' . $respuesta['autor_usuario'] . ')'; ?></strong>
                    <div style="color: #666; font-size: 0.8rem;">
                        <?php echo $respuesta['autor_comuna']; ?>
                        • 📅 <?php echo date('d/m/Y H:i', strtotime($respuesta['fecha_respuesta'])); ?>
                    </div>
                </div>
                
                <div style="font-size: 0.8rem; color: #666;">
                    #<?php echo $index + 1; ?>
                </div>
            </div>
            
            <div style="line-height: 1.6; white-space: pre-wrap;">
                <?php echo nl2br(htmlspecialchars($respuesta['contenido'])); ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Formulario de respuesta -->
<?php if (isset($_SESSION['usuario_id'])): ?>
<div class="card" id="responder">
    <h3>✍️ Escribe tu respuesta</h3>
    
    <?php if ($hilo['es_cerrado']): ?>
    <div style="background: #ffebee; padding: 1rem; border-radius: 5px; border-left: 4px solid #f44336;">
        <p>❌ Este hilo está cerrado y no acepta nuevas respuestas.</p>
    </div>
    <?php else: ?>
    <form method="POST" action="?pagina=responder_hilo">
        <input type="hidden" name="hilo_id" value="<?php echo $hilo_id; ?>">
        
        <div class="form-group">
            <textarea id="contenido" name="contenido" class="form-control" 
                      rows="6" placeholder="Escribe tu respuesta..." required></textarea>
        </div>
        
        <button type="submit" class="btn">📤 Publicar Respuesta</button>
    </form>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="card">
    <div style="text-align: center; padding: 1.5rem;">
        <p>🔐 Debes <a href="?pagina=login">iniciar sesión</a> para responder en este hilo.</p>
    </div>
</div>
<?php endif; ?>