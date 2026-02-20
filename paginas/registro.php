<?php
require_once 'includes/config.php';

$mensaje = '';

if ($_POST) {
    if ($pdo) {
        // Con base de datos - nuevos campos
        $ci = trim($_POST['ci']);
        $usuario = trim($_POST['usuario']);
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $email = trim($_POST['email']);
        $comuna = trim($_POST['comuna']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $habilidades = trim($_POST['habilidades']);
        
        // Validaciones
        $errores = [];
        
        if (empty($ci) || empty($usuario) || empty($nombre) || empty($apellido) || empty($email) || empty($comuna) || empty($password)) {
            $errores[] = "Todos los campos obligatorios deben ser completados.";
        }
        
        if ($password !== $confirm_password) {
            $errores[] = "Las contraseñas no coinciden.";
        }
        
        if (strlen($password) < 6) {
            $errores[] = "La contraseña debe tener al menos 6 caracteres.";
        }
        
        // Verificar si CI, usuario o email ya existen
        if (empty($errores)) {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE ci = ? OR usuario = ? OR email = ?");
            $stmt->execute([$ci, $usuario, $email]);
            
            if ($stmt->rowCount() > 0) {
                $errores[] = "La cédula, usuario o correo electrónico ya están registrados.";
            }
        }
        
        if (empty($errores)) {
            // Hash de la contraseña
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO usuarios (ci, usuario, nombre, apellido, email, comuna, password, habilidades) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$ci, $usuario, $nombre, $apellido, $email, $comuna, $password_hash, $habilidades])) {
                $mensaje = "✅ ¡Registro exitoso! Ahora puedes iniciar sesión.";
                // Limpiar formulario
                $_POST = [];
            } else {
                $mensaje = "❌ Error en el registro.";
            }
        } else {
            $mensaje = "❌ " . implode("<br>", $errores);
        }
    } else {
        // Sin base de datos
        $mensaje = "✅ ¡Registro simulado! (Base de datos en configuración)";
    }
}
?>

<div class="card">
    <h2>👤 Registro en el Foro Comunal</h2>
    
    <?php if (!$pdo): ?>
    <div style="background: #fff3e0; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; border-left: 4px solid #ff9800;">
        <strong>⚠️ Modo demostración</strong>
        <p>Los registros se guardarán cuando la base de datos esté disponible.</p>
    </div>
    <?php endif; ?>
    
    <?php if ($mensaje): ?>
        <div style="background: <?php echo strpos($mensaje, '✅') !== false ? '#e8f5e8' : '#ffebee'; ?>; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="ci">Cédula de Identidad *</label>
                <input type="text" id="ci" name="ci" class="form-control" 
                       value="<?php echo isset($_POST['ci']) ? htmlspecialchars($_POST['ci']) : ''; ?>" 
                       required pattern="[0-9]+" title="Solo números">
            </div>
            
            <div class="form-group">
                <label for="usuario">Nombre de Usuario *</label>
                <input type="text" id="usuario" name="usuario" class="form-control" 
                       value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>" 
                       required pattern="[a-zA-Z0-9]+" title="Solo letras y números">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="nombre">Nombre *</label>
                <input type="text" id="nombre" name="nombre" class="form-control" 
                       value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="apellido">Apellido *</label>
                <input type="text" id="apellido" name="apellido" class="form-control" 
                       value="<?php echo isset($_POST['apellido']) ? htmlspecialchars($_POST['apellido']) : ''; ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="email">Correo Electrónico *</label>
            <input type="email" id="email" name="email" class="form-control" 
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="comuna">Comuna o Territorio *</label>
            <input type="text" id="comuna" name="comuna" class="form-control" 
                   value="<?php echo isset($_POST['comuna']) ? htmlspecialchars($_POST['comuna']) : ''; ?>" required>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="password">Contraseña *</label>
                <input type="password" id="password" name="password" class="form-control" required minlength="6">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmar Contraseña *</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>
        </div>
        
        <div class="form-group">
            <label for="habilidades">Habilidades o Conocimientos</label>
            <textarea id="habilidades" name="habilidades" class="form-control" 
                      placeholder="Ej: Programación, Electricidad, Comunicación, Agricultura..."><?php echo isset($_POST['habilidades']) ? htmlspecialchars($_POST['habilidades']) : ''; ?></textarea>
        </div>
        
        <button type="submit" class="btn">✅ Registrarse en el Foro</button>
    </form>
    
    <div style="text-align: center; margin-top: 1rem;">
        <p>¿Ya tienes cuenta? <a href="?pagina=login">Inicia sesión aquí</a></p>
    </div>
</div>