<?php
// INICIAR SESIÓN SIEMPRE AL PRINCIPIO
session_start();

// Configurar encoding UTF-8
header('Content-Type: text/html; charset=utf-8');

// Determinar qué página mostrar
$pagina = $_GET['pagina'] ?? 'inicio';

// Incluir header
include 'includes/header.php';

// Mostrar la página correspondiente
switch ($pagina) {
    case 'foros':
        include 'paginas/foros.php';
        break;
    case 'ver_foro':
        include 'paginas/ver_foro.php';
        break;
    case 'ver_hilo':
        include 'paginas/ver_hilo.php';
        break;
    case 'crear_hilo':
        include 'paginas/crear_hilo.php';
        break;
    case 'responder_hilo':
        include 'paginas/responder_hilo.php';
        break;
    case 'registro':
        include 'paginas/registro.php';
        break;
    case 'login':
        include 'paginas/login.php';
        break;
    case 'perfil':
        include 'paginas/perfil.php';
        break;
    case 'notificaciones':
        include 'paginas/notificaciones.php';
       break;
    case 'mensajes':
        include 'paginas/mensajes.php';
       break;
    case 'inicio':
    default:
        include 'paginas/inicio.php';
        break;
    case 'crear_foro':
        include 'paginas/crear_foro.php';
        break;
    case 'gestion_foros':
        include 'paginas/gestion_foros.php';
        break;
    case 'buscar':
        include 'paginas/buscar.php';
        break;
    case 'editar_foro':
        include 'paginas/editar_foro.php';
        break;
}

// Incluir footer
include 'includes/footer.php';
?>