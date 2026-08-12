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

## Próximos pasos (según la planeación del proyecto)

- Definir la estructura de columnas del Sheet maestro.
- Servicio de sincronización Google Sheets → catálogo (con cache).
- Páginas de Catálogo y Detalle de producto.
- Widget de chat conectado a WhatsApp.
- Wireframes de Home, Catálogo y Detalle de producto.
