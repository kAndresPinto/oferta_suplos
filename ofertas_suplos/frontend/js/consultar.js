/**
 * ARCHIVO: consultar.js
 * FUNCIÓN: Configuración de DataTables y eventos para la página de consulta
 * 
 * DEPENDENCIAS:
 * - jQuery 3.6.0+
 * - DataTables 1.11.5+
 * - DataTables Responsive
 * - Tooltips de Bootstrap (opcional)
 * 
 * CONFIGURACIÓN DATATABLE:
 * - Internacionalización: Español (es-ES)
 * - Columnas no ordenables: [5, 6] (Documentos y Acciones)
 * - Responsive: true (adaptación a móviles)
 * 
 * EVENTOS:
 * - btn-limpiar: Resetea formulario y recarga la página
 * - Tooltips: Muestra hints en botones deshabilitados
 * 
 * SELECTORES:
 * - #tabla-procesos: Tabla principal
 * - #form-filtros: Formulario de filtrado
 * - #btn-limpiar: Botón de limpieza
 * 
 * EJEMPLO DE USO HTML:
 * <table id="tabla-procesos">
 *   <thead>...</thead>
 *   <tbody>...</tbody>
 * </table>
 * 
 * MEJORAS RECOMENDADAS:
 * 1. Carga diferida (defer) para mejor performance
 * 2. Manejo de errores en carga de DataTables
 * 3. Eventos personalizados para acciones complejas
 */
$(document).ready(function() {
    // Inicializar DataTable con configuración básica
    $('#tabla-procesos').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
        },
        responsive: true,
        columnDefs: [
            { orderable: false, targets: [5, 6] } // Hacer no ordenables las columnas de Documentos y Descargar
        ],
        initComplete: function() {
            // Agregar evento para limpiar filtros
            $('#btn-limpiar').click(function() {
                $('#form-filtros')[0].reset();
                window.location.href = window.location.pathname;
            });
        }
    });

    // Mostrar tooltips para botones deshabilitados
    $('[title]').tooltip({
        placement: 'top',
        trigger: 'hover'
    });
});