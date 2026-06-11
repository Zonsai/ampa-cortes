# Guía de despliegue — Portal AMPA

Instrucciones para desplegar este proyecto Laravel 12 en un servidor Plesk con PHP 8.3 y MySQL.

---

## Requisitos del servidor

### PHP

- **PHP 8.3** (mínimo)
- Extensiones requeridas:
  - `pdo_mysql`
  - `mbstring`
  - `openssl`
  - `tokenizer`
  - `xml`
  - `ctype`
  - `fileinfo`
  - `bcmath`
  - `zip`
  - `intl`
  - `curl`

### Base de datos

- MySQL 8.0+ o MariaDB 10.6+

### Herramientas en servidor

- **Composer** (disponible en CLI)
- **Node.js / npm**: _opcional_ — solo necesario si se quiere compilar los assets directamente en el servidor. Ver sección [Assets](#assets--public-build) para las opciones recomendadas.

---

## Document root en Plesk

> **Importante:** el document root del dominio/subdominio en Plesk debe apuntar a la carpeta **`/public`** dentro del proyecto, **no a la raíz**.

En Plesk: _Sitios web y dominios → (dominio) → Configuración de alojamiento → Raíz del documento_ → establecer como `httpdocs/public` (o la ruta equivalente según dónde esté alojado el proyecto).

Si el document root apunta a la raíz, el archivo `index.php` no se encontrará y Laravel no arrancará.

---

## Assets / public/build

Los assets compilados (CSS/JS) se generan con Vite y se depositan en `public/build`. Esta carpeta está en `.gitignore` por defecto. Hay tres estrategias posibles:

### Opción 1 — Compilar localmente y subir `public/build` (recomendada para Plesk sin CI)

1. En local: `npm run build`
2. Subir la carpeta `public/build` al servidor junto con el resto del código (SFTP, rsync, etc.)
3. El servidor **no necesita Node** para nada

### Opción 2 — Versionar `public/build` en git

1. Eliminar `public/build` del `.gitignore`
2. Ejecutar `npm run build` en local antes de cada commit que cambie assets
3. Hacer `git add public/build` y commitear
4. En el servidor basta con `git pull` — no hace falta Node
5. Inconveniente: el repositorio crece con archivos binarios generados

### Opción 3 — CI/CD (GitHub Actions, GitLab CI, Bitbucket Pipelines...)

1. El pipeline ejecuta `npm ci && npm run build` automáticamente
2. Despliega el resultado incluyendo `public/build`
3. El servidor no necesita Node
4. Recomendada si el proyecto escala o hay múltiples entornos

**Nota:** independientemente de la opción elegida, en producción nunca se deben instalar las `devDependencies` de npm ni ejecutar `npm run dev`.

---

## Primer despliegue

```bash
# 1. Obtener el código
git clone <url-repositorio> .
# o subir archivos por SFTP

# 2. Dependencias PHP (sin devDependencies)
composer install --no-dev --optimize-autoloader

# 3. Configuración de entorno
cp .env.example .env
php artisan key:generate
```

Editar `.env` con los valores de producción (ver sección [Variables de entorno](#variables-de-entorno-mínimas)).

```bash
# 4. Base de datos
php artisan migrate --force

# 5. Datos base obligatorios (roles y permisos)
php artisan db:seed --class=RoleSeeder --force

# 6. Enlace simbólico de storage (para archivos subidos)
php artisan storage:link

# 7. Cachés de producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 8. Assets (si no vienen en el repositorio ni en el despliegue)
# npm ci && npm run build   ← solo si hay Node disponible en el servidor
```

---

## Variables de entorno mínimas

```dotenv
APP_NAME="Portal AMPA"
APP_ENV=production
APP_DEBUG=false
APP_KEY=          # generada con php artisan key:generate
APP_URL=https://tu-dominio.com

APP_LOCALE=es

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ampa_cortes
DB_USERNAME=usuario_db
DB_PASSWORD=contraseña_db

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

LOG_CHANNEL=stack
LOG_LEVEL=error

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="ampa@tu-dominio.com"
MAIL_FROM_NAME="Portal AMPA"
```

---

## Permisos de escritura

Laravel necesita escribir en dos carpetas. En Plesk, el usuario que ejecuta PHP es el usuario de la suscripción del dominio (no `www-data`).

Las carpetas que deben ser escribibles por ese usuario:

```
storage/
bootstrap/cache/
```

En Plesk se puede ajustar desde el gestor de archivos, o via SSH con el usuario de la suscripción:

```bash
chmod -R 775 storage bootstrap/cache
```

Si se usa el administrador de archivos de Plesk, asegurarse de que el propietario sea el usuario de la suscripción y tenga permisos de escritura.

---

## Actualizaciones posteriores

Checklist para cada actualización:

- [ ] `git pull origin main` (o subir archivos modificados por SFTP)
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] Subir/actualizar `public/build` si han cambiado assets CSS/JS
- [ ] `php artisan migrate --force`
- [ ] Limpiar y regenerar cachés:
  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  php artisan event:cache
  ```
- [ ] Comprobar que la aplicación carga correctamente tras el despliegue

---

## Auditoría de dependencias npm

Estado actual (junio 2025):

| Comando | Resultado |
|---|---|
| `npm audit` | 2 vulnerabilidades críticas |
| `npm audit --omit=dev` | **0 vulnerabilidades** |

**Diagnóstico:** las 2 vulnerabilidades corresponden a `shell-quote` (≤ 1.8.3) a través de `concurrently`. Ambos son `devDependencies` usados exclusivamente en el entorno local de desarrollo (`composer run dev`). En producción se usa `composer install --no-dev`, por lo que **estas dependencias nunca se instalan ni ejecutan en producción**.

**No ejecutar `npm audit fix` sin revisar el impacto en el entorno de desarrollo.** Cuando se actualicen, verificar que el build de assets sigue funcionando correctamente.

---

## Datos demo y entorno de staging

Los datos de demostración (usuario `familia@ampa.test`, familia García López, actividades de prueba) fueron creados mediante `php artisan tinker` en el entorno local. **No están versionados** y no llegarán a producción.

**Pendiente futuro:** crear un `DemoSeeder` opcional que permita recrear un entorno demo/staging reproducible con un solo comando:

```bash
php artisan db:seed --class=DemoSeeder
```

Este seeder **no debe ejecutarse en producción**. Podría incluir una comprobación de `APP_ENV !== 'production'` antes de insertar datos.
