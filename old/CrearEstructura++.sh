#!/bin/bash
echo "🏗️ Creando estructura completa del Foro Comunal CTI..."

# Crear directorio principal
mkdir -p foro_comunal_cti
cd foro_comunal_cti

# Crear estructura de carpetas
mkdir -p app/{config,controllers,models,core}
mkdir -p public/assets/{css,js,img}
mkdir -p views/{layouts,auth,foros,hilos}
mkdir -p storage/{database,logs}

echo "📁 Creando archivos del proyecto..."

# Archivos de configuración
cat > app/config/database.php << 'EOF'
<?php
class DatabaseConfig {
    const DRIVER = 'mysql'; // o 'sqlite'
    const HOST = 'localhost';
    const USERNAME = 'root';
    const PASSWORD = '';
    const DATABASE = 'foro_comunal_cti';
    const CHARSET = 'utf8mb4';
    
    // Para SQLite
    const SQLITE_PATH = __DIR__ . '/../../storage/database/foro_comunal.db';
}
?>
EOF

# Core - Database
cat > app/core/Database.php << 'EOF'
<?php
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $config = new DatabaseConfig();
        
        try {
            if ($config::DRIVER === 'sqlite') {
                // Crear directorio si no existe
                $dir = dirname($config::SQLITE_PATH);
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                
                $this->connection = new PDO("sqlite:" . $config::SQLITE_PATH);
            } else {
                $dsn = "mysql:host={$config::HOST};dbname={$config::DATABASE};charset={$config::CHARSET}";
                $this->connection = new PDO($dsn, $config::USERNAME, $config::PASSWORD);
            }
            
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->createTables();
            
        } catch(PDOException $e) {
            die("❌ Error de conexión: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    private function createTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(100) NOT NULL,
            email VARCHAR(150) UNIQUE,
            comuna VARCHAR(100) NOT NULL,
            rol ENUM('vocero', 'tecnico', 'ciudadano', 'coordinador') DEFAULT 'ciudadano',
            habilidades TEXT,
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            activo BOOLEAN DEFAULT TRUE
        );
        
        CREATE TABLE IF NOT EXISTS foros (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(100) NOT NULL,
            descripcion TEXT,
            responsable VARCHAR(100),
            color VARCHAR(7) DEFAULT '#CC0000',
            orden INTEGER DEFAULT 0,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS hilos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo VARCHAR(200) NOT NULL,
            contenido TEXT NOT NULL,
            usuario_id INTEGER,
            foro_id INTEGER,
            es_importante BOOLEAN DEFAULT FALSE,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
            FOREIGN KEY (foro_id) REFERENCES foros(id)
        );
        
        CREATE TABLE IF NOT EXISTS respuestas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            contenido TEXT NOT NULL,
            usuario_id INTEGER,
            hilo_id INTEGER,
            fecha_respuesta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
            FOREIGN KEY (hilo_id) REFERENCES hilos(id)
        );
        
        INSERT OR IGNORE INTO foros (nombre, descripcion, responsable, orden) VALUES
        ('Ciencia, Tecnología e Innovación', 'Espacio para discutir soberanía tecnológica, software libre y innovación popular', 'Coordinador CTI', 1),
        ('Comunicación Popular', 'Método Calles, contenidos, redes sociales y medios comunitarios', 'Coordinación Comunicación', 2),
        ('Organización Territorial', 'Consejos comunales, comunas y articulación popular', 'Coordinación Organización', 3),
        ('Formación Política', 'Teoría Crítica, pedagogía liberadora y formación ideológica', 'Coordinación Formación', 4);
        ";
        
        $this->connection->exec($sql);
    }
}
?>
EOF

# Core - Router
cat > app/core/Router.php << 'EOF'
<?php
class Router {
    private $routes = [];
    
    public function addRoute($method, $path, $controller, $action) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller,
            'action' => $action
        ];
    }
    
    public function dispatch() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        $requestUri = str_replace($basePath, '', $requestUri);
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod && $this->matchPath($route['path'], $requestUri)) {
                $controllerName = $route['controller'];
                $action = $route['action'];
                
                require_once __DIR__ . "/../controllers/{$controllerName}.php";
                $controller = new $controllerName();
                $controller->$action();
                return;
            }
        }
        
        // 404 - Página no encontrada
        http_response_code(404);
        echo "Página no encontrada";
    }
    
    private function matchPath($routePath, $requestUri) {
        return $routePath === $requestUri;
    }
}
?>
EOF

# Modelo Usuario
cat > app/models/Usuario.php << 'EOF'
<?php
class Usuario {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function registrar($datos) {
        $sql = "INSERT INTO usuarios (nombre, email, comuna, rol, habilidades) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $datos['nombre'],
            $datos['email'],
            $datos['comuna'],
            $datos['rol'],
            $datos['habilidades']
        ]);
    }
    
    public function obtenerPorId($id) {
        $sql = "SELECT * FROM usuarios WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function todos() {
        $sql = "SELECT * FROM usuarios WHERE activo = 1 ORDER BY fecha_registro DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
EOF

# Controlador de Autenticación
cat > app/controllers/AuthController.php << 'EOF'
<?php
require_once __DIR__ . '/../models/Usuario.php';

class AuthController {
    public function registro() {
        $mensaje = '';
        $error = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = new Usuario();
            
            $datos = [
                'nombre' => trim($_POST['nombre']),
                'email' => trim($_POST['email']),
                'comuna' => trim($_POST['comuna']),
                'rol' => $_POST['rol'],
                'habilidades' => trim($_POST['habilidades'])
            ];
            
            if (empty($datos['nombre']) || empty($datos['comuna'])) {
                $error = "Nombre y comuna son obligatorios";
            } else {
                if ($usuario->registrar($datos)) {
                    $mensaje = "¡Registro exitoso! Bienvenid@ al foro comunitario.";
                } else {
                    $error = "Error al registrar usuario";
                }
            }
        }
        
        require_once __DIR__ . '/../../views/auth/registro.php';
    }
    
    public function login() {
        // Implementar login después
        echo "Página de login - Próximamente";
    }
}
?>
EOF

# Layout principal
cat > views/layouts/header.php << 'EOF'
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foro Comunal CTI - Poder Popular</title>
    <link rel="stylesheet" href="/public/assets/css/estilo.css">
</head>
<body>
    <header class="header-comunal">
        <h1>🏭 Foro Comunal CTI</h1>
        <p>Ciencia, Tecnología e Innovación del Poder Popular</p>
    </header>
    <nav class="nav-principal">
        <a href="/public/index.php">🏠 Inicio</a>
        <a href="/public/index.php?action=foros">📋 Foros</a>
        <a href="/public/index.php?action=registro">👤 Registrarse</a>
        <a href="/public/index.php?action=login">🔐 Login</a>
    </nav>
    <main class="container">
EOF

cat > views/layouts/footer.php << 'EOF'
    </main>
    <footer class="footer-comunal">
        <p>Foro Comunal CTI - Construyendo soberanía tecnológica desde el poder popular</p>
        <p>🚧 Desarrollado con Tecnologías Libres 🚧</p>
    </footer>
</body>
</html>
EOF

# Vista de registro
cat > views/auth/registro.php << 'EOF'
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="form-container">
    <h2>👤 Registro en el Foro Comunal</h2>
    <p>Únete a nuestra comunidad de Ciencia, Tecnología e Innovación Popular</p>
    
    <?php if ($mensaje): ?>
        <div class="alert alert-success"><?php echo $mensaje; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="nombre">Nombre completo *</label>
            <input type="text" id="nombre" name="nombre" class="form-control" 
                   value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" class="form-control"
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="comuna">Comuna o territorio *</label>
            <input type="text" id="comuna" name="comuna" class="form-control"
                   value="<?php echo isset($_POST['comuna']) ? htmlspecialchars($_POST['comuna']) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="rol">Rol en la comunidad</label>
            <select id="rol" name="rol" class="form-control">
                <option value="ciudadano">Ciudadano</option>
                <option value="tecnico">Técnico Popular</option>
                <option value="vocero">Vocero Comunal</option>
                <option value="coordinador">Coordinador</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="habilidades">Habilidades o conocimientos</label>
            <textarea id="habilidades" name="habilidades" class="form-control" 
                      placeholder="Ej: Electricidad, Programación, Agricultura, Comunicación..."><?php echo isset($_POST['habilidades']) ? htmlspecialchars($_POST['habilidades']) : ''; ?></textarea>
        </div>
        
        <button type="submit" class="btn-comunal">✅ Registrarse en el Foro</button>
    </form>
    
    <div class="form-footer">
        <p>¿Ya tienes cuenta? <a href="/public/index.php?action=login">Inicia sesión aquí</a></p>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
EOF

# Archivo principal
cat > public/index.php << 'EOF'
<?php
// Inicializar la aplicación
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Router.php';

// Crear router y definir rutas
$router = new Router();

// Rutas principales
$router->addRoute('GET', '/', 'ForoController', 'index');
$router->addRoute('GET', '/foros', 'ForoController', 'index');
$router->addRoute('GET', '/registro', 'AuthController', 'registro');
$router->addRoute('POST', '/registro', 'AuthController', 'registro');
$router->addRoute('GET', '/login', 'AuthController', 'login');

// Manejar la ruta actual
$router->dispatch();
?>
EOF

# Estilos CSS
cat > public/assets/css/estilo.css << 'EOF'
/* Estilos del Foro Comunal CTI */
:root {
    --rojo-patria: #CC0000;
    --azul-patria: #0033A0;
    --amarillo-patria: #FFD100;
    --verde-comunal: #2E7D32;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    line-height: 1.6;
}

.header-comunal {
    background: linear-gradient(135deg, var(--rojo-patria), var(--azul-patria));
    color: white;
    padding: 2rem 1rem;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.nav-principal {
    background: rgba(46, 125, 50, 0.95);
    padding: 1rem;
    display: flex;
    justify-content: center;
    gap: 2rem;
    backdrop-filter: blur(10px);
    flex-wrap: wrap;
}

.nav-principal a {
    color: white;
    text-decoration: none;
    padding: 0.8rem 1.5rem;
    border-radius: 25px;
    transition: all 0.3s ease;
    font-weight: bold;
}

.nav-principal a:hover {
    background: rgba(255,255,255,0.2);
    transform: translateY(-2px);
}

.container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.form-container {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    max-width: 600px;
    margin: 0 auto;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: bold;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 0.8rem;
    border: 2px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--rojo-patria);
}

.btn-comunal {
    background: linear-gradient(135deg, var(--rojo-patria), #ff4444);
    color: white;
    padding: 1rem 2rem;
    border: none;
    border-radius: 25px;
    font-size: 1.1rem;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
}

.btn-comunal:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(204,0,0,0.3);
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    text-align: center;
}

.alert-success {
    background: #e8f5e8;
    border: 1px solid #4caf50;
    color: #2e7d32;
}

.alert-error {
    background: #ffebee;
    border: 1px solid #f44336;
    color: #c62828;
}

.footer-comunal {
    text-align: center;
    padding: 2rem;
    background: #333;
    color: white;
    margin-top: 3rem;
}

@media (max-width: 768px) {
    .nav-principal {
        gap: 1rem;
    }
    
    .nav-principal a {
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
    }
    
    .container {
        margin: 1rem auto;
    }
}
EOF

echo "✅ Estructura completa creada!"
echo ""
echo "🚀 PARA INICIAR EL PROYECTO:"
echo "1. Navega a la carpeta: cd foro_comunal_cti"
echo "2. Accede via: http://localhost:8080/foro_comunal_cti/public/"
echo ""
echo "📁 ESTRUCTURA CREADA:"
find . -type f -name "*.php" -o -name "*.css" | sort