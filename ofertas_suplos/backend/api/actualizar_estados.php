
<?php
/**
 * @file actualizar_estados.php
 * @brief Actualizador automático de estados de ofertas
 * 
 * @description
 * - Actualiza ofertas de ACTIVO→PUBLICADO al cumplir fecha_inicio
 * - Actualiza ofertas de PUBLICADO→EVALUACION al cumplir fecha_cierre
 * - Registra resultados y errores en logs
 * 
 * @param PDO $conexion Objeto de conexión a BD (de conexion.php)
 * @return array Estadísticas: 
 *    ['publicados' => int, 'evaluacion' => int]
 * 
 * @database_requirements
 * - Tabla 'ofertas' con:
 *   - estado (ACTIVO/PUBLICADO/EVALUACION)
 *   - fecha_inicio (DATETIME)
 *   - fecha_cierre (DATETIME)
 * 
 * @security
 * - Usa consultas preparadas
 * - Oculta errores SQL (solo log)
 * 
 * @cron_recommendation Ejecutar cada hora
 */
require_once 'conexion.php';

function actualizarEstados($conexion) {
    $now = date('Y-m-d H:i:s');
    
    try {
        // Actualizar a PUBLICADO los que cumplieron fecha inicio
        $query = "UPDATE ofertas SET estado = 'PUBLICADO' 
                  WHERE estado = 'ACTIVO' AND fecha_inicio <= :now";
        $stmt = $conexion->prepare($query);
        $stmt->execute([':now' => $now]);
        $publicados = $stmt->rowCount();
        
        // Actualizar a EVALUACION los que cumplieron fecha cierre
        $query = "UPDATE ofertas SET estado = 'EVALUACION' 
                  WHERE estado = 'PUBLICADO' AND fecha_cierre <= :now";
        $stmt = $conexion->prepare($query);
        $stmt->execute([':now' => $now]);
        $evaluacion = $stmt->rowCount();
        
        return ['publicados' => $publicados, 'evaluacion' => $evaluacion];
    } catch (PDOException $e) {
        error_log("Error actualizando estados: " . $e->getMessage());
        return ['publicados' => 0, 'evaluacion' => 0];
    }
}

// Ejecutar actualización
actualizarEstados($conexion);
?>