<?php
require_once 'includes/config.php';

// Verificar si el usuario es coordinador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'coordinador') {
    echo "<div class='card'><p>🚫 Acceso restringido para coordinadores</p></div>";
    return;
}

$foro_id = intval($_GET['id'] ?? 0);

if (!$foro_id) {
    echo "<div class='card'><p>❌ Foro no especificado</p></div>";
    return;
}

// Obtener datos del foro
$foro = $pdo->prepare("SELECT * FROM foros WHERE id = ?");
$foro->execute([$foro_id]);
$foro = $foro->fetch();

if (!$foro) {
    echo "<div class='card'><p>❌ Foro no encontrado</p></div>";
    return;
}

$mensaje = '';

// Procesar formulario de edición
if ($_POST) {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $responsable = trim($_POST['responsable']);
    $icono = $_POST['icono'];
    $color = $_POST['color'];
    $permisos = $_POST['permisos'];
    $orden = intval($_POST['orden']);
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validaciones
    if (empty($nombre) || empty($descripcion) || empty($responsable)) {
        $mensaje = "❌ Nombre, descripción y responsable son obligatorios.";
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE foros 
                SET nombre = ?, descripcion = ?, responsable = ?, icono = ?, 
                    color = ?, permisos = ?, orden = ?, activo = ?
                WHERE id = ?
            ");
            
            $stmt->execute([$nombre, $descripcion, $responsable, $icono, $color, $permisos, $orden, $activo, $foro_id]);
            
            $mensaje = "✅ ¡Foro actualizado exitosamente!";
            
            // Actualizar datos del foro en la variable
            $foro = array_merge($foro, [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'responsable' => $responsable,
                'icono' => $icono,
                'color' => $color,
                'permisos' => $permisos,
                'orden' => $orden,
                'activo' => $activo
            ]);
            
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $mensaje = "❌ Ya existe un foro con ese nombre.";
            } else {
                $mensaje = "❌ Error al actualizar el foro: " . $e->getMessage();
            }
        }
    }
}

// Obtener estadísticas del foro
$estadisticas = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT h.id) as total_hilos,
        COUNT(DISTINCT r.id) as total_respuestas,
        MAX(h.fecha_creacion) as ultimo_hilo
    FROM foros f 
    LEFT JOIN hilos h ON f.id = h.foro_id 
    LEFT JOIN respuestas r ON h.id = r.hilo_id 
    WHERE f.id = ?
    GROUP BY f.id
");
$estadisticas->execute([$foro_id]);
$estadisticas = $estadisticas->fetch();
?>

<div class="card">
    <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 1rem;">
        <div>
            <h2>✏️ Editar Foro</h2>
            <p>Modifica la configuración de "<?php echo $foro['nombre']; ?>"</p>
        </div>
        <a href="?pagina=gestion_foros" class="btn" style="background: #666;">← Volver a Gestión</a>
    </div>
    
    <!-- Estadísticas del foro -->
    <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
        <h4 style="margin: 0 0 0.5rem 0;">📊 Estadísticas del Foro</h4>
        <div style="display: flex; gap: 2rem; font-size: 0.9rem;">
            <div><strong>📝 Hilos:</strong> <?php echo $estadisticas['total_hilos'] ?? 0; ?></div>
            <div><strong>💬 Respuestas:</strong> <?php echo $estadisticas['total_respuestas'] ?? 0; ?></div>
            <?php if ($estadisticas['ultimo_hilo']): ?>
            <div><strong>🕒 Último hilo:</strong> <?php echo date('d/m/Y H:i', strtotime($estadisticas['ultimo_hilo'])); ?></div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($mensaje): ?>
        <div style="background: <?php echo strpos($mensaje, '✅') !== false ? '#e8f5e8' : '#ffebee'; ?>; 
                    padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="nombre">Nombre del Foro *</label>
            <input type="text" id="nombre" name="nombre" class="form-control" 
                   value="<?php echo htmlspecialchars($foro['nombre']); ?>" 
                   placeholder="Ej: Tecnología Libre, Agricultura Urbana..." required maxlength="100">
        </div>
        
        <div class="form-group">
            <label for="descripcion">Descripción *</label>
            <textarea id="descripcion" name="descripcion" class="form-control" 
                      rows="3" placeholder="Describe el propósito y temas de este foro..." required><?php echo htmlspecialchars($foro['descripcion']); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="responsable">Responsable *</label>
            <input type="text" id="responsable" name="responsable" class="form-control" 
                   value="<?php echo htmlspecialchars($foro['responsable']); ?>" 
                   placeholder="Nombre del coordinador responsable" required>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="icono">Icono</label>
                <select id="icono" name="icono" class="form-control">
                    <option value="folder" <?php echo $foro['icono'] == 'folder' ? 'selected' : ''; ?>>📁 Carpeta</option>
                    <option value="science" <?php echo $foro['icono'] == 'science' ? 'selected' : ''; ?>>🔬 Ciencia</option>
                    <option value="tech" <?php echo $foro['icono'] == 'tech' ? 'selected' : ''; ?>>💻 Tecnología</option>
                    <option value="comms" <?php echo $foro['icono'] == 'comms' ? 'selected' : ''; ?>>📢 Comunicación</option>
                    <option value="community" <?php echo $foro['icono'] == 'community' ? 'selected' : ''; ?>>🏘️ Comunidad</option>
                    <option value="education" <?php echo $foro['icono'] == 'education' ? 'selected' : ''; ?>>📚 Educación</option>
                    <option value="ecology" <?php echo $foro['icono'] == 'ecology' ? 'selected' : ''; ?>>🌱 Ecología</option>
                    <option value="energy" <?php echo $foro['icono'] == 'energy' ? 'selected' : ''; ?>>⚡ Energía</option>
                    <option value="workshop" <?php echo $foro['icono'] == 'workshop' ? 'selected' : ''; ?>>🛠️ Talleres</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="color">Color</label>
                <select id="color" name="color" class="form-control">
                    <option value="#CC0000" style="background: #CC0000; color: white;" <?php echo $foro['color'] == '#CC0000' ? 'selected' : ''; ?>>🔴 Rojo Patria</option>
                    <option value="#0033A0" style="background: #0033A0; color: white;" <?php echo $foro['color'] == '#0033A0' ? 'selected' : ''; ?>>🔵 Azul Patria</option>
                    <option value="#2E7D32" style="background: #2E7D32; color: white;" <?php echo $foro['color'] == '#2E7D32' ? 'selected' : ''; ?>>🟢 Verde Comunal</option>
                    <option value="#FFD100" style="background: #FFD100; color: black;" <?php echo $foro['color'] == '#FFD100' ? 'selected' : ''; ?>>🟡 Amarillo Patria</option>
                    <option value="#6A1B9A" style="background: #6A1B9A; color: white;" <?php echo $foro['color'] == '#6A1B9A' ? 'selected' : ''; ?>>🟣 Morado Popular</option>
                    <option value="#E65100" style="background: #E65100; color: white;" <?php echo $foro['color'] == '#E65100' ? 'selected' : ''; ?>>🟠 Naranja Revolución</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="orden">Orden de Visualización</label>
                <input type="number" id="orden" name="orden" class="form-control" 
                       value="<?php echo $foro['orden']; ?>" min="1" max="100">
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="permisos">Permisos de Acceso</label>
                <select id="permisos" name="permisos" class="form-control">
                    <option value="todos" <?php echo $foro['permisos'] == 'todos' ? 'selected' : ''; ?>>👥 Todos los usuarios</option>
                    <option value="registrados" <?php echo $foro['permisos'] == 'registrados' ? 'selected' : ''; ?>>🔐 Solo usuarios registrados</option>
                    <option value="tecnicos" <?php echo $foro['permisos'] == 'tecnicos' ? 'selected' : ''; ?>>🛠️ Solo técnicos y coordinadores</option>
                    <option value="coordinadores" <?php echo $foro['permisos'] == 'coordinadores' ? 'selected' : ''; ?>>🛡️ Solo coordinadores</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="activo" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1.8rem;">
                    <input type="checkbox" id="activo" name="activo" value="1" 
                           <?php echo $foro['activo'] ? 'checked' : ''; ?>>
                    🟢 Foro activo (visible para usuarios)
                </label>
            </div>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" class="btn">💾 Guardar Cambios</button>
            <a href="?pagina=gestion_foros" class="btn" style="background: #666;">↩️ Cancelar</a>
            <?php if ($estadisticas['total_hilos'] == 0): ?>
            <a href="?pagina=gestion_foros&accion=eliminar&id=<?php echo $foro_id; ?>" 
               onclick="return confirm('¿Estás seguro de ELIMINAR permanentemente este foro?')"
               class="btn" style="background: #f44336;">🗑️ Eliminar Foro</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Vista previa del foro -->
<div class="card" style="background: #f8f9fa;">
    <h3>👀 Vista Previa</h3>
    <div id="vista-previa-foro" style="
        background: white; 
        padding: 1.5rem; 
        border-radius: 8px; 
        border-left: 4px solid <?php echo $foro['color']; ?>;
        margin-top: 1rem;
    ">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
            <span id="preview-icono">
                <?php
                $iconos_map = [
                    'folder' => '📁',
                    'science' => '🔬',
                    'tech' => '💻',
                    'comms' => '📢',
                    'community' => '🏘️',
                    'education' => '📚',
                    'ecology' => '🌱',
                    'energy' => '⚡',
                    'workshop' => '🛠️'
                ];
                echo $iconos_map[$foro['icono']] ?? '📁';
                ?>
            </span>
            <strong id="preview-nombre"><?php echo $foro['nombre']; ?></strong>
            <?php if (!$foro['activo']): ?>
            <span style="background: #666; color: white; padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.7rem;">⏸️ INACTIVO</span>
            <?php endif; ?>
        </div>
        <p id="preview-descripcion" style="color: #666; margin: 0;"><?php echo $foro['descripcion']; ?></p>
        <div style="margin-top: 1rem; font-size: 0.8rem; color: #999;">
            Responsable: <span id="preview-responsable"><?php echo $foro['responsable']; ?></span>
            • Acceso: <span id="preview-permisos">
                <?php 
                $permisos_texto = [
                    'todos' => '👥 Público',
                    'registrados' => '🔐 Registrados', 
                    'tecnicos' => '🛠️ Técnicos+',
                    'coordinadores' => '🛡️ Coordinadores'
                ];
                echo $permisos_texto[$foro['permisos']] ?? $foro['permisos'];
                ?>
            </span>
        </div>
    </div>
</div>

<script>
// Actualizar vista previa en tiempo real
document.getElementById('nombre').addEventListener('input', function() {
    document.getElementById('preview-nombre').textContent = this.value;
});

document.getElementById('descripcion').addEventListener('input', function() {
    document.getElementById('preview-descripcion').textContent = this.value;
});

document.getElementById('responsable').addEventListener('input', function() {
    document.getElementById('preview-responsable').textContent = this.value;
});

document.getElementById('icono').addEventListener('change', function() {
    const iconos = {
        'folder': '📁', 'science': '🔬', 'tech': '💻', 'comms': '📢',
        'community': '🏘️', 'education': '📚', 'ecology': '🌱', 
        'energy': '⚡', 'workshop': '🛠️'
    };
    document.getElementById('preview-icono').textContent = iconos[this.value] || '📁';
});

document.getElementById('color').addEventListener('change', function() {
    document.getElementById('vista-previa-foro').style.borderLeftColor = this.value;
});

document.getElementById('permisos').addEventListener('change', function() {
    const permisosText = {
        'todos': '👥 Público',
        'registrados': '🔐 Registrados',
        'tecnicos': '🛠️ Técnicos+', 
        'coordinadores': '🛡️ Coordinadores'
    };
    document.getElementById('preview-permisos').textContent = permisosText[this.value] || this.value;
});

document.getElementById('activo').addEventListener('change', function() {
    const preview = document.getElementById('vista-previa-foro');
    if (!this.checked) {
        if (!preview.querySelector('.inactive-badge')) {
            const badge = document.createElement('span');
            badge.className = 'inactive-badge';
            badge.style = 'background: #666; color: white; padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.7rem; margin-left: 0.5rem;';
            badge.textContent = '⏸️ INACTIVO';
            document.getElementById('preview-nombre').appendChild(badge);
        }
    } else {
        const badge = preview.querySelector('.inactive-badge');
        if (badge) badge.remove();
    }
});
</script>