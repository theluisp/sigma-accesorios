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

**Capa 3 (sep 2026):** LocalBusiness JSON-LD — datos estructurados
(schema.org, `@type: ElectronicsStore`) para las dos sucursales, en Home y
Contacto (`App\Service\Seo\NegocioJsonLd` + `App\Service\Seo\SucursalDireccionHorario`).
Incluye horarios (`openingHoursSpecification`), colonia/CP/ciudad (sin
`streetAddress` — no hay calle/número confirmado), teléfono en formato
E.164, y `sameAs` a Facebook/Rappi. Pedido explícito del usuario: "es
importante posicionar en los primeros resultados cuando se busque
accesorios para celular" — ver la nota de expectativas abajo.

### Nota de expectativas: "primeros resultados de accesorios para celular"

"Accesorios para celular" a secas es un término nacional altamente
competido (Amazon, MercadoLibre, Claro, cadenas grandes) — ningún negocio
de 2 sucursales en Puebla va a rankear #1 ahí compitiendo de tú a tú, sin
importar cuánto SEO técnico se le meta al sitio. Lo que SÍ es alcanzable y
es lo que de verdad genera clientes reales para un negocio físico local es
rankear bien en búsquedas con intención local: "accesorios para celular en
Puebla", "cerca de mí", y sobre todo aparecer en el Local Pack de Google
(el mapa + 3 negocios que Google muestra arriba en ese tipo de búsquedas).
El JSON-LD de esta capa ayuda, pero el factor que más pesa para el Local
Pack es la ficha de Google Business Profile (reseñas, fotos, categoría,
horarios ahí también) — eso no es código, es configuración directa en la
cuenta de Google del negocio (ver más abajo).

## Otras capas futuras no empezadas

- Páginas individuales por producto (URL propia en vez del modal actual)
  — mayor impacto a mediano plazo, pero el desarrollo más grande de las
  opciones consideradas.
- Google Business Profile (ficha de Google Maps/Negocio) — fuera del
  alcance de este repo, es configuración directa en la cuenta de Google
  del negocio, no código. Es el mayor factor para aparecer en el Local
  Pack (ver nota de expectativas arriba) — vale la pena resolverlo aunque
  no sea trabajo de desarrollo.
