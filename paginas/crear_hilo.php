<?php
require_once 'includes/config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ?pagina=login');
    exit();
}

$foro_id = intval($_GET['foro_id'] ?? 0);
$mensaje = '';

// Obtener información del foro si se especificó
if ($foro_id > 0 && $pdo) {
    $foro = $pdo->prepare("SELECT * FROM foros WHERE id = ?");
    $foro->execute([$foro_id]);
    $foro = $foro->fetch();
}

// Procesar formulario
if ($_POST) {
    $titulo = trim($_POST['titulo']);
    $contenido = trim($_POST['contenido']);
    $foro_id = intval($_POST['foro_id']);
    $es_importante = isset($_POST['es_importante']) ? 1 : 0;
    
    if ($titulo && $contenido && $foro_id) {
        try {
            $stmt = $pdo->prepare("INSERT INTO hilos (titulo, contenido, usuario_id, foro_id, es_importante) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$titulo, $contenido, $_SESSION['usuario_id'], $foro_id, $es_importante]);
            
            $hilo_id = $pdo->lastInsertId();
            $mensaje = "✅ ¡Hilo creado exitosamente!";
            
            // Redirigir al hilo creado
            echo "<script>setTimeout(() => window.location.href = '?pagina=ver_hilo&id=$hilo_id', 1500);</script>";
            
        } catch (PDOException $e) {
            $mensaje = "❌ Error al crear el hilo: " . $e->getMessage();
        }
    } else {
        $mensaje = "❌ Completa todos los campos obligatorios.";
    }
}
?>

<div class="card">
    <h2>📝 Crear Nuevo Hilo</h2>
    
    <?php if (isset($foro)): ?>
    <div style="background: #e3f2fd; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; border-left: 4px solid #2196F3;">
        <strong>Foro:</strong> <?php echo $foro['nombre']; ?>
    </div>
    <?php endif; ?>
    
    <?php if ($mensaje): ?>
        <div style="background: <?php echo strpos($mensaje, '✅') !== false ? '#e8f5e8' : '#ffebee'; ?>; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <?php if (!isset($foro)): ?>
        <div class="form-group">
            <label for="foro_id">Seleccionar Foro *</label>
            <select id="foro_id" name="foro_id" class="form-control" required>
                <option value="">-- Selecciona un foro --</option>
                <?php
                if ($pdo) {
                    $foros = $pdo->query("SELECT * FROM foros ORDER BY orden")->fetchAll();
                    foreach ($foros as $f) {
                        echo "<option value='{$f['id']}'>{$f['nombre']}</option>";
                    }
                }
                ?>
            </select>
        </div>
        <?php else: ?>
        <input type="hidden" name="foro_id" value="<?php echo $foro_id; ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label for="titulo">Título del Hilo *</label>
            <input type="text" id="titulo" name="titulo" class="form-control" 
                   value="<?php echo isset($_POST['titulo']) ? htmlspecialchars($_POST['titulo']) : ''; ?>" 
                   placeholder="Escribe un título claro y descriptivo" required maxlength="200">
        </div>
        
        <div class="form-group">
            <label for="contenido">Contenido *</label>
            <textarea id="contenido" name="contenido" class="form-control" 
                      rows="10" placeholder="Describe tu pregunta, propuesta o debate..." required><?php echo isset($_POST['contenido']) ? htmlspecialchars($_POST['contenido']) : ''; ?></textarea>
            <small>Puedes usar formato básico: **negrita**, _cursiva_, saltos de línea</small>
        </div>
        
        <?php if ($_SESSION['usuario_rol'] == 'coordinador'): ?>
        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 0.5rem;">
                <input type="checkbox" name="es_importante" value="1" 
                       <?php echo isset($_POST['es_importante']) ? 'checked' : ''; ?>>
                📌 Marcar como hilo importante
            </label>
        </div>
        <?php endif; ?>
        
        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" class="btn">📤 Publicar Hilo</button>
            <a href="javascript:history.back()" class="btn" style="background: #666;">↩️ Cancelar</a>
        </div>
    </form>
</div>