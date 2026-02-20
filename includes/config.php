<?php
// config.php - VERSIÓN COMPATIBLE CON UTF-8
$host = '127.0.0.1';
$dbname = 'foro_comunal_cti';
$username = 'root';
$password = 'root';
$port = '3306';
$pdo = null;

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Configuración para UTF-8
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // Forzar UTF-8 en la conexión
    $pdo->exec("SET NAMES 'utf8mb4'");
    $pdo->exec("SET CHARACTER SET utf8mb4");
    
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("Error de conexión a la base de datos");
}
?>
