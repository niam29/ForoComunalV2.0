<?php
require_once 'includes/config.php';
require_once 'includes/funciones_notificaciones.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ?pagina=login');
    exit();
}

$accion = $_GET['accion'] ?? 'lista';
$mensaje_id = intval($_GET['ver'] ?? 0);
$destinatario_id = intval($_GET['para'] ?? 0);

// Procesar envío de mensaje
if ($_POST && isset($_POST['enviar_mensaje'])) {
    $destinatario_id = intval($_POST['destinatario_id']);
    $asunto = trim($_POST['asunto']);
    $contenido = trim($_POST['contenido']);
    
    if ($destinatario_id && $asunto && $contenido) {
        if (enviarMensajePrivado($_SESSION['usuario_id'], $destinatario_id, $asunto, $contenido)) {
            $_SESSION['exito'] = "✅ Mensaje enviado correctamente";
        } else {
            $_SESSION['error'] = "❌ Error al enviar el mensaje";
        }
    } else {
        $_SESSION['error'] = "❌ Completa todos los campos";
    }
    
    header('Location: ?pagina=mensajes');
    exit();
}

// Marcar mensaje como leído
if ($mensaje_id > 0) {
    $stmt = $pdo->prepare("
        UPDATE mensajes_privados 
        SET leido = 1 
        WHERE id = ? AND destinatario_id = ?
    ");
    $stmt->execute([$mensaje_id, $_SESSION['usuario_id']]);
}
?>

<div class="card fade-in">
    <div style="display: flex; justify-content: between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2>📩 Mensajes Privados</h2>
            <p>Comunícate de forma privada con otros usuarios</p>
        </div>
        
        <div>
            <a href="?pagina=mensajes&accion=nuevo" class="btn btn-success">
                ✉️ Nuevo Mensaje
            </a>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['exito'])): ?>
<div class="card" style="background: #e8f5e8; border-left: 4px solid #4CAF50;">
    <p><?php echo $_SESSION['exito']; unset($_SESSION['exito']); ?></p>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="card" style="background: #ffebee; border-left: 4px solid #f44336;">
    <p><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
</div>
<?php endif; ?>

<?php if ($accion === 'nuevo' || $accion === 'responder'): ?>
<!-- Formulario de nuevo mensaje -->
<div class="card fade-in">
    <h3>✉️ <?php echo $accion === 'responder' ? 'Responder Mensaje' : 'Nuevo Mensaje'; ?></h3>
    
    <form method="POST">
        <input type="hidden" name="enviar_mensaje" value="1">
        
        <div class="form-group">
            <label for="destinatario">👤 Para:</label>
            <select id="destinatario" name="destinatario_id" class="form-control" required>
                <option value="">Selecciona un usuario</option>
                <?php
                $usuarios = $pdo->query("
                    SELECT id, usuario, nombre, apellido 
                    FROM usuarios 
                    WHERE activo = 1 AND id != {$_SESSION['usuario_id']} 
                    ORDER BY usuario
                ")->fetchAll();
                
                foreach ($usuarios as $usuario): 
                ?>
                <option value="<?php echo $usuario['id']; ?>" 
                        <?php echo $destinatario_id == $usuario['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($usuario['usuario'], ENT_QUOTES, 'UTF-8'); ?> 
                    (<?php echo htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8'); ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="asunto">📋 Asunto:</label>
            <input type="text" id="asunto" name="asunto" class="form-control" 
                   value="<?php echo isset($_POST['asunto']) ? htmlspecialchars($_POST['asunto']) : ''; ?>" 
                   required maxlength="200">
        </div>
        
        <div class="form-group">
            <label for="contenido">💬 Mensaje:</label>
            <textarea id="contenido" name="contenido" class="form-control" 
                      rows="8" required placeholder="Escribe tu mensaje aquí..."><?php echo isset($_POST['contenido']) ? htmlspecialchars($_POST['contenido']) : ''; ?></textarea>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-success">📤 Enviar Mensaje</button>
            <a href="?pagina=mensajes" class="btn btn-secondary">↩️ Cancelar</a>
        </div>
    </form>
</div>

<?php elseif ($mensaje_id > 0): ?>
<!-- Ver mensaje individual -->
<?php
$stmt = $pdo->prepare("
    SELECT m.*, 
           r.usuario as remitente_usuario,
           r.nombre as remitente_nombre,
           d.usuario as destinatario_usuario,
           d.nombre as destinatario_nombre
    FROM mensajes_privados m
    JOIN usuarios r ON m.remitente_id = r.id
    JOIN usuarios d ON m.destinatario_id = d.id
    WHERE m.id = ? AND (m.remitente_id = ? OR m.destinatario_id = ?)
");
$stmt->execute([$mensaje_id, $_SESSION['usuario_id'], $_SESSION['usuario_id']]);
$mensaje = $stmt->fetch();

if ($mensaje):
?>
<div class="card fade-in">
    <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 1rem;">
        <h3>📩 <?php echo htmlspecialchars($mensaje['asunto'], ENT_QUOTES, 'UTF-8'); ?></h3>
        <a href="?pagina=mensajes&accion=nuevo&para=<?php echo $mensaje['remitente_id']; ?>" class="btn btn-success">
            ↩️ Responder
        </a>
    </div>
    
    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
        <div style="display: flex; flex-wrap: wrap; gap: 2rem; margin-bottom: 1rem; font-size: 0.9rem;">
            <div>
                <strong>👤 De:</strong> 
                <?php echo htmlspecialchars($mensaje['remitente_nombre'], ENT_QUOTES, 'UTF-8'); ?>
                (@<?php echo htmlspecialchars($mensaje['remitente_usuario'], ENT_QUOTES, 'UTF-8'); ?>)
            </div>
            <div>
                <strong>👤 Para:</strong> 
                <?php echo htmlspecialchars($mensaje['destinatario_nombre'], ENT_QUOTES, 'UTF-8'); ?>
                (@<?php echo htmlspecialchars($mensaje['destinatario_usuario'], ENT_QUOTES, 'UTF-8'); ?>)
            </div>
            <div>
                <strong>📅 Fecha:</strong> 
                <?php echo date('d/m/Y H:i', strtotime($mensaje['fecha_envio'])); ?>
            </div>
        </div>
        
        <div style="line-height: 1.6; white-space: pre-wrap;">
            <?php echo nl2br(htmlspecialchars($mensaje['contenido'], ENT_QUOTES, 'UTF-8')); ?>
        </div>
    </div>
    
    <div style="text-align: center;">
        <a href="?pagina=mensajes" class="btn">📋 Volver a mensajes</a>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div style="text-align: center; padding: 2rem;">
        <h3>❌ Mensaje no encontrado</h3>
        <p>El mensaje que buscas no existe o no tienes permiso para verlo.</p>
        <a href="?pagina=mensajes" class="btn">📋 Volver a mensajes</a>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<!-- Lista de mensajes -->
<?php
// Obtener mensajes recibidos
$mensajes_recibidos = $pdo->prepare("
    SELECT m.*, 
           r.usuario as remitente_usuario,
           r.nombre as remitente_nombre,
           DATE_FORMAT(m.fecha_envio, '%d/%m/%Y %H:%i') as fecha_formateada
    FROM mensajes_privados m
    JOIN usuarios r ON m.remitente_id = r.id
    WHERE m.destinatario_id = ?
    ORDER BY m.leido ASC, m.fecha_envio DESC
    LIMIT 20
");
$mensajes_recibidos->execute([$_SESSION['usuario_id']]);
$mensajes_recibidos = $mensajes_recibidos->fetchAll();

// Obtener mensajes enviados
$mensajes_enviados = $pdo->prepare("
    SELECT m.*, 
           d.usuario as destinatario_usuario,
           d.nombre as destinatario_nombre,
           DATE_FORMAT(m.fecha_envio, '%d/%m/%Y %H:%i') as fecha_formateada
    FROM mensajes_privados m
    JOIN usuarios d ON m.destinatario_id = d.id
    WHERE m.remitente_id = ?
    ORDER BY m.fecha_envio DESC
    LIMIT 20
");
$mensajes_enviados->execute([$_SESSION['usuario_id']]);
$mensajes_enviados = $mensajes_enviados->fetchAll();
?>

<!-- Mensajes Recibidos -->
<div class="card fade-in">
    <h3>📥 Mensajes Recibidos</h3>
    
    <?php if (empty($mensajes_recibidos)): ?>
    <div style="text-align: center; padding: 2rem; color: #666;">
        <p>📭 No tienes mensajes recibidos</p>
    </div>
    <?php else: ?>
        <?php foreach ($mensajes_recibidos as $mensaje): ?>
        <div class="mensaje-item <?php echo $mensaje['leido'] ? 'mensaje-leido' : 'mensaje-no-leido'; ?>">
            <div style="display: flex; gap: 1rem; align-items: center; padding: 1rem; border-bottom: 1px solid #eee;">
                <div style="font-size: 1.2rem;">
                    <?php echo $mensaje['leido'] ? '📭' : '📨'; ?>
                </div>
                
                <div style="flex: 1;">
                    <h4 style="margin: 0 0 0.2rem 0;">
                        <a href="?pagina=mensajes&ver=<?php echo $mensaje['id']; ?>" 
                           style="text-decoration: none; color: inherit;">
                            <?php echo htmlspecialchars($mensaje['asunto'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </h4>
                    <div style="font-size: 0.9rem; color: #666;">
                        De: <strong><?php echo htmlspecialchars($mensaje['remitente_nombre'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        (@<?php echo htmlspecialchars($mensaje['remitente_usuario'], ENT_QUOTES, 'UTF-8'); ?>)
                    </div>
                </div>
                
                <div style="text-align: right; min-width: 120px;">
                    <div style="font-size: 0.8rem; color: #888;">
                        <?php echo $mensaje['fecha_formateada']; ?>
                    </div>
                    <?php if (!$mensaje['leido']): ?>
                    <span style="background: #FF9800; color: white; padding: 0.2rem 0.4rem; border-radius: 10px; font-size: 0.7rem;">
                        NUEVO
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Mensajes Enviados -->
<div class="card fade-in">
    <h3>📤 Mensajes Enviados</h3>
    
    <?php if (empty($mensajes_enviados)): ?>
    <div style="text-align: center; padding: 2rem; color: #666;">
        <p>📤 No has enviado mensajes</p>
    </div>
    <?php else: ?>
        <?php foreach ($mensajes_enviados as $mensaje): ?>
        <div class="mensaje-item">
            <div style="display: flex; gap: 1rem; align-items: center; padding: 1rem; border-bottom: 1px solid #eee;">
                <div style="font-size: 1.2rem;">📤</div>
                
                <div style="flex: 1;">
                    <h4 style="margin: 0 0 0.2rem 0;">
                        <a href="?pagina=mensajes&ver=<?php echo $mensaje['id']; ?>" 
                           style="text-decoration: none; color: inherit;">
                            <?php echo htmlspecialchars($mensaje['asunto'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </h4>
                    <div style="font-size: 0.9rem; color: #666;">
                        Para: <strong><?php echo htmlspecialchars($mensaje['destinatario_nombre'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        (@<?php echo htmlspecialchars($mensaje['destinatario_usuario'], ENT_QUOTES, 'UTF-8'); ?>)
                    </div>
                </div>
                
                <div style="text-align: right; min-width: 120px;">
                    <div style="font-size: 0.8rem; color: #888;">
                        <?php echo $mensaje['fecha_formateada']; ?>
                    </div>
                    <?php if (!$mensaje['leido']): ?>
                    <span style="background: #666; color: white; padding: 0.2rem 0.4rem; border-radius: 10px; font-size: 0.7rem;">
                        NO LEÍDO
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php endif; ?>