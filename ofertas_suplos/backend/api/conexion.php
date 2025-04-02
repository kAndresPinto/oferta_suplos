<?php
/**
 * ARCHIVO: conexion.php
 * FUNCIÓN: Establece conexión segura a la base de datos MySQL/MariaDB usando PDO
 * 
 * CONFIGURACIÓN:
 * - Host: localhost
 * - Base de datos: ofertas_suplos
 * - Usuario: root (¡Cambiar en producción!)
 * - Charset: utf8mb4 (soporte completo Unicode)
 * 
 * OPCIONES PDO:
 * - ERRMODE_EXCEPTION: Lanza excepciones en errores
 * - FETCH_ASSOC: Devuelve arrays asociativos
 * - EMULATE_PREPARES: False (prevención SQL injection)
 * 
 * MANEJO DE ERRORES:
 * - Registra errores en error_log
 * - Devuelve JSON con error amigable en fallos
 * 
 * USO:
 * require_once 'conexion.php';
 * $stmt = $pdo->query("SELECT...");
 * 
 * SEGURIDAD:
 * ¡NUNCA subir con credenciales reales en producción!
 * Recomendado usar variables de entorno (.env)
 */
$host = 'localhost';
$db   = 'ofertas_suplos';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';  // Mejor usar utf8mb4 para soportar todos los caracteres

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $conexion = new PDO($dsn, $user, $pass, $options);
    $pdo = $conexion; // Creamos un alias para compatibilidad
    
    // Opcional: Verificar conexión
    // $conexion->query("SELECT 1");
} catch (\PDOException $e) {
    error_log('Error de conexión: ' . $e->getMessage());
    die(json_encode([
        'success' => false,
        'message' => 'Error de conexión con la base de datos'
    ]));
}
?>