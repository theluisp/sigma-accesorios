# Plan de trabajo: Pedidos a domicilio con pago en línea

**Proyecto:** Sigma Accesorios — sigma-accesorios (Symfony)
**Fecha:** Septiembre 2026
**Objetivo:** Evolucionar el catálogo actual (carrito informativo → WhatsApp) a un flujo de e-commerce completo: el cliente arma su pedido, paga en línea o notifica su depósito, y el pedido se entrega a domicilio ya sea con reparto propio o con un servicio de entrega bajo demanda (Uber Direct / DiDi).

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

Con base en lo que platicamos, arrancamos con:

- **Entrega:** combinar reparto propio (coordinado manualmente) y un servicio de entrega bajo demanda (Uber Direct o DiDi) desde el diseño del sistema, aunque — ver sección 4 — técnicamente conviene que el propio quede operando primero mientras se valida la integración con el proveedor externo.
- **Pagos:** depósito/transferencia manual **y** PayPal en línea, ambos desde la primera fase útil.
- **Costo de envío:** tarifa fija única para toda el área de cobertura (no por zona, no por distancia calculada). Esto simplifica mucho el cálculo del pedido — es literalmente un monto configurable, no un motor de tarifas.

## 3. Decisiones pendientes (necesito tu respuesta antes de construir ciertas partes)

Estas no bloquean empezar, pero sí bloquean fases específicas más adelante:

1. **Monto de la tarifa fija de envío** y si aplica igual para todas las sucursales o varía por sucursal de origen.
2. **Área de cobertura**: aunque la tarifa es fija, necesitamos decidir si validamos que la dirección esté dentro de una zona razonable de Puebla, o si se acepta cualquier dirección y se resuelve caso por caso.
3. **Cuenta business de PayPal**: ¿ya existe una cuenta PayPal Business de Sigma, o hay que crearla? Se necesita antes de poder integrar el checkout de PayPal (API keys, modo sandbox para pruebas).
4. **Cuenta con Uber Direct o DiDi para entregas**: hay que confirmar cuál de las dos tiene API de entregas bajo demanda disponible para negocios en Puebla y qué requisitos piden (alta de negocio, tarifas por entrega, zona de cobertura del repartidor). Esto es investigación, no desarrollo — lo ideal es resolverlo en paralelo a la Fase 1 para no bloquear el resto.
5. **¿Requiere cuenta de cliente (login) o todo es "guest checkout"?** Mi recomendación es guest checkout (solo nombre, teléfono y dirección, sin contraseña) para mantener la misma fricción baja que ya maneja todo el sitio vía WhatsApp — pero es tu decisión final.
6. **Facturación**: ¿los pedidos necesitan generar factura (CFDI) o el negocio no factura por ahora? Si se factura, es una integración adicional (PAC) que conviene planear aparte.

## 4. Fases propuestas

La idea es que cada fase deje algo usable en producción, no que todo se libere junto al final.

### Fase 0 — Preparativos (no-código)

Antes de escribir el checkout necesitamos tener listo:

- Cuenta PayPal Business + credenciales de API (sandbox y producción).
- Definición del monto de envío fijo.
- Decisión sobre cuenta de cliente (guest vs. registro).
- Investigación de viabilidad de Uber Direct/DiDi para el área de Puebla (puede correr en paralelo mientras se construye la Fase 1).

### Fase 1 — Pedidos con backend real (sin pagos todavía)

Esta es la fase más importante estructuralmente: mover el carrito de `localStorage` a una tabla real de Pedidos.

Nuevas entidades (nombres tentativos):

- `Pedido`: cliente (nombre, teléfono, email opcional), dirección de entrega, sucursal de origen, subtotal, costo de envío (tarifa fija), total, estado (`pendiente`, `confirmado`, `en_preparacion`, `en_camino`, `entregado`, `cancelado`), método de pago elegido, método de entrega elegido, fecha.
- `PedidoItem`: producto, cantidad, precio unitario al momento del pedido (no referenciar el precio actual del catálogo, para no alterar pedidos ya hechos si el precio cambia después).

Cambios de flujo:

- El botón de WhatsApp del carrito se convierte en un formulario de checkout (dirección, teléfono, sucursal más cercana o de preferencia, método de entrega, método de pago) que crea un `Pedido` real en la base de datos.
- Confirmación del pedido por WhatsApp sigue existiendo (se envía un resumen), pero ahora el pedido también queda registrado y visible en el admin.
- Pantalla de "admin de pedidos" (`/admin/pedidos`, mismo patrón que `/admin/categorias`) para ver, cambiar de estado y dar seguimiento a cada pedido.

Al final de esta fase: los pedidos ya no se pierden, quedan en base de datos y hay panel de administración — aunque el cobro real todavía se siga coordinando manualmente (igual que hoy).

### Fase 2 — Pagos

Se agrega el campo "método de pago" con dos caminos:

- **Depósito/transferencia manual**: se muestran los datos bancarios, el cliente marca "ya deposité" y opcionalmente sube su comprobante (imagen/PDF). El pedido pasa a estado `pendiente de confirmación de pago` hasta que alguien del negocio lo concilie manualmente desde el admin. Es la opción más simple de construir y no depende de ninguna cuenta externa lista.
- **PayPal Checkout**: botón de pago en línea con el SDK de PayPal. Al completarse el pago, un webhook de PayPal marca el pedido como pagado automáticamente. Requiere la cuenta business de la Fase 0.

Recomendación: lanzar primero depósito manual (no tiene dependencias externas) y sumar PayPal en cuanto la cuenta esté lista, en vez de bloquear todo el checkout a que ambas estén listas al mismo tiempo.

### Fase 3 — Entrega

Dos caminos conviviendo, seleccionables al momento del pedido (o decididos internamente por el negocio después de recibirlo):

- **Reparto propio**: en el admin de pedidos, se asigna el pedido a un repartidor (puede ser tan simple como un campo de texto "asignado a" al inicio, sin necesitar una app aparte para el repartidor).
- **Uber Direct / DiDi**: al confirmar el pedido, se dispara una solicitud de entrega a la API del proveedor con la dirección del cliente y la de la sucursal de origen; se guarda el ID de la entrega y se refleja su estatus (buscando repartidor, en camino, entregado) en el pedido. Esta parte depende por completo de que la Fase 0 haya resuelto qué proveedor usar y tener acceso a su API — es la pieza de mayor incertidumbre técnica del plan porque depende de un tercero.

### Fase 4 — Notificaciones y pulido

- Confirmaciones automáticas al cliente por WhatsApp (link pre-llenado, igual que ya se hace en otras partes del sitio) cuando el pedido cambia de estado.
- Página de "sigue tu pedido" para que el cliente vea el estatus sin tener que preguntar.
- Reportes básicos en el admin (pedidos del día, ventas por método de pago, etc.) si hace falta para el negocio.

### Fase 5 — Pruebas y lanzamiento gradual

- Probar todo el flujo con pagos en modo sandbox de PayPal y con pedidos de prueba reales de reparto propio antes de activar Uber Direct/DiDi en producción.
- Lanzar primero con un grupo reducido de clientes o solo desde una sucursal, antes de abrirlo a todo el catálogo.

## 5. Consideraciones y riesgos a tener en cuenta

- **Uber Direct/DiDi es la parte de mayor riesgo del plan**, no por complejidad de código sino porque depende de que el proveedor tenga API disponible y accesible para un negocio del tamaño de Sigma en Puebla, con costos por entrega que hay que validar que tengan sentido contra la tarifa fija que se cobre al cliente.
- **Conciliación de depósitos manuales** requiere disciplina operativa (alguien tiene que revisar comprobantes todos los días) — es simple de construir pero no es "automática" en el sentido de que alguien humano cierra el ciclo.
- **Guest checkout vs. cuentas de cliente**: si eventualmente quieren historial de pedidos por cliente, recompensas, etc., en algún momento convendría un sistema de cuentas — no es necesario para el MVP, pero vale la pena tenerlo en mente para no diseñar el `Pedido` de una forma que lo haga difícil de agregar después (por ejemplo, guardando el teléfono/email de forma consistente desde el inicio).
- Igual que con el resto del sitio, todo esto sigue el mismo patrón de trabajo ya establecido: cambios en `sigma-build`, entregados como parches, aplicados en WSL y luego jalados en Hostinger.

## 6. Próximo paso inmediato

Para poder arrancar la Fase 1 con algo concreto, lo que más ayuda ahora es resolver las decisiones pendientes de la sección 3 — sobre todo el monto de envío fijo y si el checkout será con o sin cuenta de cliente — y empezar en paralelo la investigación de la cuenta de Uber Direct/DiDi, ya que es lo que más tiempo de espera externo puede tomar.
