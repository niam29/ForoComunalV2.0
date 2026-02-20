<?php
require_once 'includes/config.php';

// Verificar si el usuario es coordinador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'coordinador') {
    echo "<div class='card'>";
    echo "<h2>🚫 Acceso Restringido</h2>";
    echo "<p>No tienes permisos para crear foros. Esta función es solo para coordinadores.</p>";
    echo "<a href='?pagina=foros' class='btn'>← Volver a Foros</a>";
    echo "</div>";
    return;
}

$mensaje = '';

// Procesar formulario de creación
if ($_POST) {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $responsable = trim($_POST['responsable']);
    $icono = $_POST['icono'];
    $color = $_POST['color'];
    $permisos = $_POST['permisos'];
    $orden = intval($_POST['orden']);
    
    // Validaciones
    if (empty($nombre) || empty($descripcion) || empty($responsable)) {
        $mensaje = "❌ Nombre, descripción y responsable son obligatorios.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO foros (nombre, descripcion, responsable, icono, color, permisos, orden) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$nombre, $descripcion, $responsable, $icono, $color, $permisos, $orden]);
            
            $foro_id = $pdo->lastInsertId();
            $mensaje = "✅ ¡Foro creado exitosamente!";
            
            // Limpiar formulario después de éxito
            $_POST = [];
            
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $mensaje = "❌ Ya existe un foro con ese nombre.";
            } else {
                $mensaje = "❌ Error al crear el foro: " . $e->getMessage();
            }
        }
    }
}

// Obtener el próximo orden disponible
$proximo_orden = 1;
if ($pdo) {
    $ultimo_orden = $pdo->query("SELECT MAX(orden) as max_orden FROM foros")->fetch();
    $proximo_orden = $ultimo_orden['max_orden'] + 1;
}
?>

<div class="card">
    <h2>🏗️ Crear Nuevo Foro</h2>
    <p>Crea un nuevo espacio de diálogo para la comunidad</p>
    
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
                   value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" 
                   placeholder="Ej: Tecnología Libre, Agricultura Urbana..." required maxlength="100">
        </div>
        
        <div class="form-group">
            <label for="descripcion">Descripción *</label>
            <textarea id="descripcion" name="descripcion" class="form-control" 
                      rows="3" placeholder="Describe el propósito y temas de este foro..." required><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="responsable">Responsable *</label>
            <input type="text" id="responsable" name="responsable" class="form-control" 
                   value="<?php echo isset($_POST['responsable']) ? htmlspecialchars($_POST['responsable']) : ''; ?>" 
                   placeholder="Nombre del coordinador responsable" required>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
            <div class="form-group">
    <label for="icono">Icono</label>
    <select id="icono" name="icono" class="form-control">
        <option value="folder" <?php echo (isset($_POST['icono']) && $_POST['icono'] == 'folder') ? 'selected' : ''; ?>>📁 Carpeta</option>
        <option value="science" <?php echo (isset($_POST['icono']) && $_POST['icono'] == 'science') ? 'selected' : ''; ?>>🔬 Ciencia</option>
        <option value="tech" <?php echo (isset($_POST['icono']) && $_POST['icono'] == 'tech') ? 'selected' : ''; ?>>💻 Tecnología</option>
        <option value="comms" <?php echo (isset($_POST['icono']) && $_POST['icono'] == 'comms') ? 'selected' : ''; ?>>📢 Comunicación</option>
        <option value="community" <?php echo (isset($_POST['icono']) && $_POST['icono'] == 'community') ? 'selected' : ''; ?>>🏘️ Comunidad</option>
        <option value="education" <?php echo (isset($_POST['icono']) && $_POST['icono'] == 'education') ? 'selected' : ''; ?>>📚 Educación</option>
        <option value="ecology" <?php echo (isset($_POST['icono']) && $_POST['icono'] == 'ecology') ? 'selected' : ''; ?>>🌱 Ecología</option>
        <option value="energy" <?php echo (isset($_POST['icono']) && $_POST['icono'] == 'energy') ? 'selected' : ''; ?>>⚡ Energía</option>
        <option value="workshop" <?php echo (isset($_POST['icono']) && $_POST['icono'] == 'workshop') ? 'selected' : ''; ?>>🛠️ Talleres</option>
    </select>
</div>
            
            <div class="form-group">
                <label for="color">Color</label>
                <select id="color" name="color" class="form-control">
                    <option value="#CC0000" style="background: #CC0000; color: white;" <?php echo (isset($_POST['color']) && $_POST['color'] == '#CC0000') ? 'selected' : ''; ?>>🔴 Rojo Patria</option>
                    <option value="#0033A0" style="background: #0033A0; color: white;" <?php echo (isset($_POST['color']) && $_POST['color'] == '#0033A0') ? 'selected' : ''; ?>>🔵 Azul Patria</option>
                    <option value="#2E7D32" style="background: #2E7D32; color: white;" <?php echo (isset($_POST['color']) && $_POST['color'] == '#2E7D32') ? 'selected' : ''; ?>>🟢 Verde Comunal</option>
                    <option value="#FFD100" style="background: #FFD100; color: black;" <?php echo (isset($_POST['color']) && $_POST['color'] == '#FFD100') ? 'selected' : ''; ?>>🟡 Amarillo Patria</option>
                    <option value="#6A1B9A" style="background: #6A1B9A; color: white;" <?php echo (isset($_POST['color']) && $_POST['color'] == '#6A1B9A') ? 'selected' : ''; ?>>🟣 Morado Popular</option>
                    <option value="#E65100" style="background: #E65100; color: white;" <?php echo (isset($_POST['color']) && $_POST['color'] == '#E65100') ? 'selected' : ''; ?>>🟠 Naranja Revolución</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="orden">Orden de Visualización</label>
                <input type="number" id="orden" name="orden" class="form-control" 
                       value="<?php echo isset($_POST['orden']) ? $_POST['orden'] : $proximo_orden; ?>" 
                       min="1" max="100">
            </div>
        </div>
        
        <div class="form-group">
            <label for="permisos">Permisos de Acceso</label>
            <select id="permisos" name="permisos" class="form-control">
                <option value="todos" <?php echo (isset($_POST['permisos']) && $_POST['permisos'] == 'todos') ? 'selected' : ''; ?>>👥 Todos los usuarios</option>
                <option value="registrados" <?php echo (isset($_POST['permisos']) && $_POST['permisos'] == 'registrados') ? 'selected' : ''; ?>>🔐 Solo usuarios registrados</option>
                <option value="tecnicos" <?php echo (isset($_POST['permisos']) && $_POST['permisos'] == 'tecnicos') ? 'selected' : ''; ?>>🛠️ Solo técnicos y coordinadores</option>
                <option value="coordinadores" <?php echo (isset($_POST['permisos']) && $_POST['permisos'] == 'coordinadores') ? 'selected' : ''; ?>>🛡️ Solo coordinadores</option>
            </select>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" class="btn">🏗️ Crear Foro</button>
            <a href="?pagina=foros" class="btn" style="background: #666;">↩️ Cancelar</a>
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
        border-left: 4px solid #CC0000;
        margin-top: 1rem;
    ">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
            <span id="preview-icono">📁</span>
            <strong id="preview-nombre">Nombre del foro</strong>
        </div>
        <p id="preview-descripcion" style="color: #666; margin: 0;">Descripción del foro aparecerá aquí</p>
        <div style="margin-top: 1rem; font-size: 0.8rem; color: #999;">
            Responsable: <span id="preview-responsable">[Responsable]</span>
        </div>
    </div>
</div>

<script>
// Actualizar vista previa en tiempo real
document.getElementById('nombre').addEventListener('input', function() {
    document.getElementById('preview-nombre').textContent = this.value || 'Nombre del foro';
});

document.getElementById('descripcion').addEventListener('input', function() {
    document.getElementById('preview-descripcion').textContent = this.value || 'Descripción del foro aparecerá aquí';
});

document.getElementById('responsable').addEventListener('input', function() {
    document.getElementById('preview-responsable').textContent = this.value || '[Responsable]';
});

document.getElementById('icono').addEventListener('change', function() {
    document.getElementById('preview-icono').textContent = this.value;
});

document.getElementById('color').addEventListener('change', function() {
    document.getElementById('vista-previa-foro').style.borderLeftColor = this.value;
});
</script>
