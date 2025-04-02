<?php 

/**
 * ARCHIVO: crear_oferta.php
 * FUNCIÓN: Interfaz para creación de nuevas ofertas/procesos con gestión documental
 * 
 * DEPENDENCIAS:
 * 
 * - Font Awesome 6+ (íconos)
 * - jQuery 3.6+ (AJAX y manipulación DOM)
 * 
 * ESTRUCTURA:
 * 1. Formulario multiparte con 3 secciones:
 *    - Información básica
 *    - Cronograma y estados
 *    - Documentación adjunta
 * 2. Integración con APIs backend:
 *    - obtener_actividades.php (GET)
 *    - ofertas.php (POST)
 * 
 * VALIDACIONES CLIENTE:
 * - Campos requeridos
 * - Fechas coherentes (cierre > inicio)
 * - Archivos adjuntos válidos
 * - Tipos MIME permitidos (PDF, Word, Excel)
 * 
 * FLUJO DE ESTADOS:
 * ACTIVO → PUBLICADO → EVALUACIÓN (transición automática por fechas)
 */
// Configuración de errores para desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/header.php'; 
?>

<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<div class="container">
    <h1>Crear Nueva Oferta</h1>
    
    <form id="form-oferta" enctype="multipart/form-data">
        <!-- Sección 1: Información Básica -->
        <div class="form-section">
            <div class="section-header">
                <h2>Información Básica</h2>
            </div>
            <div class="section-body">
                <div class="form-group">
                    <label for="objeto">Objeto*</label>
                    <input type="text" id="objeto" name="objeto" required>
                </div>
                
                <div class="form-group">
                    <label for="descripcion">Descripción/Alcance</label>
                    <textarea id="descripcion" name="descripcion" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group col">
                        <label for="moneda">Moneda*</label>
                        <select id="moneda" name="moneda" required>
                            <option value="COP">COP (Pesos Colombianos)</option>
                            <option value="USD">USD (Dólares Americanos)</option>
                        </select>
                    </div>
                    <div class="form-group col">
                        <label for="presupuesto">Presupuesto*</label>
                        <input type="number" id="presupuesto" name="presupuesto" min="0" step="0.01" required>
                    </div>
                    <div class="form-group col">
                        <label for="actividad">Actividad*</label>
                        <select id="actividad" name="actividad" required>
                            <option value="">Cargando actividades...</option>
                        </select>
                        <small class="form-text">
                            <a href="https://www.colombiacompra.gov.co/sites/cce_public/files/cce_clasificador/clasificador_de_bienes_y_servicios_v14_1.xls" 
                               target="_blank">
                               Ver clasificador completo
                            </a>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección 2: Cronograma -->
        <div class="form-section">
            <div class="section-header">
                <div>
                    <h2><i class="fas fa-calendar-alt"></i> Cronograma</h2>
                    <p class="section-description">Los estados cambiarán automáticamente según las fechas programadas</p>
                </div>
            </div>
            <div class="section-body">
                <div class="form-row">
                    <div class="form-group col">
                        <label for="fecha_inicio">
                            <i class="fas fa-play-circle"></i> Fecha/Hora Inicio*
                        </label>
                        <input type="datetime-local" id="fecha_inicio" name="fecha_inicio" required 
                               min="<?= date('Y-m-d\TH:i') ?>" class="form-control">
                        <small class="form-text">
                            <i class="fas fa-info-circle"></i> Estado cambiará a <span class="badge badge-success">PUBLICADO</span> al llegar esta fecha
                        </small>
                    </div>
                    <div class="form-group col">
                        <label for="fecha_cierre">
                            <i class="fas fa-stop-circle"></i> Fecha/Hora Cierre*
                        </label>
                        <input type="datetime-local" id="fecha_cierre" name="fecha_cierre" required class="form-control">
                        <small class="form-text">
                            <i class="fas fa-info-circle"></i> Estado cambiará a <span class="badge badge-warning">EVALUACIÓN</span> al llegar esta fecha
                        </small>
                    </div>
                </div>
                <div class="info-box">
                    <h4><i class="fas fa-sync-alt"></i> Transición de Estados Automática</h4>
                    <ul>
                        <li><span class="badge badge-primary">ACTIVO</span> Estado inicial al crear la oferta</li>
                        <li><span class="badge badge-success">PUBLICADO</span> Cuando se alcanza la fecha de inicio</li>
                        <li><span class="badge badge-warning">EVALUACIÓN</span> Cuando se alcanza la fecha de cierre</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Sección 3: Documentación -->
        <div class="form-section">
            <div class="section-header">
                <h2><i class="fas fa-file-alt"></i> Documentación</h2>
                <button type="button" class="btn btn-primary" id="btn-agregar-doc">
                    <i class="fas fa-plus"></i> Agregar Documento
                </button>
            </div>
            <div class="section-body" id="documentos-container">
                <!-- Documentos se agregarán aquí dinámicamente -->
            </div>
        </div>

        <!-- Acciones del formulario -->
        <div class="form-actions">
            <button type="submit" class="btn btn-success btn-lg">
                <i class="fas fa-save"></i> Guardar Oferta
            </button>
            <a href="index.php" class="btn btn-secondary btn-lg">
                <i class="fas fa-arrow-left"></i> Volver al listado
            </a>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Cargar actividades al iniciar
    cargarActividades();
    
    // Contador para documentos
    let docCounter = 0;
    
    // Función para cargar actividades desde la API
    function cargarActividades() {
        $.ajax({
            url: '../backend/api/obtener_actividades.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                const select = $('#actividad');
                select.empty().append('<option value="">Seleccionar actividad...</option>');
                
                if (data.success && data.data && data.data.length > 0) {
                    $.each(data.data, function(index, actividad) {
                        select.append(
                            $('<option></option>')
                                .val(actividad.codigo)
                                .text(actividad.codigo + ' - ' + actividad.nombre)
                        );
                    });
                } else {
                    select.empty().append('<option value="">No hay actividades disponibles</option>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar actividades:', error);
                $('#actividad').empty().append(
                    '<option value="">Error al cargar actividades</option>'
                );
                alert('Error al cargar las actividades. Por favor recargue la página.');
            }
        });
    }
    
    // Función para agregar documento
    function agregarDocumento() {
        const container = $('#documentos-container');
        const docId = docCounter++;
        
        const docHTML = `
        <div class="documento mb-3 p-3 border rounded" id="doc-${docId}">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">Documento #${docId + 1}</h5>
                <button type="button" class="btn btn-sm btn-danger" onclick="eliminarDocumento(${docId})">
                    <i class="fas fa-times"></i> Eliminar
                </button>
            </div>
            <div class="mb-3">
                <label class="form-label">Título del Documento*</label>
                <input type="text" class="form-control" name="documentos[${docId}][titulo]" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea class="form-control" name="documentos[${docId}][descripcion]" rows="2"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Archivo (PDF, Word, Excel)*</label>
                <input type="file" class="form-control" name="documentos[${docId}][archivo]" 
                       accept=".pdf,.doc,.docx,.xls,.xlsx" required>
                <small class="form-text text-muted">Tamaño máximo: 5MB. Formatos permitidos: PDF, Word, Excel</small>
            </div>
        </div>`;
        
        container.append(docHTML);
    }
    
    // Eliminar documento
    window.eliminarDocumento = function(id) {
        if (confirm('¿Estás seguro de eliminar este documento?')) {
            $(`#doc-${id}`).remove();
        }
    };
    
    // Validar fechas al cambiar
    $('[name="fecha_inicio"]').change(function() {
        const fechaCierre = $('[name="fecha_cierre"]');
        fechaCierre.attr('min', $(this).val());
    });
    
    // Agregar primer documento al cargar
    agregarDocumento();
    
    // Botón agregar documento
    $('#btn-agregar-doc').click(agregarDocumento);
    
    // Enviar formulario
    $('#form-oferta').submit(function(e) {
        e.preventDefault();
        
        // Validación de fechas
        const fechaInicio = new Date($('#fecha_inicio').val());
        const fechaCierre = new Date($('#fecha_cierre').val());
        
        if (fechaCierre <= fechaInicio) {
            alert('Error: La fecha de cierre debe ser posterior a la de inicio');
            return;
        }
        
        // Validar actividad seleccionada
        if (!$('#actividad').val()) {
            alert('Error: Debes seleccionar una actividad');
            return;
        }
        
        // Validar que al menos un documento tenga archivo
        let archivosValidos = true;
        $('input[type="file"]').each(function() {
            if (!$(this).prop('files')[0]) {
                archivosValidos = false;
                return false; // Salir del each
            }
        });
        
        if (!archivosValidos) {
            alert('Error: Todos los documentos deben tener un archivo adjunto');
            return;
        }
        
        // Crear FormData para enviar archivos
        const formData = new FormData(this);
        
        // Mostrar loader
        const submitBtn = $(this).find('[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
        submitBtn.prop('disabled', true);
        
        $.ajax({
            url: '../backend/api/ofertas.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    alert(`Oferta #${data.id} creada exitosamente con ${data.documentos_procesados} documentos`);
                    window.location.href = 'index.php';
                } else {
                    throw new Error(data.message || 'Error al crear la oferta');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                let errorMsg = 'Error en el servidor';
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg = response.message || errorMsg;
                } catch (e) {
                    errorMsg = xhr.responseText || errorMsg;
                }
                
                alert(`Error: ${errorMsg}`);
            },
            complete: function() {
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>