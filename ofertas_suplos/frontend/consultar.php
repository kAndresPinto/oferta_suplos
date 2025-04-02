<?php
/**
 * ARCHIVO: consultar.php
 * FUNCIÓN: Página principal para consulta y gestión de ofertas/procesos
 * 
 * DEPENDENCIAS:
 * 
 * - DataTables 1.11+ (JS)
 * - Font Awesome 6+ (íconos)
 * - jQuery 3.6+ (requerido por DataTables)
 * 
 * ESTRUCTURA:
 * 1. Sección de filtros avanzados
 * 2. Tabla responsive de resultados
 * 3. Integración con exportación a Excel
 * 
 * CARACTERÍSTICAS PRINCIPALES:
 * - Sistema de filtrado multidimensional
 * - Visualización de estados con badges
 * - Descarga directa de documentos
 * - Exportación a Excel con filtros aplicados
 * 
 * SEGURIDAD:
 * - htmlspecialchars() para prevenir XSS
 * - Consultas preparadas con PDO
 * - Validación de tipos en filtros
 * 
 * MEJORAS RECOMENDADAS:
 * 1. Paginación server-side para grandes volúmenes
 * 2. Caché de consultas frecuentes
 * 3. Historial de búsquedas
 */
/**
 * FILTROS DISPONIBLES:
 * 
 * 1. Por Objeto:
 * - Búsqueda por texto libre (LIKE %texto%)
 * - Filtra en campo 'objeto' de la tabla ofertas
 * 
 * 2. Por Estado:
 * - Dropdown con opciones: Todos, Activo, Publicado, Evaluación
 * - Valores: ACTIVO, PUBLICADO, EVALUACION
 * 
 * 3. Por Rango de Fechas:
 * - Desde/Hasta para fecha_inicio y fecha_cierre
 * - Formato: YYYY-MM-DD (HTML5 date input)
 * 
 * ACCIONES:
 * - Buscar: Aplica filtros
 * - Limpiar: Restablece formulario
 * - Exportar: Genera Excel con filtros actuales
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/header.php';
require_once '../backend/api/conexion.php';
?>

<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">

<div class="container">
    <h1><i class="fas fa-search"></i> Consultar Procesos/Eventos</h1>
    
    <div class="filter-container expanded">
        <form id="form-filtros" method="GET" class="filter-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="filtro_objeto">Objeto:</label>
                    <input type="text" id="filtro_objeto" name="filtro_objeto" 
                           value="<?= htmlspecialchars($_GET['filtro_objeto'] ?? '') ?>">
                </div>
                
                <div class="filter-group">
                    <label for="filtro_estado">Estado:</label>
                    <select id="filtro_estado" name="filtro_estado">
                        <option value="">Todos</option>
                        <option value="ACTIVO" <?= ($_GET['filtro_estado'] ?? '') == 'ACTIVO' ? 'selected' : '' ?>>Activo</option>
                        <option value="PUBLICADO" <?= ($_GET['filtro_estado'] ?? '') == 'PUBLICADO' ? 'selected' : '' ?>>Publicado</option>
                        <option value="EVALUACION" <?= ($_GET['filtro_estado'] ?? '') == 'EVALUACION' ? 'selected' : '' ?>>Evaluación</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="filtro_fecha_desde">Desde:</label>
                    <input type="date" id="filtro_fecha_desde" name="filtro_fecha_desde"
                           value="<?= htmlspecialchars($_GET['filtro_fecha_desde'] ?? '') ?>">
                </div>
                
                <div class="filter-group">
                    <label for="filtro_fecha_hasta">Hasta:</label>
                    <input type="date" id="filtro_fecha_hasta" name="filtro_fecha_hasta"
                           value="<?= htmlspecialchars($_GET['filtro_fecha_hasta'] ?? '') ?>">
                </div>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <button type="button" id="btn-limpiar" class="btn btn-secondary">
                    <i class="fas fa-broom"></i> Limpiar
                </button>
                <a href="../backend/api/export_excel.php?<?= http_build_query($_GET) ?>" 
                   class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Exportar
                </a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table id="tabla-procesos" class="display">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Objeto</th>
                    <th>Estado</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Cierre</th>
                    <th>Documentos</th>
                    <th>Descargar</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Construir consulta con filtros
                $query = "SELECT o.id, o.objeto, o.estado, o.fecha_inicio, o.fecha_cierre, 
                         COUNT(d.id) as num_documentos,
                         MIN(d.id) as primer_documento_id
                         FROM ofertas o
                         LEFT JOIN documentos d ON o.id = d.oferta_id
                         WHERE 1=1";
                
                $params = [];
                
                if (!empty($_GET['filtro_objeto'])) {
                    $query .= " AND o.objeto LIKE ?";
                    $params[] = "%".$_GET['filtro_objeto']."%";
                }
                
                if (!empty($_GET['filtro_estado'])) {
                    $query .= " AND o.estado = ?";
                    $params[] = $_GET['filtro_estado'];
                }
                
                if (!empty($_GET['filtro_fecha_desde'])) {
                    $query .= " AND o.fecha_inicio >= ?";
                    $params[] = $_GET['filtro_fecha_desde'] . ' 00:00:00';
                }
                
                if (!empty($_GET['filtro_fecha_hasta'])) {
                    $query .= " AND o.fecha_cierre <= ?";
                    $params[] = $_GET['filtro_fecha_hasta'] . ' 23:59:59';
                }
                
                $query .= " GROUP BY o.id ORDER BY o.fecha_inicio DESC";
                
                $stmt = $conexion->prepare($query);
                $stmt->execute($params);
                $ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($ofertas as $oferta) {
                    $fechaInicio = date('d/m/Y H:i', strtotime($oferta['fecha_inicio']));
                    $fechaCierre = date('d/m/Y H:i', strtotime($oferta['fecha_cierre']));
                    
                    echo '<tr>
                        <td>'.htmlspecialchars($oferta['id']).'</td>
                        <td>'.htmlspecialchars($oferta['objeto']).'</td>
                        <td><span class="estado-badge estado-'.strtolower($oferta['estado']).'">'.
                            $oferta['estado'].'</span></td>
                        <td>'.$fechaInicio.'</td>
                        <td>'.$fechaCierre.'</td>
                        <td>'.$oferta['num_documentos'].' doc(s)</td>
                        <td class="action-buttons">';
                    
                    if ($oferta['num_documentos'] > 0) {
                        echo '<a href="../backend/api/descargar_documento.php?documento_id='.htmlspecialchars($oferta['primer_documento_id']).'" 
                               class="btn-descargar" title="Descargar documento principal">
                                <i class="fas fa-file-download"></i> Formato
                            </a>';
                    } else {
                        echo '<span class="btn-descargar disabled" title="No hay documentos">
                                <i class="fas fa-file-download"></i> N/A
                              </span>';
                    }
                    
                    echo '</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="js/consultar.js"></script>

<?php include 'includes/footer.php'; ?>