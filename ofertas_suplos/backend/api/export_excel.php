<?php
/**
 * ARCHIVO: export_excel.php
 * FUNCIÓN: Genera reporte en formato Excel (.xls) de todas las ofertas registradas
 * 
 * MÉTODO: GET
 * SALIDA: Archivo Excel descargable
 * 
 * ESTRUCTURA DEL REPORTE:
 * - Columnas: ID, Objeto, Descripción, Moneda, Presupuesto, Actividad,
 *             Fechas (Inicio/Cierre/Creación), Estado, Creador
 * - Formateo automático de:
 *   - Moneda (COP/USD con símbolo)
 *   - Números (separadores de miles)
 *   - Fechas (formato dd/mm/YYYY HH:MM)
 * 
 * TABLAS INVOLUCRADAS:
 * - ofertas (todos los campos)
 * - actividades (solo nombre para relación)
 * 
 * SEGURIDAD:
 * - No acepta parámetros externos (reporte completo)
 * - Headers evitan caché del navegador
 * 
 * USO:
 * <a href="/api/export_excel.php">Descargar Reporte Completo</a>
 * 
 * NOTAS:
 * - Formato .xls (compatible con Excel 2003+)
 * - Los saltos de línea en celdas pueden requerir ajustes manuales
 */
require_once 'conexion.php';

// Configurar headers para descarga de Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=reporte_completo_ofertas_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Consulta SQL para obtener todos los campos de ofertas
$query = "SELECT 
            o.id,
            o.objeto,
            o.descripcion,
            o.moneda,
            o.presupuesto,
            a.nombre as actividad,
            o.actividad_id,
            o.fecha_inicio,
            o.fecha_cierre,
            o.estado,
            o.creador,
            o.fecha_creacion
          FROM ofertas o
          LEFT JOIN actividades a ON o.actividad_id = a.id
          ORDER BY o.id DESC";

$stmt = $conexion->prepare($query);
$stmt->execute();
$ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear tabla HTML para Excel
echo "<table border='1'>";
// Encabezados con todos los campos
echo "<tr>
        <th>ID</th>
        <th>Objeto</th>
        <th>Descripción</th>
        <th>Moneda</th>
        <th>Presupuesto</th>
        <th>Actividad</th>
      
        <th>Fecha Inicio</th>
        <th>Fecha Cierre</th>
        <th>Estado</th>
        <th>Creador</th>
        <th>Fecha Creación</th>
    </tr>";

// Datos
foreach ($ofertas as $oferta) {
    // Formatear valores para mejor visualización
    $presupuesto = number_format($oferta['presupuesto'], 2, ',', '.');
    $moneda = ($oferta['moneda'] == 'COP') ? 'COP $' : 'USD $';
    $fecha_inicio = date('d/m/Y H:i', strtotime($oferta['fecha_inicio']));
    $fecha_cierre = date('d/m/Y H:i', strtotime($oferta['fecha_cierre']));
    $fecha_creacion = date('d/m/Y H:i', strtotime($oferta['fecha_creacion']));
    
    echo "<tr>
            <td>{$oferta['id']}</td>
            <td>{$oferta['objeto']}</td>
            <td>{$oferta['descripcion']}</td>
            <td>{$moneda}</td>
            <td>{$presupuesto}</td>
            <td>{$oferta['actividad']}</td>
            <td>{$fecha_inicio}</td>
            <td>{$fecha_cierre}</td>
            <td>{$oferta['estado']}</td>
            <td>{$oferta['creador']}</td>
            <td>{$fecha_creacion}</td>
        </tr>";
}
echo "</table>";
?>