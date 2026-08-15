/**
 * Modal de "ver detalle" de producto. Cualquier botón con la clase
 * js-ver-detalle y los atributos data-nombre / data-descripcion / data-precio
 * / data-imagen abre el modal #producto-modal (definido en base.html.twig)
 * con esos datos. Sin dependencias externas.
 */
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('producto-modal');
    if (!modal) {
        return;
    }

    const imagenEl = document.getElementById('producto-modal-imagen');
    const tituloEl = document.getElementById('producto-modal-title');
    const precioEl = document.getElementById('producto-modal-precio');
    const descripcionEl = document.getElementById('producto-modal-descripcion');

    function abrirModal(datos) {
        imagenEl.src = datos.imagen || '';
        imagenEl.alt = datos.nombre || '';
        tituloEl.textContent = datos.nombre || '';
        precioEl.textContent = datos.precio || '';
        descripcionEl.textContent = datos.descripcion || 'Sin descripción disponible.';

        modal.hidden = false;
        document.body.classList.add('modal-open');
    }

    function cerrarModal() {
        modal.hidden = true;
        document.body.classList.remove('modal-open');
    }

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('.js-ver-detalle');
        if (trigger) {
            abrirModal({
                nombre: trigger.dataset.nombre,
                descripcion: trigger.dataset.descripcion,
                precio: trigger.dataset.precio,
                imagen: trigger.dataset.imagen,
            });
            return;
        }

        if (event.target.closest('[data-modal-close]')) {
            cerrarModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) {
            cerrarModal();
        }
    });
});
