# Sigma Accesorios — sitio web

Proyecto Symfony recién inicializado (configuración base) para el sitio de Sigma Accesorios.

## Primeros pasos

1. Instalar dependencias:

   ```
   composer install
   ```

2. Copiar `.env.local.example` a `.env.local` y llenar los valores reales (`APP_SECRET`, credenciales de Google Sheets, WhatsApp, etc.). Este archivo no se sube a git.

3. Levantar el servidor local:

   ```
   symfony server:start
   ```

   o con el servidor embebido de PHP:

   ```
   php -S localhost:8000 -t public
   ```

4. Abrir `http://localhost:8000` — deberías ver la página de inicio de prueba.

## Catálogo (base de datos clonada de Google Sheets) y panel de imágenes

El inventario real vive en el Google Sheet, pero el sitio lee de **su propia
base de datos** (SQLite por default, cero setup) — no llama a Sheets en cada
visita. Un comando trae los datos de Sheets y actualiza la base:

```bash
php bin/console app:catalog:sync
```

Ese es el que se programa por cron un par de veces al día (ver
[`docs/google-sheets-setup.md`](docs/google-sheets-setup.md)). Para ver el
estado actual sin tocar Sheets:

```bash
php bin/console app:catalog:status
```

Setup completo (cuenta de servicio de Google, migraciones, cron, panel de
imágenes en `/admin/imagenes`), paso a paso, en
[`docs/google-sheets-setup.md`](docs/google-sheets-setup.md).

## Catálogo público

`/catalogo` — grid de productos disponibles con buscador (nombre/descripción,
insensible a mayúsculas/acentos), filtro por categoría (inferida del nombre
del producto, ver `App\Service\Catalog\ProductCategorizer`) y paginador. Cada
tarjeta abre un modal con el detalle (mismo componente que "Destacados" en
Home). El markup de la tarjeta vive en un solo lugar reutilizable:
`templates/_partials/_producto_card.html.twig`.

## Home: carrusel de banners

3 slides con autoplay (Bootstrap Carousel). Cada uno busca su imagen por
convención de nombre en `public/images/banners/banner-1.jpg` (o `.webp`,
`.jpeg`, `.png`), `banner-2.*`, `banner-3.*` — mientras no subas la foto
real, se ve un degradado de la paleta de marca en su lugar, así el carrusel
funciona desde el día uno. Basta con dejar caer los 3 archivos ahí para
reemplazarlos, sin tocar código.

## Nosotros y Contacto

`/nosotros` (texto de ejemplo, listo para ajustar con la historia real del
negocio) y `/contacto` (WhatsApp, Rappi, Facebook, Google Maps y las
sucursales de la base de datos). Los links de contacto se configuran una
sola vez en `.env.local`:

```
WHATSAPP_PHONE_NUMBER=  # con lada país, solo dígitos, ej. 529671234567
FACEBOOK_URL=            # ej. https://facebook.com/tu-pagina
```

`RAPPI_URL` y `MAPS_QUERY` ya traen un valor por default en `.env` (ajusta
`MAPS_QUERY` si quieres afinar la búsqueda en Maps — no es un pin exacto,
es una búsqueda por texto). Si `WHATSAPP_PHONE_NUMBER` o `FACEBOOK_URL`
quedan vacíos, esos botones simplemente no se muestran en vez de llevar a
un link roto.

## Próximos pasos (según la planeación del proyecto)

- Subir las fotos reales de los ~213 productos vía `/admin/imagenes`.
- Subir las 3 fotos del carrusel de Home (`public/images/banners/`).
- Configurar `WHATSAPP_PHONE_NUMBER` y `FACEBOOK_URL` en `.env.local`.
- Integrar el widget de chat de WhatsApp (más allá del link `wa.me`).
- Página de Detalle de producto individual (por ahora el detalle se ve en el modal).
