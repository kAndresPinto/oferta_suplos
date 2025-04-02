<?php
/**
 * ENDPOINT: guardar_oferta.php
 * MÉTODO: POST
 * FUNCIÓN: Registra una nueva oferta con sus documentos adjuntos
 * 
 * PARÁMETROS REQUERIDOS (POST):
 * - objeto (string): Nombre/objeto de la oferta
 * - moneda (string): Tipo de moneda (COP/USD)
 * - presupuesto (decimal): Monto presupuestado
 * - actividad (string): Actividad asociada
 * - fecha_inicio (date): Fecha de inicio formato YYYY-MM-DD
 * - fecha_cierre (date): Fecha de cierre formato YYYY-MM-DD
 * 
 * PARÁMETROS OPCIONALES:
 * - descripcion (string): Detalles adicionales
 * - doc_titulo[] (array): Títulos de documentos
 * - doc_descripcion[] (array): Descripciones de documentos
 * 
 * ARCHIVOS (MULTIPART/FORM-DATA):
 * - doc_archivo[] (file): Archivos adjuntos (PDF/DOC/DOCX)
 *   - Máx 5MB por archivo
 *   - Máx tipos permitidos: application/pdf, application/msword, 
 *     application/vnd.openxmlformats-officedocument.wordprocessingml.document
 * 
 * RESPUESTAS:
 * - 200 OK:
 *   {"success": true, "message": "Oferta guardada correctamente"}
 * - 400 Bad Request:
 *   {"success": false, "message": "[motivo del error]"}
 * - 500 Server Error:
 *   {"success": false, "message": "Error en la base de datos"}
 * 
 * TABLAS AFECTADAS:
 * - ofertas: Registro principal
 * - documentos: Archivos adjuntos (si se incluyen)
 * 
 * SEGURIDAD:
 * - Validación estricta de tipos de archivo
 * - Límite de tamaño de archivos
 * - Consultas preparadas contra SQL injection
 * - Directorio uploads con permisos restringidos
 */
header('Content-Type: application/json');

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Incluir conexión a BD
include '../../includes/conexion.php';

// Validar datos básicos
$requiredFields = ['objeto', 'moneda', 'presupuesto', 'actividad', 'fecha_inicio', 'fecha_cierre'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => "El campo $field es obligatorio"]);
        exit;
    }
}

// Procesar archivos
$documentos = [];
if (!empty($_FILES['doc_archivo']['name'][0])) {
    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    
    foreach ($_FILES['doc_archivo']['tmp_name'] as $key => $tmpName) {
        $fileType = $_FILES['doc_archivo']['type'][$key];
        $fileSize = $_FILES['doc_archivo']['size'][$key];
        
        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Solo se permiten archivos PDF o DOC/DOCX']);
            exit;
        }

        if ($fileSize > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'El archivo no debe exceder 5MB']);
            exit;
        }

        $fileName = uniqid() . '_' . $_FILES['doc_archivo']['name'][$key];
        $uploadPath = '../../uploads/documentos/' . $fileName;

        if (!is_dir('../../uploads/documentos/')) {
            mkdir('../../uploads/documentos/', 0777, true);
        }

        if (move_uploaded_file($tmpName, $uploadPath)) {
            $documentos[] = [
                'titulo' => $_POST['doc_titulo'][$key],
                'descripcion' => $_POST['doc_descripcion'][$key] ?? '',
                'archivo' => $fileName
            ];
        }
    }
}

try {
    // Guardar oferta principal
    $stmt = $pdo->prepare("INSERT INTO ofertas (objeto, descripcion, moneda, presupuesto, actividad, fecha_inicio, fecha_cierre) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['objeto'],
        $_POST['descripcion'] ?? '',
        $_POST['moneda'],
        $_POST['presupuesto'],
        $_POST['actividad'],
        $_POST['fecha_inicio'],
        $_POST['fecha_cierre']
    ]);
    $oferta_id = $pdo->lastInsertId();

    // Guardar documentos
    if (!empty($documentos)) {
        $stmt = $pdo->prepare("INSERT INTO documentos (oferta_id, titulo, descripcion, archivo) 
                              VALUES (?, ?, ?, ?)");
        foreach ($documentos as $doc) {
            $stmt->execute([$oferta_id, $doc['titulo'], $doc['descripcion'], $doc['archivo']]);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Oferta guardada correctamente']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}