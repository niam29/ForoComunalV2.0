<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foro Comunal CTI</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>🏭 Foro Comunal CTI</h1>
            <p>Ciencia, Tecnología e Innovación del Poder Popular</p>
        </div>
    </header>
    
    <!-- Menú móvil hamburguesa -->
    <input type="checkbox" id="menu-toggle" class="menu-toggle">
    <label for="menu-toggle" class="menu-btn">
        <span></span>
        <span></span>
        <span></span>
    </label>
    
    <nav class="main-nav">
        <div class="container">
            <a href="index.php" class="nav-link">🏠 Inicio</a>
            <a href="index.php?pagina=foros" class="nav-link">📋 Foros</a>
            <a href="index.php?pagina=buscar" class="nav-link">🔍 Buscar</a>
            
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <!-- Usuario logueado -->
                <a href="index.php?pagina=perfil" class="nav-link">👤 <?php echo htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8'); ?></a>
                
                <!-- Notificaciones con contador -->
                <a href="index.php?pagina=notificaciones" class="nav-link">
                    🔔 Notificaciones
                    <?php
                    // Solo cargar funciones si la BD está disponible
                    if (file_exists('includes/funciones_notificaciones.php') && isset($pdo)) {
                        require_once 'includes/funciones_notificaciones.php';
                        $total_no_leidas = obtenerNotificacionesNoLeidas($_SESSION['usuario_id']);
                        if ($total_no_leidas > 0): 
                    ?>
                    <span style="background: #FF9800; color: white; padding: 0.2rem 0.4rem; border-radius: 10px; font-size: 0.7rem; margin-left: 0.5rem;">
                        <?php echo $total_no_leidas; ?>
                    </span>
                    <?php 
                        endif;
                    }
                    ?>
                </a>
                
                <a href="index.php?pagina=mensajes" class="nav-link">📩 Mensajes</a>
                <a href="logout.php" class="nav-link">🚪 Salir</a>
            <?php else: ?>
                <!-- Usuario no logueado -->
                <a href="index.php?pagina=registro" class="nav-link">👤 Registro</a>
                <a href="index.php?pagina=login" class="nav-link">🔐 Login</a>
            <?php endif; ?>
        </div>
    </nav>
    
    <main class="container">