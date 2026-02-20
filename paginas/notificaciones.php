<?php
require_once 'includes/config.php';
require_once 'includes/funciones_notificaciones.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ?pagina=login');
    exit();
}

// Procesar acciones
if (isset($_GET['accion'])) {
    switch ($_GET['accion']) {
        case 'marcar_leida':
            if (isset($_GET['id'])) {
                marcarNotificacionLeida($_GET['id'], $_SESSION['usuario_id']);
            }
            break;
            
        case 'marcar_todas_leidas':
            marcarTodasLeidas($_SESSION['usuario_id']);
            $_SESSION['exito'] = "✅ Todas las notificaciones marcadas como leídas";
            break;
            
        case 'eliminar':
            if (isset($_GET['id'])) {
                $stmt = $pdo->prepare("DELETE FROM notificaciones WHERE id = ? AND usuario_id = ?");
                $stmt->execute([$_GET['id'], $_SESSION['usuario_id']]);
            }
            break;
    }
    
    // Redirigir para evitar reenvío de formulario
    header('Location: ?pagina=notificaciones');
    exit();
}

// Obtener notificaciones
$notificaciones = obtenerNotificacionesRecientes($_SESSION['usuario_id'], 20);
$total_no_leidas = obtenerNotificacionesNoLeidas($_SESSION['usuario_id']);
?>

<div class="card fade-in">
    <div style="display: flex; justify-content: between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2>🔔 Mis Notificaciones</h2>
            <p style="margin: 0; color: #666;">
                <?php if ($total_no_leidas > 0): ?>
                    Tienes <strong><?php echo $total_no_leidas; ?></strong> notificaciones no leídas
                <?php else: ?>
                    No tienes notificaciones no leídas
                <?php endif; ?>
            </p>
        </div>
        
        <?php if ($total_no_leidas > 0): ?>
        <div>
            <a href="?pagina=notificaciones&accion=marcar_todas_leidas" class="btn btn-success">
                ✅ Marcar todas como leídas
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($notificaciones)): ?>
<div class="card fade-in">
    <div style="text-align: center; padding: 3rem;">
        <h3>📭 No hay notificaciones</h3>
        <p>Cuando tengas nuevas notificaciones, aparecerán aquí.</p>
        <a href="?pagina=foros" class="btn btn-success" style="margin-top: 1rem;">
            📋 Explorar foros
        </a>
    </div>
</div>
<?php else: ?>
    <?php foreach ($notificaciones as $notif): ?>
    <div class="card notificacion-item fade-in <?php echo $notif['leida'] ? 'notificacion-leida' : 'notificacion-no-leida'; ?>">
        <div style="display: flex; gap: 1rem; align-items: flex-start;">
            <!-- Icono según tipo -->
            <div style="font-size: 1.5rem; min-width: 40px; text-align: center;">
                <?php 
                switch($notif['tipo']) {
                    case 'respuesta_hilo': echo '💬'; break;
                    case 'mencion': echo '👤'; break;
                    case 'mensaje_privado': echo '📩'; break;
                    case 'moderacion': echo '🛡️'; break;
                    case 'sistema': echo '⚙️'; break;
                    default: echo '🔔';
                }
                ?>
            </div>
            
            <!-- Contenido de la notificación -->
            <div style="flex: 1;">
                <div style="display: flex; justify-content: between; align-items: flex-start; flex-wrap: wrap; gap: 0.5rem;">
                    <h4 style="margin: 0 0 0.5rem 0; flex: 1;">
                        <?php if ($notif['enlace']): ?>
                            <a href="<?php echo $notif['enlace']; ?>" 
                               style="text-decoration: none; color: inherit;"
                               onclick="marcarLeida(<?php echo $notif['id']; ?>)">
                                <?php echo htmlspecialchars($notif['titulo'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        <?php else: ?>
                            <?php echo htmlspecialchars($notif['titulo'], ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                    </h4>
                    
                    <div style="display: flex; gap: 0.5rem;">
                        <?php if (!$notif['leida']): ?>
                        <a href="?pagina=notificaciones&accion=marcar_leida&id=<?php echo $notif['id']; ?>" 
                           class="btn-accion" 
                           style="background: #4CAF50; padding: 0.3rem 0.6rem; font-size: 0.7rem;">
                            ✅ Leída
                        </a>
                        <?php endif; ?>
                        <a href="?pagina=notificaciones&accion=eliminar&id=<?php echo $notif['id']; ?>" 
                           class="btn-accion" 
                           style="background: #f44336; padding: 0.3rem 0.6rem; font-size: 0.7rem;"
                           onclick="return confirm('¿Eliminar esta notificación?')">
                            🗑️
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($notif['contenido'])): ?>
                <p style="margin: 0 0 0.5rem 0; color: #666;">
                    <?php echo htmlspecialchars($notif['contenido'], ENT_QUOTES, 'UTF-8'); ?>
                </p>
                <?php endif; ?>
                
                <div style="font-size: 0.8rem; color: #888;">
                    📅 <?php echo $notif['fecha_formateada']; ?>
                    <?php if (!$notif['leida']): ?>
                    <span style="background: #FF9800; color: white; padding: 0.2rem 0.4rem; border-radius: 10px; font-size: 0.7rem; margin-left: 0.5rem;">
                        NUEVO
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
function marcarLeida(notificacionId) {
    // Marcar como leída via AJAX (mejora futura)
    fetch(`?pagina=notificaciones&accion=marcar_leida&id=${notificacionId}`, {
        method: 'GET'
    });
}
</script>