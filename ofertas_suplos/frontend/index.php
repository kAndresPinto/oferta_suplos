<?php 

/**
 * ARCHIVO: index.php
 * FUNCIÓN: Página principal para gestión de procesos/eventos con funcionalidades CRUD
 * 
 * DEPENDENCIAS:
 * - Font Awesome 6+ (íconos)
 * - publicar.js (para acciones de publicación)
 * 
 * ESTRUCTURA PRINCIPAL:
 * 1. Acciones rápidas (Crear, Copiar, Consultar)
 * 2. Sistema de filtrado
 * 3. Tabla de resultados con acciones
 * 
 * CARACTERÍSTICAS:
 * - Actualización automática de estados
 * - Filtrado por objeto y estado
 * - Exportación a Excel
 * - Acción de publicación condicional
 */

/**
 * SECCIÓN DE ACCIONES RÁPIDAS:
 * - Crear: Redirige a crear_oferta.php
 * - Copiar: (Funcionalidad pendiente)
 * - Consultar: Redirige a consultar.php
 */

/**
 * SISTEMA DE FILTRADO:
 * - Por Objeto: Búsqueda por texto (LIKE)
 * - Por Estado: Dropdown (ACTIVO/PUBLICADO/EVALUACION)
 * - Exportación: Genera Excel con filtros aplicados
 */

/**
 * TABLA DE RESULTADOS:
 * Columnas:
 * 1. ID
 * 2. Objeto
 * 3. Estado (con badges visuales)
 * 4. Fechas (Inicio/Cierre formateadas)
 * 5. Acciones (Botón Publicar condicional)
 * 
 * Estados:
 * - ACTIVO: badge-primary (azul)
 * - PUBLICADO: badge-success (verde)
 * - EVALUACION: badge-warning (amarillo)
 */
/**
 * CONFIGURACIÓN BACKEND:
 * - Incluye actualización automática de estados (actualizar_estados.php)
 * - Consulta preparada con filtros opcionales
 * - Protección XSS con htmlspecialchars()
 * 
 * ENDPOINTS RELACIONADOS:
 * - export_excel.php: Exportación de datos
 * - publicar.js: Manejo de publicación
 * 
 * SEGURIDAD:
 * - Validación de parámetros GET
 * - Consultas preparadas
 * - Escape de outputs
 */

/**
 * MEJORAS RECOMENDADAS:
 * 1. Paginación de resultados
 * 2. Ordenamiento por columnas
 * 3. Búsqueda avanzada
 * 4. Vista de calendario alternativo
 * 5. Implementar funcionalidad de "Copiar"
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/header.php'; 
?>

<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<div class="container">
    <h1>Procesos / Eventos</h1>
    
    <div class="process-actions">
        <a href="crear_oferta.php" class="process-card">
            <i class="fas fa-plus-circle"></i>
            <span>Crear</span>
        </a>
        <div class="process-card">
            <i class="fas fa-copy"></i>
            <span>Copiar</span>
        </div>
        <a href="consultar.php" class="process-card">
    <i class="fas fa-search"></i>
    <span>Consultar</span>
</a>
    </div>
    
    <div class="filter-container">
        <form method="GET" action="" class="filter-form">
            <input type="text" name="filtro_objeto" placeholder="Buscar por objeto" 
                   value="<?= htmlspecialchars($_GET['filtro_objeto'] ?? '') ?>">
            <select name="filtro_estado">
                <option value="">Todos</option>
                <option value="ACTIVO" <?= ($_GET['filtro_estado'] ?? '') == 'ACTIVO' ? 'selected' : '' ?>>Activo</option>
                <option value="PUBLICADO" <?= ($_GET['filtro_estado'] ?? '') == 'PUBLICADO' ? 'selected' : '' ?>>Publicado</option>
                <option value="EVALUACION" <?= ($_GET['filtro_estado'] ?? '') == 'EVALUACION' ? 'selected' : '' ?>>Evaluación</option>
            </select>
            <button type="submit">Buscar</button>
        </form>
        <a href="../backend/api/export_excel.php" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Exportar
        </a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Objeto</th>
                <th>Estado</th>
                <th>Fecha Inicio</th>
                <th>Fecha Cierre</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
            require_once '../backend/api/conexion.php';
            
            // Actualizar estados automáticamente antes de mostrar
            require_once '../backend/api/actualizar_estados.php';
            
            $filtro_objeto = $_GET['filtro_objeto'] ?? '';
            $filtro_estado = $_GET['filtro_estado'] ?? '';
            
            $query = "SELECT id, objeto, estado, fecha_inicio, fecha_cierre FROM ofertas WHERE 1=1";
            $params = [];
            
            if (!empty($filtro_objeto)) {
                $query .= " AND objeto LIKE ?";
                $params[] = "%$filtro_objeto%";
            }
            
            if (!empty($filtro_estado)) {
                $query .= " AND estado = ?";
                $params[] = $filtro_estado;
            }
            
            $query .= " ORDER BY fecha_inicio ASC";
            
            $stmt = $conexion->prepare($query);
            $stmt->execute($params);
            $ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($ofertas as $oferta) {
                $fechaInicio = date('d/m/Y H:i', strtotime($oferta['fecha_inicio']));
                $fechaCierre = date('d/m/Y H:i', strtotime($oferta['fecha_cierre']));
                $puedePublicar = ($oferta['estado'] == 'ACTIVO');
                
                echo '<tr>
                    <td>'.htmlspecialchars($oferta['id']).'</td>
                    <td>'.htmlspecialchars($oferta['objeto']).'</td>
                    <td><span class="estado-badge estado-'.strtolower($oferta['estado']).'">'.$oferta['estado'].'</span></td>
                    <td>'.$fechaInicio.'</td>
                    <td>'.$fechaCierre.'</td>
                    <td class="action-buttons">
                        <button class="btn-publicar '.($puedePublicar ? '' : 'disabled').'" 
                                data-id="'.htmlspecialchars($oferta['id']).'" '.
                                (!$puedePublicar ? 'disabled' : '').'>
                            <i class="fas fa-share-square"></i> Publicar
                        </button>
                    </td>
                </tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<script src="js/publicar.js"></script>

<?php include 'includes/footer.php'; ?>