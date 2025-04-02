/**
 * FUNCIÓN: publicarOferta(id)
 * DESCRIPCIÓN: Publica una oferta mediante una solicitud PUT al backend
 * 
 * PARÁMETROS:
 * @param {number} id - ID de la oferta a publicar
 * 
 * ENDPOINT:
 * - Método: PUT
 * - Ruta: ../backend/api/ofertas.php
 * - Body: { id: number, action: 'publicar' }
 * 
 * COMPORTAMIENTO:
 * 1. Envía solicitud PUT con el ID
 * 2. Recarga la página al completarse (éxito o error)
 * 
 * MEJORAS RECOMENDADAS:
 * - Agregar manejo de errores visual
 * - Mostrar spinner durante la carga
 * - Validar ID antes del envío
 */

/**
 * FUNCIÓN: agregarDocumento()
 * DESCRIPCIÓN: Crea campos dinámicos para agregar documentos adjuntos
 * 
 * ESTRUCTURA GENERADA:
 * <div class="documento">
 *   <input type="text" name="documentos[N][titulo]" placeholder="Título">
 *   <textarea name="documentos[N][descripcion]" placeholder="Descripción"></textarea>
 * </div>
 * 
 * DONDE:
 * - N = índice secuencial basado en cantidad existente
 * 
 * UBICACIÓN:
 * - Se agrega al contenedor con ID 'documentos'
 * 
 * MEJORAS RECOMENDADAS:
 * - Agregar campo para subir archivos
 * - Botón para eliminar documentos
 * - Validación de campos requeridos
 */
// Publicar oferta (AJAX vanilla)
function publicarOferta(id) {
    fetch(`../backend/api/ofertas.php`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, action: 'publicar' })
    })
    .then(response => location.reload());
}

// Agregar campos de documentos dinámicos
function agregarDocumento() {
    const contenedor = document.getElementById('documentos');
    const count = contenedor.children.length;
    const nuevoDoc = document.createElement('div');
    nuevoDoc.className = 'documento';
    nuevoDoc.innerHTML = `
        <input type="text" name="documentos[${count}][titulo]" placeholder="Título">
        <textarea name="documentos[${count}][descripcion]" placeholder="Descripción"></textarea>
    `;
    contenedor.appendChild(nuevoDoc);
}