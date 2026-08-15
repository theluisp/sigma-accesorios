/**
 * Llena el modal de "ver detalle" de producto (#producto-modal, componente
 * Modal de Bootstrap) con los data-* del botón que lo disparó. Bootstrap se
 * encarga de abrir/cerrar/centrar/backdrop — aquí solo ponemos el contenido,
 * usando su evento show.bs.modal y event.relatedTarget (el botón clickeado).
 */
document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('producto-modal');
    if (!modalEl) {
        return;
    }

    const imagenEl = document.getElementById('producto-modal-imagen');
    const tituloEl = document.getElementById('producto-modal-title');
    const precioEl = document.getElementById('producto-modal-precio');
    const descripcionEl = document.getElementById('producto-modal-descripcion');

    modalEl.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (!trigger) {
            return;
        }

        imagenEl.src = trigger.dataset.imagen || '';
        imagenEl.alt = trigger.dataset.nombre || '';
        tituloEl.textContent = trigger.dataset.nombre || '';
        precioEl.textContent = trigger.dataset.precio || '';
        descripcionEl.textContent = trigger.dataset.descripcion || 'Sin descripción disponible.';
    });
});
