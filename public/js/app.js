document.addEventListener('DOMContentLoaded', () => {
    /* =========================================================
       Modal de detalle de producto (#producto-modal)
       ========================================================= */
    const modalEl = document.getElementById('producto-modal');
    if (modalEl) {
        const imagenEl = document.getElementById('producto-modal-imagen');
        const tituloEl = document.getElementById('producto-modal-title');
        const precioEl = document.getElementById('producto-modal-precio');
        const descripcionEl = document.getElementById('producto-modal-descripcion');
        const whatsappEl = document.getElementById('producto-modal-whatsapp');
        const carritoBtnEl = document.getElementById('producto-modal-carrito');

        modalEl.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (!trigger) { return; }
            const nombre = trigger.dataset.nombre || '';
            imagenEl.src = trigger.dataset.imagen || '';
            imagenEl.alt = nombre;
            tituloEl.textContent = nombre;
            precioEl.textContent = trigger.dataset.precio || '';
            descripcionEl.textContent = trigger.dataset.descripcion || 'Sin descripción disponible.';

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

            // El botón "Agregar al carrito" del modal reutiliza el mismo
            // patrón data-* que los botones de las tarjetas (js-carrito-agregar
            // + delegación de eventos abajo), así que solo hace falta copiar
            // los datos del trigger que abrió el modal hacia este botón.
            if (carritoBtnEl) {
                const precioNumero = trigger.dataset.precioNumero;
                if (precioNumero) {
                    carritoBtnEl.dataset.slug = trigger.dataset.slug || '';
                    carritoBtnEl.dataset.nombre = nombre;
                    carritoBtnEl.dataset.precioNumero = precioNumero;
                    carritoBtnEl.dataset.precio = trigger.dataset.precio || '';
                    carritoBtnEl.dataset.imagen = trigger.dataset.imagen || '';
                    carritoBtnEl.hidden = false;
                } else {
                    carritoBtnEl.hidden = true;
                }
            }
        });
    }

    /* =========================================================
       Carrito (MVP): 100% en el navegador vía localStorage, sin
       backend de pedidos todavía. El "checkout" de esta primera
       versión es mandar el resumen del pedido por WhatsApp — el
       sistema completo de pagos/envío se construirá después.
       ========================================================= */
    const CARRITO_KEY = 'sigma_carrito';

    const Carrito = {
        leer() {
            try {
                const raw = localStorage.getItem(CARRITO_KEY);
                const items = raw ? JSON.parse(raw) : [];
                return Array.isArray(items) ? items : [];
            } catch (error) {
                return [];
            }
        },

        guardar(items) {
            try {
                localStorage.setItem(CARRITO_KEY, JSON.stringify(items));
            } catch (error) {
                // Si localStorage no está disponible (modo privado, etc.) el
                // carrito simplemente no persiste entre recargas; no rompemos
                // la página por esto.
            }
            render();
        },

        agregar(item) {
            const items = this.leer();
            const existente = items.find((i) => i.slug === item.slug);
            if (existente) {
                existente.cantidad += 1;
            } else {
                items.push({ ...item, cantidad: 1 });
            }
            this.guardar(items);
        },

        actualizarCantidad(slug, cantidad) {
            let items = this.leer();
            if (cantidad <= 0) {
                items = items.filter((i) => i.slug !== slug);
            } else {
                const existente = items.find((i) => i.slug === slug);
                if (existente) {
                    existente.cantidad = cantidad;
                }
            }
            this.guardar(items);
        },

        quitar(slug) {
            const items = this.leer().filter((i) => i.slug !== slug);
            this.guardar(items);
        },

        vaciar() {
            this.guardar([]);
        },

        total() {
            return this.leer().reduce((suma, i) => suma + (Number(i.precioNumero) || 0) * i.cantidad, 0);
        },

        cantidadTotal() {
            return this.leer().reduce((suma, i) => suma + i.cantidad, 0);
        },
    };

    const formatoMoneda = (numero) => `$${Number(numero).toLocaleString('es-MX', { maximumFractionDigits: 0 })}`;

    // Aviso de "producto agregado al carrito" (#carrito-toast en
    // base.html.twig) — antes, dar clic en "Agregar" no mostraba ninguna
    // confirmación visible hasta que el usuario abría el carrito manualmente.
    // Reutiliza el componente Toast de Bootstrap (ya viene cargado, mismo
    // patrón que los Modal).
    function mostrarToastCarrito(nombre) {
        const toastEl = document.getElementById('carrito-toast');
        const textoEl = document.getElementById('carrito-toast-texto');
        if (!toastEl || !textoEl || typeof bootstrap === 'undefined') {
            return;
        }
        textoEl.textContent = `${nombre || 'Producto'} se agregó al carrito`;
        bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 2500 }).show();
    }

    function construirMensajeWhatsapp(items, total) {
        const lineas = items.map((i) => `• ${i.nombre} x${i.cantidad} — ${formatoMoneda((Number(i.precioNumero) || 0) * i.cantidad)}`);
        return [
            'Hola, quiero hacer este pedido:',
            '',
            ...lineas,
            '',
            `Total: ${formatoMoneda(total)}`,
        ].join('\n');
    }

    function render() {
        const items = Carrito.leer();
        const cantidadTotal = Carrito.cantidadTotal();
        const total = Carrito.total();

        // Badge del header
        document.querySelectorAll('.js-carrito-contador').forEach((badge) => {
            if (cantidadTotal > 0) {
                badge.textContent = String(cantidadTotal);
                badge.hidden = false;
            } else {
                badge.hidden = true;
            }
        });

        const contenedor = document.querySelector('.js-carrito-items');
        const vacioEl = document.querySelector('.js-carrito-vacio');
        const totalWrap = document.querySelector('.js-carrito-total-wrap');
        const totalEl = document.querySelector('.js-carrito-total');
        const vaciarBtn = document.querySelector('.js-carrito-vaciar');
        const whatsappEl = document.getElementById('carrito-whatsapp');

        if (!contenedor) { return; }

        if (items.length === 0) {
            contenedor.innerHTML = '';
            if (vacioEl) { vacioEl.hidden = false; }
            if (totalWrap) { totalWrap.hidden = true; }
            if (vaciarBtn) { vaciarBtn.hidden = true; }
            if (whatsappEl) { whatsappEl.hidden = true; }
            return;
        }

        if (vacioEl) { vacioEl.hidden = true; }
        if (totalWrap) { totalWrap.hidden = false; }
        if (vaciarBtn) { vaciarBtn.hidden = false; }

        contenedor.innerHTML = items.map((item) => `
            <div class="carrito-item" data-slug="${item.slug}">
                <img class="carrito-item__imagen" src="${item.imagen || ''}" alt="${item.nombre}">
                <div class="carrito-item__info">
                    <p class="carrito-item__nombre">${item.nombre}</p>
                    <p class="carrito-item__precio">${item.precio || formatoMoneda(item.precioNumero || 0)}</p>
                </div>
                <div class="carrito-item__cantidad">
                    <button type="button" class="carrito-item__paso js-carrito-restar" data-slug="${item.slug}" aria-label="Quitar uno">−</button>
                    <span class="carrito-item__cantidad-num">${item.cantidad}</span>
                    <button type="button" class="carrito-item__paso js-carrito-sumar" data-slug="${item.slug}" aria-label="Agregar uno">+</button>
                </div>
                <button type="button" class="carrito-item__quitar js-carrito-quitar" data-slug="${item.slug}" aria-label="Quitar ${item.nombre} del carrito">✕</button>
            </div>
        `).join('');

        if (totalEl) { totalEl.textContent = formatoMoneda(total); }

        if (whatsappEl) {
            const base = whatsappEl.dataset.whatsappBase;
            if (base) {
                const mensaje = construirMensajeWhatsapp(items, total);
                whatsappEl.href = `${base}?text=${encodeURIComponent(mensaje)}`;
                whatsappEl.hidden = false;
            } else {
                whatsappEl.hidden = true;
            }
        }
    }

    // Delegación de eventos: los botones "Agregar" viven en tarjetas
    // repetidas (Home, Catálogo) y en el modal de detalle, así que se
    // escucha una sola vez en document en vez de atar un listener por botón.
    document.addEventListener('click', (event) => {
        const agregarBtn = event.target.closest('.js-carrito-agregar');
        if (agregarBtn && !agregarBtn.hidden) {
            const { slug, nombre, precioNumero, precio, imagen } = agregarBtn.dataset;
            if (slug && precioNumero) {
                Carrito.agregar({
                    slug,
                    nombre: nombre || '',
                    precioNumero: Number(precioNumero),
                    precio: precio || '',
                    imagen: imagen || '',
                });
                mostrarToastCarrito(nombre);
            }
            return;
        }

        const sumarBtn = event.target.closest('.js-carrito-sumar');
        if (sumarBtn) {
            const items = Carrito.leer();
            const item = items.find((i) => i.slug === sumarBtn.dataset.slug);
            if (item) { Carrito.actualizarCantidad(item.slug, item.cantidad + 1); }
            return;
        }

        const restarBtn = event.target.closest('.js-carrito-restar');
        if (restarBtn) {
            const items = Carrito.leer();
            const item = items.find((i) => i.slug === restarBtn.dataset.slug);
            if (item) { Carrito.actualizarCantidad(item.slug, item.cantidad - 1); }
            return;
        }

        const quitarBtn = event.target.closest('.js-carrito-quitar');
        if (quitarBtn) {
            Carrito.quitar(quitarBtn.dataset.slug);
            return;
        }

        const vaciarBtn = event.target.closest('.js-carrito-vaciar');
        if (vaciarBtn) {
            Carrito.vaciar();
        }
    });

    render();
});
