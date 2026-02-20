<?php
// INICIAR SESIÓN AL PRINCIPIO
session_start();

require_once 'includes/config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    echo "<script>window.location.href = '?pagina=login';</script>";
    exit();
}

// Cargar funciones de notificaciones solo si la BD está disponible
if ($pdo && file_exists('includes/funciones_notificaciones.php')) {
    require_once 'includes/funciones_notificaciones.php';
}

$hilo_id = intval($_POST['hilo_id'] ?? 0);
$contenido = trim($_POST['contenido'] ?? '');

if (!$hilo_id || !$contenido) {
    $_SESSION['error'] = "Datos incompletos para responder.";
    echo "<script>window.location.href = '?pagina=ver_hilo&id=$hilo_id';</script>";
    exit();
}

if ($pdo) {
    // Verificar que el hilo existe y no está cerrado
    $hilo = $pdo->prepare("SELECT es_cerrado FROM hilos WHERE id = ?");
    $hilo->execute([$hilo_id]);
    $hilo = $hilo->fetch();
    
    if (!$hilo) {
        $_SESSION['error'] = "El hilo no existe.";
        echo "<script>window.location.href = '?pagina=foros';</script>";
        exit();
    }
    
    if ($hilo['es_cerrado']) {
        $_SESSION['error'] = "No puedes responder en un hilo cerrado.";
        echo "<script>window.location.href = '?pagina=ver_hilo&id=$hilo_id';</script>";
        exit();
    }
    
    // Insertar respuesta
    try {
        $stmt = $pdo->prepare("INSERT INTO respuestas (contenido, usuario_id, hilo_id) VALUES (?, ?, ?)");
        $stmt->execute([$contenido, $_SESSION['usuario_id'], $hilo_id]);
        
        $respuesta_id = $pdo->lastInsertId();
        
        // ACTUALIZACIÓN: Notificar al autor del hilo (solo si funciones están cargadas)
        if (function_exists('notificarRespuestaHilo')) {
            notificarRespuestaHilo($hilo_id, $respuesta_id, $_SESSION['usuario_id']);
        }
        
        // ACTUALIZACIÓN: Notificar menciones (solo si funciones están cargadas)
        if (function_exists('notificarMencion')) {
            notificarMencion($_SESSION['usuario_id'], $contenido, "?pagina=ver_hilo&id={$hilo_id}#respuesta-{$respuesta_id}");
        }
        
        // Actualizar fecha de actualización del hilo
        $pdo->prepare("UPDATE hilos SET fecha_actualizacion = NOW() WHERE id = ?")->execute([$hilo_id]);
        
        $_SESSION['exito'] = "✅ Respuesta publicada exitosamente.";
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "❌ Error al publicar la respuesta: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "❌ Base de datos no disponible.";
}

// Redirección con JavaScript para evitar problemas de headers
echo "<script>window.location.href = '?pagina=ver_hilo&id=$hilo_id';</script>";
exit();
?>