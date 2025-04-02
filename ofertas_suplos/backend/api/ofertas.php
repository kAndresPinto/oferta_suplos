<?php
/**
 * API: ofertas.php
 * VERSIÓN: 1.0
 * DESCRIPCIÓN: Endpoint centralizado para gestión completa de ofertas (CRUD + documentos)
 * 
 * MÉTODOS SOPORTADOS:
 * - GET    /ofertas      Listar ofertas (con filtros)
 * - POST   /ofertas      Crear nueva oferta (con documentos adjuntos)
 * - PUT    /ofertas      Actualizar estado de ofertas (ej: publicar)
 * 
 * AUTENTICACIÓN: No implementada (requeriría JWT/OAuth en producción)
 * 
 * FORMATOS ACEPTADOS:
 * - JSON (application/json)
 * - FormData (multipart/form-data para subida de archivos)
 * 
 * CORS: Habilitado para todos los orígenes (*) - Ajustar en producción
 */

/**
 * CONFIGURACIONES PRINCIPALES
 * - Directorio uploads: ../uploads/documentos/
 * - Tipos de archivo permitidos: PDF, DOC/DOCX, XLS/XLSX
 * - Tamaño máximo por archivo: 5MB
 * - Límites PHP: upload_max_filesize=10M, post_max_size=12M
 */

/**
 * ESTRUCTURA DE DATOS (TABLAS)
 * - ofertas: id, objeto, descripcion, moneda, presupuesto, actividad, 
 *            fecha_inicio, fecha_cierre, estado, creador, fecha_creacion
 * - documentos: id, oferta_id, titulo, descripcion, archivo
 */

/**
 * ENDPOINTS Y EJEMPLOS:
 * 
 * ▶ GET /ofertas?filtro_estado=ACTIVO
 *   - Filtros opcionales: filtro_objeto, filtro_actividad, filtro_creador
 *   - Parámetro: with_documents=true para incluir documentos
 * 
 * ▶ POST /ofertas (multipart/form-data)
 *   - Campos requeridos: objeto, presupuesto, fecha_inicio, fecha_cierre
 *   - Documentos: array 'documentos' con {titulo, descripcion, archivo}
 * 
 * ▶ PUT /ofertas (application/json)
 *   - Acción requerida: {"action": "publicar", "id": 123}
 */

/**
 * MANEJO DE ERRORES:
 * - 400 Bad Request: Validación fallida
 * - 404 Not Found: Recurso no existe
 * - 405 Method Not Allowed
 * - 500 Server Error
 * - Todos incluyen: {success: bool, message: string, timestamp: string}
 */

/**
 * SEGURIDAD:
 * - Validación estricta de tipos MIME
 * - Sanitización de nombres de archivo
 * - Consultas preparadas
 * - Transacciones para operaciones críticas
 */

/**
 * REGISTRO (LOGGING):
 * - Errores detallados en error_log
 * - Auditoría de cambios de estado
 */

/**
 * MEJORAS RECOMENDADAS:
 * 1. Autenticación (JWT)
 * 2. Limitar CORS a dominios específicos
 * 3. Paginación para listados grandes
 * 4. Cache HTTP para GET
 */
// Limpiar buffer de salida si existe
if (ob_get_level() > 0) {
    ob_end_clean();
}

// Configuración de headers para API REST
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

require_once 'conexion.php';

// ================= CONFIGURACIÓN =================
$uploadDir = __DIR__ . '/../uploads/documentos/';
$allowedTypes = [
    'application/pdf' => 'pdf',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/vnd.ms-excel' => 'xls',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx'
];
$maxFileSize = 5 * 1024 * 1024; // 5MB

// Configurar límites de subida
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '12M');
ini_set('max_execution_time', '300');

// ================= FUNCIONES AUXILIARES =================

/**
 * Envía una respuesta de error estandarizada
 */
function sendError($message, $code = 400) {
    http_response_code($code);
    die(json_encode([
        'success' => false,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]));
}

/**
 * Sube un archivo al servidor con validaciones
 */
function subirArchivo($file, $uploadDir, $allowedTypes, $maxSize) {
    // Validar errores de subida
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir el archivo. Código: ' . $file['error']);
    }
    
    // Validar tamaño
    if ($file['size'] > $maxSize) {
        throw new Exception('El archivo excede el tamaño máximo permitido de 5MB');
    }
    
    // Validar tipo de archivo
    $fileType = mime_content_type($file['tmp_name']);
    if (!array_key_exists($fileType, $allowedTypes)) {
        throw new Exception('Tipo de archivo no permitido. Solo se aceptan PDF, Word y Excel');
    }
    
    // Crear directorio si no existe
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            throw new Exception('No se pudo crear el directorio para almacenar archivos');
        }
    }
    
    // Generar nombre único para el archivo
    $extension = $allowedTypes[$fileType];
    $filename = 'doc_' . uniqid() . '.' . $extension;
    $destination = $uploadDir . $filename;
    
    // Mover archivo temporal a su ubicación final
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('No se pudo guardar el archivo en el servidor');
    }
    
    return $filename;
}

/**
 * Actualiza automáticamente los estados de las ofertas según fechas
 */
function actualizarEstadosAutomaticos($conexion) {
    $now = date('Y-m-d H:i:s');
    $resultados = [
        'publicados' => 0, 
        'evaluacion' => 0,
        'errores' => 0
    ];
    
    try {
        // Transacción para asegurar consistencia
        $conexion->beginTransaction();
        
        // Actualizar a PUBLICADO (fecha_inicio alcanzada)
        $query = "UPDATE ofertas SET estado = 'PUBLICADO' 
                  WHERE estado = 'ACTIVO' AND fecha_inicio <= :now";
        $stmt = $conexion->prepare($query);
        $stmt->execute([':now' => $now]);
        $resultados['publicados'] = $stmt->rowCount();
        
        // Actualizar a EVALUACIÓN (fecha_cierre alcanzada)
        $query = "UPDATE ofertas SET estado = 'EVALUACION' 
                  WHERE estado = 'PUBLICADO' AND fecha_cierre <= :now";
        $stmt = $conexion->prepare($query);
        $stmt->execute([':now' => $now]);
        $resultados['evaluacion'] = $stmt->rowCount();
        
        $conexion->commit();
    } catch (PDOException $e) {
        $conexion->rollBack();
        error_log("Error actualizando estados automáticos: " . $e->getMessage());
        $resultados['errores']++;
    }
    
    return $resultados;
}

/**
 * Obtiene los documentos asociados a una oferta
 */
function getDocumentosOferta($conexion, $ofertaId) {
    $query = "SELECT id, titulo, descripcion, archivo FROM documentos WHERE oferta_id = ?";
    $stmt = $conexion->prepare($query);
    $stmt->execute([$ofertaId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ================= MANEJO DE SOLICITUDES =================

// Manejar solicitud OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Actualizar estados automáticos al inicio
$estadosActualizados = actualizarEstadosAutomaticos($conexion);

try {
    // Obtener datos de entrada según el método
    $input = [];
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $input = $_GET;
    } else {
        // Manejar JSON o FormData según Content-Type
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'multipart/form-data') !== false) {
            $input = $_POST;
            // Procesar archivos subidos
            $input['documentos_files'] = $_FILES['documentos'] ?? [];
        } else {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
        }
    }

    // Enrutamiento por método HTTP
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGetRequest($conexion, $input, $estadosActualizados);
            break;
            
        case 'POST':
            handlePostRequest($conexion, $input, $uploadDir, $allowedTypes, $maxFileSize);
            break;
            
        case 'PUT':
            handlePutRequest($conexion, $input);
            break;
            
        default:
            sendError("Método no permitido", 405);
    }
} catch (PDOException $e) {
    error_log("Error en la base de datos: " . $e->getMessage());
    sendError("Error en el servidor: " . $e->getMessage(), 500);
} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    sendError($e->getMessage(), 400);
}

// ================= MANEJADORES DE SOLICITUDES =================

/**
 * Maneja solicitudes GET (listar ofertas)
 */
function handleGetRequest($conexion, $input, $estadosActualizados) {
    // Filtros
    $filtros = [
        'objeto' => $input['filtro_objeto'] ?? '',
        'estado' => $input['filtro_estado'] ?? '',
        'actividad' => $input['filtro_actividad'] ?? '',
        'creador' => $input['filtro_creador'] ?? ''
    ];
    
    // Construir consulta base
    $query = "SELECT 
        id, objeto, descripcion, moneda, presupuesto, actividad,
        fecha_inicio, fecha_cierre, estado, creador,
        DATE_FORMAT(fecha_inicio, '%Y-%m-%d %H:%i') as fecha_inicio_formato,
        DATE_FORMAT(fecha_cierre, '%Y-%m-%d %H:%i') as fecha_cierre_formato
        FROM ofertas WHERE 1=1";
    
    $params = [];
    foreach ($filtros as $campo => $valor) {
        if (!empty($valor)) {
            $query .= " AND $campo LIKE ?";
            $params[] = ($campo === 'objeto') ? "%$valor%" : "$valor";
        }
    }
    
    // Ordenamiento
    $query .= " ORDER BY fecha_inicio ASC";
    
    // Ejecutar consulta
    $stmt = $conexion->prepare($query);
    $stmt->execute($params);
    
    // Obtener documentos asociados si se solicita
    $withDocuments = !empty($input['with_documents']);
    $ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($withDocuments) {
        foreach ($ofertas as &$oferta) {
            $oferta['documentos'] = getDocumentosOferta($conexion, $oferta['id']);
        }
    }
    
    echo json_encode([
        'success' => true, 
        'data' => $ofertas,
        'estados_actualizados' => $estadosActualizados,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Maneja solicitudes POST (crear ofertas)
 */
function handlePostRequest($conexion, $input, $uploadDir, $allowedTypes, $maxFileSize) {
    // Validaciones básicas
    $required = ['objeto', 'presupuesto', 'fecha_inicio', 'fecha_cierre'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            sendError("El campo $field es requerido");
        }
    }
    
    // Validación de fechas
    if (strtotime($input['fecha_cierre']) <= strtotime($input['fecha_inicio'])) {
        sendError("La fecha de cierre debe ser posterior a la de inicio");
    }
    
    // Validación de presupuesto
    if (!is_numeric($input['presupuesto']) || $input['presupuesto'] <= 0) {
        sendError("Presupuesto debe ser un número positivo");
    }
    
    // Iniciar transacción para asegurar integridad
    $conexion->beginTransaction();
    
    try {
        // Insertar oferta principal
        $query = "INSERT INTO ofertas (
            objeto, descripcion, moneda, presupuesto, actividad,
            fecha_inicio, fecha_cierre, estado, creador
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'ACTIVO', ?)";
        
        $stmt = $conexion->prepare($query);
        $success = $stmt->execute([
            $input['objeto'],
            $input['descripcion'] ?? null,
            $input['moneda'] ?? 'COP',
            $input['presupuesto'],
            $input['actividad'] ?? null,
            $input['fecha_inicio'],
            $input['fecha_cierre'],
            $input['creador'] ?? 'Sistema'
        ]);
        
        if (!$success) {
            throw new Exception("Error al crear el proceso en la base de datos");
        }
        
        $ofertaId = $conexion->lastInsertId();
        
        // Procesar documentos si existen
        $documentosProcesados = 0;
        if (!empty($input['documentos'])) {
            $queryDoc = "INSERT INTO documentos (oferta_id, titulo, descripcion, archivo) VALUES (?, ?, ?, ?)";
            $stmtDoc = $conexion->prepare($queryDoc);
            
            foreach ($input['documentos'] as $docId => $doc) {
                if (!empty($doc['titulo'])) {
                    $archivoNombre = null;
                    
                    // Procesar archivo si fue subido
                    if (!empty($input['documentos_files']['name'][$docId]['archivo'])) {
                        $fileData = [
                            'name' => $input['documentos_files']['name'][$docId]['archivo'],
                            'type' => $input['documentos_files']['type'][$docId]['archivo'],
                            'tmp_name' => $input['documentos_files']['tmp_name'][$docId]['archivo'],
                            'error' => $input['documentos_files']['error'][$docId]['archivo'],
                            'size' => $input['documentos_files']['size'][$docId]['archivo']
                        ];
                        
                        $archivoNombre = subirArchivo($fileData, $uploadDir, $allowedTypes, $maxFileSize);
                    }
                    
                    $stmtDoc->execute([
                        $ofertaId,
                        $doc['titulo'],
                        $doc['descripcion'] ?? null,
                        $archivoNombre
                    ]);
                    
                    $documentosProcesados++;
                }
            }
        }
        
        // Confirmar transacción
        $conexion->commit();
        
        // Respuesta exitosa
        echo json_encode([
            'success' => true,
            'id' => $ofertaId,
            'message' => 'Proceso creado correctamente',
            'estado' => 'ACTIVO',
            'documentos_procesados' => $documentosProcesados,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conexion->rollBack();
        sendError($e->getMessage(), 500);
    }
}

/**
 * Maneja solicitudes PUT (actualizar ofertas)
 */
function handlePutRequest($conexion, $input) {
    if (empty($input['action'])) {
        sendError("Acción no especificada");
    }
    
    switch ($input['action']) {
        case 'publicar':
            publicarOferta($conexion, $input);
            break;
            
        default:
            sendError("Acción no válida: " . $input['action']);
    }
}

/**
 * Publica una oferta (cambia estado a PUBLICADO)
 */
function publicarOferta($conexion, $input) {
    if (empty($input['id'])) {
        sendError("ID no proporcionado");
    }
    
    // Obtener información actual de la oferta
    $stmt = $conexion->prepare("SELECT id, estado, fecha_inicio, fecha_cierre FROM ofertas WHERE id = ?");
    $stmt->execute([$input['id']]);
    $oferta = $stmt->fetch();
    
    if (!$oferta) {
        sendError("Proceso no encontrado con ID: " . $input['id']);
    }
    
    // Validar estado actual
    if ($oferta['estado'] !== 'ACTIVO') {
        sendError("Solo se pueden publicar procesos en estado ACTIVO. Estado actual: " . $oferta['estado']);
    }
    
    // Validar fechas
    $now = new DateTime();
    $fechaInicio = new DateTime($oferta['fecha_inicio']);
    $fechaCierre = new DateTime($oferta['fecha_cierre']);
    
    if ($fechaInicio < $now) {
        sendError("La fecha de inicio ya pasó (" . $oferta['fecha_inicio'] . ")");
    }
    
    if ($fechaCierre <= $fechaInicio) {
        sendError("La fecha de cierre debe ser posterior a la de inicio");
    }
    
    // Actualizar estado
    $query = "UPDATE ofertas SET estado = 'PUBLICADO' WHERE id = ?";
    $stmt = $conexion->prepare($query);
    
    if (!$stmt->execute([$input['id']])) {
        sendError("Error al actualizar el estado en la base de datos", 500);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Proceso publicado correctamente',
        'data' => [
            'id' => $input['id'],
            'estado_anterior' => $oferta['estado'],
            'nuevo_estado' => 'PUBLICADO',
            'fecha_inicio' => $oferta['fecha_inicio'],
            'fecha_cierre' => $oferta['fecha_cierre']
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>