/**
 * ARCHIVO: publicar.js
 * FUNCIÓN: Maneja la publicación de procesos/ofetas via API REST
 * 
 * ENDPOINT: ../backend/api/ofertas.php (Método PUT)
 * 
 * FLUJO PRINCIPAL:
 * 1. Configura listeners en botones .btn-publicar
 * 2. Al hacer click:
 *    - Muestra confirmación
 *    - Bloquea el botón durante la operación
 *    - Envía solicitud PUT al backend
 *    - Maneja respuesta/errores
 *    - Recarga la página si es exitoso
 * 
 * ESTRUCTURA DE DATOS:
 * - Request (PUT):
 *   {
 *     "action": "publicar",
 *     "id": [number]
 *   }
 * - Response (éxito):
 *   {
 *     "success": true,
 *     "message": [string],
 *     "data": { ... }
 *   }
 * - Response (error):
 *   {
 *     "success": false,
 *     "message": [string]
 *   }
 * 
 * DEPENDENCIAS:
 * - Font Awesome 5+ (para íconos de carga)
 * - Fetch API (navegadores modernos)
 * 
 * MEJORAS RECOMENDADAS:
 * 1. Toast notifications en lugar de alerts
 * 2. Reintentos automáticos en fallos de red
 * 3. Animación suave al recargar datos
 * 
 * EJEMPLO DE USO HTML:
 * <button class="btn-publicar" data-id="123">
 *   <i class="fas fa-share-square"></i> Publicar
 * </button>
 */
async function publicarProceso(id) {
    try {
        const response = await fetch(`../backend/api/ofertas.php`, {  // Quitamos ?id=${id} de la URL
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                action: 'publicar',
                id: id  // Ahora el ID va en el body como JSON
            })
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Error en la solicitud');
        }

        return await response.json();
    } catch (error) {
        console.error('Error en publicarProceso:', error);
        throw error;
    }
}

// Configurar los botones de publicación
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.btn-publicar').forEach(btn => {
        btn.addEventListener('click', async function() {
            if (!confirm('¿Estás seguro de publicar este proceso?')) return;
            
            const id = this.dataset.id;
            const btnElement = this;
            
            btnElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publicando...';
            btnElement.disabled = true;
            
            try {
                const result = await publicarProceso(id);
                alert(result.message);
                location.reload();
            } catch (error) {
                alert('Error: ' + error.message);
                btnElement.innerHTML = '<i class="fas fa-share-square"></i> Publicar';
                btnElement.disabled = false;
            }
        });
    });
});