# Conectar el catálogo al Google Sheet maestro

El backend (`App\Service\Catalog\CatalogSyncService`) lee el Sheet con una
**cuenta de servicio** de Google (no con tu cuenta personal), así no depende de
que alguien tenga sesión iniciada. Pasos:

## 1. Crear el proyecto y la cuenta de servicio en Google Cloud

1. Ve a https://console.cloud.google.com/ y crea un proyecto (o usa uno existente).
2. En "APIs y servicios" → "Biblioteca", busca **Google Sheets API** y actívala.
3. En "APIs y servicios" → "Credenciales" → "Crear credenciales" → **Cuenta de servicio**.
4. Ponle un nombre (ej. `sigma-catalogo`) y termina el asistente (no necesita roles de proyecto).
5. Entra a la cuenta de servicio creada → pestaña "Claves" → "Agregar clave" → **Crear clave nueva** → tipo **JSON**. Se descarga un archivo `.json`.

## 2. Poner la credencial en el proyecto

Copia ese archivo descargado a:

```
config/google/service-account.json
```

Esa ruta ya está en `.gitignore`, así que nunca se sube a git.

## 3. Compartir el Sheet con la cuenta de servicio

Abre el archivo JSON descargado y copia el valor de `client_email` (algo como
`sigma-catalogo@tu-proyecto.iam.gserviceaccount.com`). En el Google Sheet
(https://docs.google.com/spreadsheets/d/1H4ytH7eHNcgKf4bu07pSfjJz1NLUgvo-IsJgY_All0Q),
dale a **Compartir** y agrega ese correo con permiso de **Lector**.

## 4. Configurar `.env.local`

```
GOOGLE_SHEETS_SPREADSHEET_ID=1H4ytH7eHNcgKf4bu07pSfjJz1NLUgvo-IsJgY_All0Q
```

Si el nombre de las pestañas del Sheet no es exactamente `REAL DE GUADALUPE` y
`CAPU` (revisa las pestañas de abajo del Sheet), agrega también en `.env.local`:

```
GOOGLE_SHEETS_RANGE_REAL_DE_GUADALUPE="'Nombre exacto de la pestaña'!A2:L"
GOOGLE_SHEETS_RANGE_CAPU="'Nombre exacto de la otra pestaña'!A2:L"
```

## 5. Probar

```bash
php bin/console app:catalog:test
```

Debería mostrar cuántos productos se leyeron de cada pestaña y un ejemplo. Si
algo falla, el error te dice exactamente qué falta (archivo de credenciales,
spreadsheet ID, o el nombre de una pestaña que no coincide).

---

# Panel de administración (subir imágenes)

Vive en `/admin/imagenes`, protegido con usuario y contraseña (HTTP Basic).

1. Genera el hash de tu contraseña:
   ```bash
   php bin/console security:hash-password
   ```
2. Pega el hash resultante en `.env.local`:
   ```
   ADMIN_PASSWORD_HASH='$2y$13$...'
   ```
3. El usuario es siempre `admin`. Entra a `http://localhost:8000/admin/imagenes`
   y el navegador te pedirá usuario/contraseña.
