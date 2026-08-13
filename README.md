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

## Catálogo (Google Sheets) y panel de imágenes

El backend ya lee el catálogo desde el Google Sheet maestro (pestañas "Real De
Guadalupe" y "Capu") y hay un panel en `/admin/imagenes` para subir la foto de
cada producto a mano. Setup completo, paso a paso, en
[`docs/google-sheets-setup.md`](docs/google-sheets-setup.md) — sin eso configurado,
`app:catalog:test` y `/admin/imagenes` van a fallar con un error explicando qué falta.

Para probar que la conexión al Sheet funciona sin abrir el navegador:

```bash
php bin/console app:catalog:test
```

## Próximos pasos (según la planeación del proyecto)

- Página de Catálogo (grid + filtros) y Detalle de producto, usando `CatalogSyncService`.
- Subir las fotos reales de los ~213 productos vía `/admin/imagenes`.
- Widget de chat conectado a WhatsApp.
