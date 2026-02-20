<?php
// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ?pagina=login');
    exit();
}

require_once 'includes/config.php';

// Obtener datos del usuario
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch();
?>

<div class="card">
    <h2>👤 Perfil de Usuario</h2>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <div>
            <h3>Información Personal</h3>
            <p><strong>Cédula:</strong> <?php echo $usuario['ci']; ?></p>
            <p><strong>Usuario:</strong> <?php echo $usuario['usuario']; ?></p>
            <p><strong>Nombre:</strong> <?php echo $usuario['nombre'] . ' ' . $usuario['apellido']; ?></p>
            <p><strong>Email:</strong> <?php echo $usuario['email']; ?></p>
            <p><strong>Comuna:</strong> <?php echo $usuario['comuna']; ?></p>
            <p><strong>Rol:</strong> <?php echo $usuario['rol']; ?></p>
        </div>
        
        <div>
            <h3>Habilidades</h3>
            <p><?php echo $usuario['habilidades'] ?: 'Sin habilidades registradas'; ?></p>
            
            <h3>Estadísticas</h3>
            <p><strong>Miembro desde:</strong> <?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></p>
            <?php if ($usuario['ultimo_login']): ?>
            <p><strong>Último acceso:</strong> <?php echo date('d/m/Y H:i', strtotime($usuario['ultimo_login'])); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <div style="margin-top: 2rem; text-align: center;">
        <a href="?pagina=foros" class="btn">📋 Ir a Foros</a>
    </div>
</div>