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

## Próximos pasos (según la planeación del proyecto)

- Página de Catálogo (grid + filtros) y Detalle de producto, usando `ProductoRepository`.
- Subir las fotos reales de los ~213 productos vía `/admin/imagenes`.
- Widget de chat conectado a WhatsApp.
