<?php
require_once 'includes/config.php';

$mensaje = '';

if ($_POST) {
    if ($pdo) {
        $login = trim($_POST['login']); // Puede ser usuario, email o CI
        $password = $_POST['password'];
        
        if ($login && $password) {
            // Buscar usuario por usuario, email o CI
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE (usuario = ? OR email = ? OR ci = ?) AND activo = 1");
            $stmt->execute([$login, $login, $login]);
            $usuario = $stmt->fetch();
            
            if ($usuario && password_verify($password, $usuario['password'])) {
                // Login exitoso
                session_start();
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'] . ' ' . $usuario['apellido'];
                $_SESSION['usuario_rol'] = $usuario['rol'];
                $_SESSION['usuario'] = $usuario['usuario'];
                
                // Actualizar último login
                $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?")->execute([$usuario['id']]);
                
                $mensaje = "✅ ¡Login exitoso! Redirigiendo...";
                echo "<script>setTimeout(() => window.location.href = '?pagina=foros', 1000);</script>";
                
            } else {
                $mensaje = "❌ Credenciales incorrectas o usuario inactivo.";
            }
        } else {
            $mensaje = "❌ Completa todos los campos.";
        }
    } else {
        $mensaje = "❌ Sistema de login no disponible temporalmente.";
    }
}
?>

<div class="card">
    <h2>🔐 Iniciar Sesión</h2>
    
    <?php if ($mensaje): ?>
        <div style="background: <?php echo strpos($mensaje, '✅') !== false ? '#e8f5e8' : '#ffebee'; ?>; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="login">Usuario, Email o C.I.</label>
            <input type="text" id="login" name="login" class="form-control" 
                   value="<?php echo isset($_POST['login']) ? htmlspecialchars($_POST['login']) : ''; ?>" required>
            <small>Puedes usar tu usuario, correo o cédula</small>
        </div>
        
        <div class="form-group">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>
        
        <button type="submit" class="btn">🔐 Iniciar Sesión</button>
    </form>
    
    <div style="text-align: center; margin-top: 1rem;">
        <p>¿No tienes cuenta? <a href="?pagina=registro">Regístrate aquí</a></p>
    </div>
</div>
