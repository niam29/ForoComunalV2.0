<?php
/**
 * Funciones para el sistema de notificaciones
 */

/**
 * Crear una nueva notificación
 */
function crearNotificacion($usuario_id, $tipo, $titulo, $contenido = '', $enlace = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notificaciones (usuario_id, tipo, titulo, contenido, enlace) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$usuario_id, $tipo, $titulo, $contenido, $enlace]);
    } catch (PDOException $e) {
        error_log("Error creando notificación: " . $e->getMessage());
        return false;
    }
}

/**
 * Notificar cuando responden a un hilo
 */
function notificarRespuestaHilo($hilo_id, $respuesta_id, $usuario_responde_id) {
    global $pdo;
    
    // Obtener información del hilo y su autor
    $stmt = $pdo->prepare("
        SELECT h.usuario_id as autor_id, h.titulo, u.usuario as autor_usuario
        FROM hilos h 
        JOIN usuarios u ON h.usuario_id = u.id 
        WHERE h.id = ?
    ");
    $stmt->execute([$hilo_id]);
    $hilo = $stmt->fetch();
    
    if ($hilo && $hilo['autor_id'] != $usuario_responde_id) {
        $titulo = "💬 Nueva respuesta en tu hilo";
        $contenido = "El usuario @{$usuario_responde_id} ha respondido en tu hilo: \"{$hilo['titulo']}\"";
        $enlace = "?pagina=ver_hilo&id={$hilo_id}#respuesta-{$respuesta_id}";
        
        return crearNotificacion($hilo['autor_id'], 'respuesta_hilo', $titulo, $contenido, $enlace);
    }
    
    return false;
}

/**
 * Notificar cuando mencionan a un usuario
 */
function notificarMencion($usuario_mentor_id, $contenido, $enlace) {
    // Buscar menciones (@usuario) en el contenido
    preg_match_all('/@([a-zA-Z0-9_]+)/', $contenido, $matches);
    
    if (empty($matches[1])) return false;
    
    $usuarios_mentions = array_unique($matches[1]);
    $notificaciones_creadas = 0;
    
    foreach ($usuarios_mentions as $username) {
        // Obtener ID del usuario mencionado
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? AND activo = 1");
        $stmt->execute([$username]);
        $usuario = $stmt->fetch();
        
        if ($usuario && $usuario['id'] != $usuario_mentor_id) {
            $titulo = "👤 Te han mencionado";
            $contenido_notif = "El usuario @{$usuario_mentor_id} te ha mencionado en un mensaje";
            
            if (crearNotificacion($usuario['id'], 'mencion', $titulo, $contenido_notif, $enlace)) {
                $notificaciones_creadas++;
            }
        }
    }
    
    return $notificaciones_creadas > 0;
}

/**
 * Enviar mensaje privado
 */
function enviarMensajePrivado($remitente_id, $destinatario_id, $asunto, $contenido) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO mensajes_privados (remitente_id, destinatario_id, asunto, contenido) 
            VALUES (?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$remitente_id, $destinatario_id, $asunto, $contenido])) {
            $mensaje_id = $pdo->lastInsertId();
            
            // Crear notificación para el destinatario
            $remitente_stmt = $pdo->prepare("SELECT usuario FROM usuarios WHERE id = ?");
            $remitente_stmt->execute([$remitente_id]);
            $remitente = $remitente_stmt->fetch();
            
            $titulo = "📩 Nuevo mensaje privado";
            $contenido_notif = "Tienes un nuevo mensaje de @{$remitente['usuario']}: {$asunto}";
            $enlace = "?pagina=mensajes&ver={$mensaje_id}";
            
            crearNotificacion($destinatario_id, 'mensaje_privado', $titulo, $contenido_notif, $enlace);
            
            return $mensaje_id;
        }
    } catch (PDOException $e) {
        error_log("Error enviando mensaje: " . $e->getMessage());
    }
    
    return false;
}

/**
 * Obtener notificaciones no leídas de un usuario
 */
function obtenerNotificacionesNoLeidas($usuario_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM notificaciones 
        WHERE usuario_id = ? AND leida = 0
    ");
    $stmt->execute([$usuario_id]);
    return $stmt->fetch()['total'];
}

/**
 * Obtener notificaciones recientes
 */
function obtenerNotificacionesRecientes($usuario_id, $limite = 10) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT *, DATE_FORMAT(fecha_creacion, '%d/%m/%Y %H:%i') as fecha_formateada
        FROM notificaciones 
        WHERE usuario_id = ? 
        ORDER BY leida ASC, fecha_creacion DESC 
        LIMIT ?
    ");
    $stmt->execute([$usuario_id, $limite]);
    return $stmt->fetchAll();
}

/**
 * Marcar notificación como leída
 */
function marcarNotificacionLeida($notificacion_id, $usuario_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE notificaciones 
        SET leida = 1 
        WHERE id = ? AND usuario_id = ?
    ");
    return $stmt->execute([$notificacion_id, $usuario_id]);
}

/**
 * Marcar todas las notificaciones como leídas
 */
function marcarTodasLeidas($usuario_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE notificaciones 
        SET leida = 1 
        WHERE usuario_id = ? AND leida = 0
    ");
    return $stmt->execute([$usuario_id]);
}
?>