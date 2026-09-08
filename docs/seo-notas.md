# Notas de SEO — Sigma Accesorios

Bitácora de lo que se ha ido implementando en capas, y los datos que ya se
confirmaron para lo que sigue.

## Capas ya implementadas

**Capa 1 (ago 2026):** meta description, canónica, Open Graph/Twitter Card
por página, `robots.txt` y `sitemap.xml` dinámicos (vía `SeoController`).
`<title>`/`<meta description>` específicos por filtro real del Catálogo
(categoría, marca, Ofertas, Novedades).

**Capa 2 (sep 2026):**
- `sitemap.xml` con imágenes (`xmlns:image`): una `<image:image>` por
  producto disponible con foto propia, colgada de la entrada del Catálogo
  (que sigue siendo una sola URL — los productos no tienen página propia
  todavía). También agrega `<lastmod>` al Catálogo con la fecha del
  producto actualizado más recientemente.
- `<h1>` del Catálogo ahora es dinámico según el filtro (antes decía
  siempre "Catálogo").
- Meta description específica por categoría real (antes todas compartían
  el mismo texto genérico con solo el nombre cambiado) — ver
  `CatalogoController::DESCRIPCIONES_SEO`.
- `<h1>` de Contacto: "Contacto" → "Contacto y pedidos a domicilio".
- Fix: `MAPS_QUERY` en `.env` decía "San Cristóbal de las Casas" (quedó de
  una plantilla genérica, nunca se actualizó) → corregido a "Sigma
  Accesorios Puebla".

## Pendiente: datos para LocalBusiness JSON-LD (todavía no implementado)

Esta capa se decidió posponer (sep 2026, el usuario eligió empezar por los
"ajustes rápidos" de la Capa 2), pero ya se juntaron datos reales para
cuando se retome:

- **Sucursal Real de Guadalupe:** lunes a viernes 10am–5pm y 7pm–9pm;
  martes 6pm–10pm (en vez del segundo horario de 7-9pm); sábados
  10am–2pm. Colonia Real de Guadalupe, CP 72016.
- **Sucursal Capu:** lunes a viernes 9am–4pm. Colonia Capu, CP 72050.
- Confirmado por el usuario (sep 2026): CP 72016 es de Real de Guadalupe,
  CP 72050 es de Capu — ya no hay ambigüedad, listo para usarse en el
  JSON-LD.
- No se tiene calle/número exacto de ninguna de las dos sucursales, solo
  colonia + CP + ciudad (Puebla, Pue.).

## Otras capas futuras no empezadas

- Páginas individuales por producto (URL propia en vez del modal actual)
  — mayor impacto a mediano plazo, pero el desarrollo más grande de las
  opciones consideradas.
- Google Business Profile (ficha de Google Maps/Negocio) — fuera del
  alcance de este repo, es configuración directa en la cuenta de Google
  del negocio, no código.
