# Conectar el catálogo al Google Sheet maestro

El backend (`App\Service\Catalog\CatalogImportService`) lee el Sheet con una
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

Las pestañas reales del Sheet se llaman `Real De Guadalupe Inventario` y
`Capu Inventario` (ya configurado por default en `.env`). Si en algún momento
las renombras, ajusta esto en `.env.local`:

```
GOOGLE_SHEETS_RANGE_REAL_DE_GUADALUPE="'Nombre exacto de la pestaña'!A2:L"
GOOGLE_SHEETS_RANGE_CAPU="'Nombre exacto de la otra pestaña'!A2:L"
```

---

# Base de datos (clon del inventario)

El sitio ya no lee Google Sheets en cada visita — lee su propia base de datos,
y un comando (`app:catalog:sync`) es el único que habla con Sheets, para
actualizarla. Por default usa **SQLite** (un archivo en `var/data.db`, cero
servidor que instalar). Si prefieres MySQL, sobreescribe en `.env.local`:

```
DATABASE_URL="mysql://usuario:password@127.0.0.1:3306/sigma_accesorios?serverVersion=8.0"
```

## 1. Generar y correr la migración inicial

Con `.env.local` ya configurado (o el default de SQLite, que no requiere nada):

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

El primer comando genera el archivo de migración a partir de las entidades
(`src/Entity/`); el segundo crea las tablas de verdad. Te va a pedir
confirmación, dale "yes".

## 2. Traer el catálogo por primera vez

```bash
php bin/console app:catalog:sync
```

Debería decir cuántas filas procesó y cuántos productos nuevos creó. Si algo
falla, el error te dice exactamente qué falta (archivo de credenciales,
spreadsheet ID, nombre de pestaña, o que falten las tablas).

Para ver el estado actual sin volver a llamar a Sheets:

```bash
php bin/console app:catalog:status
```

## 3. Automatizar la sincronización (2 veces al día)

Con cron, en tu WSL:

```bash
crontab -e
```

y agrega una línea (ajusta la ruta a tu proyecto y el usuario si hace falta):

```
0 8,20 * * * cd /home/luisp/proyectos/sigma-accesorios && /usr/bin/php bin/console app:catalog:sync >> var/log/catalog-sync.log 2>&1
```

Eso corre a las 8am y 8pm. **Ojo con WSL**: a diferencia de un servidor
normal, WSL no siempre tiene el servicio de cron corriendo de fondo (depende
de si tu distro usa systemd y de que la terminal de WSL esté abierta/activa).
Verifica que esté corriendo con `service cron status`; si no, `sudo service
cron start` (y necesitarías que WSL se mantenga activo para que dispare a esa
hora). Cuando el sitio esté en un servidor de verdad (hosting), el cron ahí sí
corre de forma confiable las 24 horas — por ahora en tu máquina de desarrollo
puedes simplemente correr `app:catalog:sync` a mano cuando quieras probar.

---

# Panel de administración (subir imágenes)

Vive en `/admin/imagenes`, protegido con usuario y contraseña (HTTP Basic).
Lista los productos desde la base de datos — corre `app:catalog:sync` al
menos una vez antes de usarlo, si no vas a ver la lista vacía.

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
