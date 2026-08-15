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
    const whatsappEl = document.getElementById('producto-modal-whatsapp');

    modalEl.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (!trigger) {
            return;
        }

        const nombre = trigger.dataset.nombre || '';

        imagenEl.src = trigger.dataset.imagen || '';
        imagenEl.alt = nombre;
        tituloEl.textContent = nombre;
        precioEl.textContent = trigger.dataset.precio || '';
        descripcionEl.textContent = trigger.dataset.descripcion || 'Sin descripción disponible.';

        // Botón "Preguntar por WhatsApp": manda un mensaje precargado con el
        // nombre del producto, para bajar la fricción de preguntar/comprar.
        const whatsappBase = trigger.dataset.whatsapp;
        if (whatsappEl) {
            if (whatsappBase) {
                const mensaje = `Hola, me interesa este producto: ${nombre}`;
                whatsappEl.href = `${whatsappBase}?text=${encodeURIComponent(mensaje)}`;
                whatsappEl.hidden = false;
            } else {
                whatsappEl.hidden = true;
            }
        }
    });
});
