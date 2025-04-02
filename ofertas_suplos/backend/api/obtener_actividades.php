<?php
/**
 * ENDPOINT: obtener_actividades.php
 * MÉTODO: GET
 * FUNCIÓN: Obtiene el listado completo de actividades registradas
 * 
 * RESPUESTAS:
 * - 200 Éxito:
 *   {
 *     "success": true,
 *     "data": [
 *       {"codigo": "001", "nombre": "Actividad 1"},
 *       ...
 *     ]
 *   }
 * - 200 Vacío:
 *   {"success": false, "message": "No hay actividades", "data": []}
 * - 500 Error:
 *   {"success": false, "message": "Error en el servidor", "data": []}
 * 
 * ESTRUCTURA DE DATOS:
 * - Cada actividad contiene:
 *   - codigo (string): Identificador único
 *   - nombre (string): Nombre descriptivo
 * 
 * TABLA INVOLUCRADA:
 * - actividades (campos: codigo, nombre)
 * 
 * SEGURIDAD:
 * - CORS abierto (*) - Ajustar en producción
 * - Logging detallado de errores
 * - Consultas preparadas
 * 
 * REGISTRO (LOGS):
 * - Inicio/fin del proceso
 * - Conteo de resultados
 * - Errores SQL y excepciones
 * 
 * USO FRONTEND:
 * fetch('/api/obtener_actividades.php')
 *   .then(response => response.json())
 *   .then(data => {
 *     if (data.success) {
 *       // usar data.data
 *     }
 *   });
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Configuración de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__.'/conexion.php';

// Registrar hora de inicio para depuración
error_log("Iniciando obtención de actividades - ".date('Y-m-d H:i:s'));

try {
    // Verificar conexión
    if (!$pdo) {
        throw new Exception("No hay conexión a la base de datos");
    }

    // Consulta SQL con manejo de errores explícito
    $sql = "SELECT codigo, nombre FROM actividades ORDER BY codigo";
    $stmt = $pdo->prepare($sql);
    
    if (!$stmt->execute()) {
        $errorInfo = $stmt->errorInfo();
        throw new Exception("Error en la consulta SQL: ".$errorInfo[2]);
    }
    
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($actividades)) {
        error_log("No se encontraron actividades en la base de datos");
        echo json_encode([
            'success' => false,
            'message' => 'No hay actividades registradas',
            'data' => []
        ]);
        exit;
    }
    
    error_log("Actividades encontradas: ".count($actividades));
    echo json_encode([
        'success' => true,
        'data' => $actividades
    ]);
    
} catch (Exception $e) {
    error_log("Error en obtener_actividades.php: ".$e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: '.$e->getMessage(),
        'data' => []
    ]);
}
?>