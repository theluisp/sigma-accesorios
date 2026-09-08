# Plan de trabajo: Pedidos a domicilio con pago en línea

**Proyecto:** Sigma Accesorios — sigma-accesorios (Symfony)
**Fecha:** Septiembre 2026
**Objetivo:** Evolucionar el catálogo actual (carrito informativo → WhatsApp) a un flujo de e-commerce completo: el cliente arma su pedido, ve el costo total con envío incluido, paga por transferencia/depósito o PayPal, y el negocio valida y autoriza el pedido antes de entregarlo — ya sea por Rappi, reparto propio, o coordinando su propio envío por DiDi.

Este documento es el punto de partida para ir descomponiendo el desarrollo en fases manejables. Está pensado para revisarse y ajustarse conforme avancemos — no es un contrato cerrado.

---

## 1. Dónde estamos hoy

El sitio ya tiene un carrito, pero es 100% de front-end:

- El carrito vive en `localStorage` del navegador (objeto `Carrito` en `public/js/app.js`). No hay backend de pedidos: si el cliente cambia de dispositivo o borra datos del navegador, el carrito desaparece.
- El único "checkout" es un botón que arma un link de WhatsApp con el resumen del pedido (`carrito-whatsapp` en `base.html.twig`), y el cierre de la venta se negocia manualmente por chat.
- No existe cobro en línea, ni registro de pedidos, ni dirección de entrega, ni costo de envío calculado.
- El catálogo de productos (`Producto`, `ProductoImagen`, `ProductoSucursal`) y las sucursales (`Sucursal`) ya están sólidos y se sincronizan desde Google Sheets — esa parte no cambia.
- No hay sistema de cuentas de cliente (login/registro) en ningún lado del sitio.

Todo lo de abajo se construye sobre esta base, no la reemplaza: el catálogo y la sincronización de inventario siguen funcionando igual.

## 2. Decisiones ya tomadas

- **Entrega — sección "Envíos a domicilio" (definida, ver sección 4, Fase 1):**
  1. Opción de pedir por Rappi, como ya existe hoy.
  2. Si no aplica Rappi: pedidos con subtotal mayor a **$299** tienen entrega gratis (reparto propio del negocio).
  3. Pedidos que no llegan a $299: el cliente cotiza su propio envío en la herramienta pública de DiDi Entrega Business y captura ese monto en nuestro checkout. Como incentivo, Sigma aplica **15% de descuento sobre el subtotal de productos** (no sobre el envío) para animar a que complete la compra.
  - Se descarta integrar una API de Uber Direct o DiDi para automatizar la entrega — ver la nota técnica en la sección 5, es más complejidad y trámite de lo que vale la pena para el volumen actual.
- **Pagos:** depósito/transferencia manual **y** PayPal en línea. La validación de pago por transferencia/depósito siempre es manual: el cliente manda su comprobante por WhatsApp y el negocio autoriza el pedido antes de que quede confirmado.
- **Costo de envío:** no es una tarifa fija para todos los casos — ver el árbol de decisión de arriba (gratis por monto, o cotizado por el cliente vía DiDi).

## 3. Decisiones pendientes (necesito tu respuesta antes de construir ciertas partes)

1. **¿Quién agenda y paga el viaje real de DiDi una vez pagado el pedido?** Mi entendimiento del flujo: el cliente solo usa el cotizador de DiDi para *saber* cuánto costaría el envío y nos reporta ese monto; nosotros lo sumamos al total y el cliente nos paga todo junto (producto con descuento + envío) por transferencia/depósito. Después de validar el pago, **el negocio** sería quien agenda y paga el viaje de DiDi para que recoja el paquete en la sucursal y lo entregue — usando el monto que el cliente ya cotizó como referencia. Confírmame si es así o si lo pensabas distinto (por ejemplo, que el cliente pague su propio viaje de DiDi por separado).
2. **Cuenta business de PayPal**: ¿ya existe una cuenta PayPal Business de Sigma, o hay que crearla? Se necesita antes de poder integrar el checkout de PayPal (API keys, modo sandbox para pruebas).
3. **¿Requiere cuenta de cliente (login) o todo es "guest checkout"?** Mi recomendación es guest checkout (solo nombre, teléfono y dirección, sin contraseña) para mantener la misma fricción baja que ya maneja todo el sitio vía WhatsApp — pero es tu decisión final.
4. **Facturación**: ¿los pedidos necesitan generar factura (CFDI) o el negocio no factura por ahora? Si se factura, es una integración adicional (PAC) que conviene planear aparte.
5. **¿Cuándo "no aplica Rappi"?** ¿Es porque el producto no está dado de alta en Rappi, porque el cliente prefiere no usarlo, o ambas? Esto define si mostramos las tres opciones siempre o si alguna se oculta según el producto.

## 4. Fases propuestas

La idea es que cada fase deje algo usable en producción, no que todo se libere junto al final.

### Fase 0 — Preparativos (no-código)

Antes de escribir el checkout necesitamos tener listo:

- Cuenta PayPal Business + credenciales de API (sandbox y producción).
- Decisión sobre cuenta de cliente (guest vs. registro).
- Confirmar el punto 1 de la sección 3 (quién agenda/paga el viaje real de DiDi).

### Fase 1 — Pedidos, envíos a domicilio y pago manual

Esta es la fase central: mover el carrito de `localStorage` a pedidos reales en base de datos, con el árbol de envío completo y pago manual validado por WhatsApp (sin PayPal todavía).

**Nuevas entidades (nombres tentativos):**

- `Pedido`: cliente (nombre, teléfono, email opcional), dirección de entrega, sucursal de origen, subtotal, tipo de entrega (`rappi`, `gratis_reparto_propio`, `envio_didi`), costo de envío (0, o el monto capturado de la cotización de DiDi), descuento aplicado (15% sobre subtotal cuando aplica envío DiDi), total, estado (`pendiente_pago`, `pago_reportado`, `confirmado`, `en_preparacion`, `en_camino`, `entregado`, `cancelado`), método de pago elegido, fecha.
- `PedidoItem`: producto, cantidad, precio unitario al momento del pedido (no referenciar el precio actual del catálogo, para no alterar pedidos ya hechos si el precio cambia después).

**Flujo de "Envíos a domicilio" en el checkout:**

1. El cliente elige cómo quiere recibir su pedido: *Pedir por Rappi* (lo manda a la app/link de Rappi como ya existe hoy, fuera de este flujo), o *Entrega directa con Sigma*.
2. Si elige entrega directa y su subtotal ya es mayor a $299: se le informa que su envío es gratis, sin pasos adicionales.
3. Si su subtotal es menor a $299: se le explica la opción de cotizar su propio envío con DiDi, con un botón que abre en una pestaña nueva `https://www.didi-food.com/es-MX/entrega-business/bill` (herramienta pública de DiDi, sin necesidad de que Sigma tenga cuenta ahí — ver nota técnica en la sección 5). El cliente captura ahí la dirección de la sucursal como origen y la suya como destino, obtiene un precio, y regresa a nuestro checkout a **escribir ese monto en un campo** de nuestro formulario.
4. Con ese monto capturado, el sistema arma el total: subtotal con 15% de descuento aplicado, más el envío capturado.
5. El cliente elige método de pago (transferencia/depósito, en esta fase) y el pedido queda en estado `pago_reportado` en cuanto sube su comprobante o confirma que ya depositó.
6. El resumen se manda por WhatsApp igual que hoy, pero ahora también queda guardado como `Pedido` real.
7. Pantalla de "admin de pedidos" (`/admin/pedidos`, mismo patrón que `/admin/categorias`) para ver cada pedido, confirmar el pago manualmente al revisar el comprobante, y cambiar su estado conforme avanza.

Al final de esta fase: un cliente puede armar su pedido, saber exactamente cuánto le cuesta con envío incluido (gratis, cotizado por DiDi, o vía Rappi), pagar por transferencia/depósito, y el negocio lo valida y autoriza desde un panel — todo queda registrado, ya no se pierde nada.

### Fase 2 — Pago automático con PayPal

Se agrega PayPal Checkout como método de pago adicional al de la Fase 1: botón de pago en línea con el SDK de PayPal. Al completarse el pago, un webhook de PayPal marca el pedido como pagado automáticamente, sin depender de que alguien revise un comprobante. Requiere la cuenta business de la Fase 0.

### Fase 3 — Notificaciones y pulido

- Confirmaciones automáticas al cliente por WhatsApp (link pre-llenado, igual que ya se hace en otras partes del sitio) cuando el pedido cambia de estado.
- Página de "sigue tu pedido" para que el cliente vea el estatus sin tener que preguntar.
- Reportes básicos en el admin (pedidos del día, ventas por método de pago, cuántos se van por Rappi vs. envío directo, etc.) si hace falta para el negocio.

### Fase 4 — Pruebas y lanzamiento gradual

- Probar todo el flujo con pagos en modo sandbox de PayPal y con pedidos de prueba reales del árbol de envío (Rappi, gratis, y cotizado por DiDi) antes de abrirlo a todos los clientes.
- Lanzar primero con un grupo reducido de clientes o solo desde una sucursal.

### Fase futura (opcional, no planeada todavía) — Entrega automatizada

Si en algún momento el volumen de pedidos justifica el trámite, quedaría pendiente evaluar una integración real con la API de negocio de Uber Direct o DiDi (la que sí requiere cuenta de negocio y aprobación) para agendar y monitorear entregas automáticamente en vez del flujo manual de la Fase 1. Por ahora se descarta por los requisitos que implica.

## 5. Consideraciones y riesgos a tener en cuenta

- **El cotizador de DiDi (`entrega-business/bill`) es una aplicación web pensada para que una persona la llene a mano** (confirmado: es una app de JavaScript sin contenido estático, típica de una herramienta de un solo uso interactivo) — no encontramos indicio de que exponga una API pública para que otros sistemas la consulten automáticamente. Por eso el plan es que el cliente la use directamente en una pestaña y nos reporte el monto, en vez de que nuestro sistema intente leerlo automáticamente. Intentar automatizarlo llamando a los endpoints internos de esa página sería frágil (puede cambiar sin aviso), probablemente viola los términos de servicio de DiDi, y no es algo que conviene construir — por eso no está en el plan.
- **Conciliación de pagos manuales** (transferencia/depósito) requiere disciplina operativa: alguien tiene que revisar comprobantes y confirmar montos capturados de DiDi contra lo cobrado — es simple de construir pero no es "automática" en el sentido de que una persona cierra el ciclo.
- **Guest checkout vs. cuentas de cliente**: si eventualmente quieren historial de pedidos por cliente, recompensas, etc., en algún momento convendría un sistema de cuentas — no es necesario para el MVP, pero vale la pena tenerlo en mente para no diseñar el `Pedido` de una forma que lo haga difícil de agregar después (por ejemplo, guardando el teléfono/email de forma consistente desde el inicio).
- Igual que con el resto del sitio, todo esto sigue el mismo patrón de trabajo ya establecido: cambios en `sigma-build`, entregados como parches, aplicados en WSL y luego jalados en Hostinger.

## 6. Próximo paso inmediato

Confirmar el punto 1 de la sección 3 (quién agenda y paga el viaje real de DiDi después del pago) y las demás decisiones pendientes de esa sección — con eso queda cerrado el diseño de la Fase 1 y se puede empezar a construir las entidades `Pedido`/`PedidoItem` y el formulario de checkout con el árbol de envío completo.
