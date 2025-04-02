<?php
/**
 * ENDPOINT: verificar_estados.php
 * MÉTODO: GET
 * FUNCIÓN: Actualiza automáticamente estados de ofertas según fechas de inicio/cierre
 * 
 * LÓGICA DE ACTUALIZACIÓN:
 * - ACTIVO → PUBLICADO: Cuando fecha_inicio ≤ ahora
 * - PUBLICADO → EVALUACION: Cuando fecha_cierre ≤ ahora
 * 
 * RESPUESTAS:
 * - 200 Éxito:
 *   {
 *     "success": true,
 *     "actualizados": X,  // Total de cambios
 *     "publicados": Y,    // Ofertas movidas a PUBLICADO
 *     "evaluacion": Z     // Ofertas movidas a EVALUACION
 *   }
 * - 500 Error:
 *   {"success": false, "message": "Error al verificar estados"}
 * 
 * TABLA AFECTADA:
 * - ofertas (campos: estado, fecha_inicio, fecha_cierre)
 * 
 * RECOMENDACIONES:
 * - Ejecutar periódicamente (ej: cada hora via CRON)
 * - No requiere parámetros de entrada
 * 
 * SEGURIDAD:
 * - Solo método GET
 * - Consultas preparadas
 * - No modifica datos sensibles
 */
header('Content-Type: application/json');
require_once '../conexion.php';

try {
    // Obtener la fecha/hora actual
    $now = date('Y-m-d H:i:s');
    
    // Actualizar a PUBLICADO los que han alcanzado fecha inicio
    $stmt = $conexion->prepare("
        UPDATE ofertas 
        SET estado = 'PUBLICADO' 
        WHERE estado = 'ACTIVO' 
        AND fecha_inicio <= :now
    ");
    $stmt->execute([':now' => $now]);
    $publicados = $stmt->rowCount();
    
    // Actualizar a EVALUACION los que han alcanzado fecha cierre
    $stmt = $conexion->prepare("
        UPDATE ofertas 
        SET estado = 'EVALUACION' 
        WHERE estado = 'PUBLICADO' 
        AND fecha_cierre <= :now
    ");
    $stmt->execute([':now' => $now]);
    $evaluacion = $stmt->rowCount();
    
    echo json_encode([
        'success' => true,
        'actualizados' => $publicados + $evaluacion,
        'publicados' => $publicados,
        'evaluacion' => $evaluacion
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al verificar estados: ' . $e->getMessage()
    ]);
}
?>