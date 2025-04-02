<?php
/**
 * ARCHIVO: descargar_documento.php
 * FUNCIÓN: Endpoint para descarga segura de documentos vinculados a ofertas
 * 
 * MÉTODO: GET
 * RUTA: /api/descargar_documento.php?documento_id=[ID]
 * 
 * PARÁMETROS:
 * - documento_id (int): ID del documento a descargar (requerido)
 * 
 * RESPUESTAS:
 * - 200 OK: Archivo descargado con headers adecuados
 * - 400 Bad Request: ID no válido
 * - 404 Not Found: Documento no existe en BD o físicamente
 * - 500 Server Error: Fallo en la base de datos
 * 
 * SEGURIDAD:
 * - Verifica existencia física del archivo
 * - Sanitiza nombres de archivos en headers
 * - CORS restringido a métodos GET
 * - Consultas preparadas contra SQL injection
 * 
 * FORMATOS SOPORTADOS:
 * - PDF, DOC/DOCX, XLS/XLSX (otros como octet-stream)
 * 
 * ESTRUCTURA ESPERADA:
 * - /backend/uploads/documentos/ [debe ser escribible]
 * - Tablas requeridas:
 *   - documentos (con campos: id, archivo, oferta_id)
 *   - ofertas (con campo: id, objeto)
 * 
 * USO:
 * <a href="/api/descargar_documento.php?documento_id=123">Descargar</a>
 * 
 * LOGS:
 * - Errores de BD registrados en error_log
 */
// Versión para cuando conexion.php está en la misma carpeta (/api)
require_once __DIR__ . '/conexion.php';

// Configuración de headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Obtener ID del documento
$documentoId = $_GET['documento_id'] ?? null;

if (!$documentoId || !is_numeric($documentoId)) {
    http_response_code(400);
    die(json_encode(['error' => 'ID de documento no válido']));
}

try {
    // Obtener información del documento
    $stmt = $conexion->prepare("SELECT d.*, o.objeto 
                               FROM documentos d
                               JOIN ofertas o ON d.oferta_id = o.id
                               WHERE d.id = ?");
    $stmt->execute([$documentoId]);
    $documento = $stmt->fetch();
    
    if (!$documento || empty($documento['archivo'])) {
        http_response_code(404);
        die(json_encode(['error' => 'Documento no encontrado']));
    }
    
    // Ruta de documentos (ajustada para estructura correcta)
    $uploadDir = __DIR__ . '/../../backend/uploads/documentos/';
    $filePath = $uploadDir . $documento['archivo'];
    
    // Verificación adicional de ruta de archivos
    if (!file_exists($filePath)) {
        http_response_code(404);
        die(json_encode([
            'error' => 'Archivo físico no encontrado',
            'detalle' => [
                'ruta_esperada' => $filePath,
                'directorio_real' => realpath($uploadDir) ?: 'No existe'
            ]
        ]));
    }
    
    // Determinar tipo MIME
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    
    $contentType = $mimeTypes[$extension] ?? 'application/octet-stream';
    
    // Preparar headers para descarga
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . 
           preg_replace('/[^a-zA-Z0-9-_\.]/', '_', $documento['titulo']) . '.' . $extension . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    
    // Limpiar buffers y enviar archivo
    ob_clean();
    flush();
    readfile($filePath);
    exit;
    
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode([
        'error' => 'Error en la base de datos',
        'detalle' => $e->getMessage()
    ]));
}
?>