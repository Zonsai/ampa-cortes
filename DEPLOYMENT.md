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
  - `zip` — requerida por maatwebsite/excel para las exportaciones
  - `gd` o `imagick` — opcional, pero recomendada para exports Excel con imágenes
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

Los assets compilados (CSS/JS) se generan con **Vite + Tailwind v4** (`@tailwindcss/vite`) y se depositan en `public/build`.

> **Estado actual del repositorio:** `public/build` está en `.gitignore` (línea `/public/build`), por lo que **no se versiona**. Tailwind v4 escanea las clases en tiempo de compilación, así que **el build debe regenerarse y desplegarse cada vez que cambian estilos/plantillas**. La zona familiar usa CSS propio incrustado en su layout y no depende de este build; el resto del panel (Filament) y los estilos base sí dependen de `public/build`.

> **Recomendación para este proyecto:** **Opción 1** (compilar en local y subir `public/build`) si el despliegue es manual/SFTP sin CI; **Opción 3** (CI/CD) si en el futuro hay pipeline. Evitar instalar Node en el servidor de producción.

Hay tres estrategias posibles:

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

# 5. Roles y permisos (idempotente)
php artisan db:seed --class=RoleSeeder --force

# 5b. Ajustes de marca por defecto (idempotente)
php artisan db:seed --class=AppSettingsSeeder --force

# ⚠️  NO ejecutar:
#   php artisan db:seed --force          ← crea datos demo innecesarios en producción
#   php artisan db:seed --class=AdminUserSeeder   ← solo para entorno local
#   php artisan db:seed --class=LocalDemoSeeder   ← solo para entorno local

# 6. Crear el primer administrador (sin credenciales hardcodeadas)
php artisan ampa:create-admin
# Con opciones para modo no interactivo / scripts de despliegue:
# php artisan ampa:create-admin --email=admin@dominio.com --name="Administrador AMPA" --password="..."
#
# Una vez creado el primer admin, el resto de usuarios se gestionan desde el panel:
# - Panel admin → Configuración → Usuarios
# - Para crear acceso familiar a un tutor/a: panel → Familias → Tutores/as legales
#   → acción "Crear acceso familiar" en la fila correspondiente.
# - Las contraseñas temporales se comunican manualmente; no se envían emails.

# 7. Enlace simbólico de storage (para archivos subidos, incluyendo logos)
php artisan storage:link
# Los logos se almacenan en storage/app/public/branding/
# El servidor debe servir /storage vía symlink (ya creado con el comando anterior)
# Verificar después: que https://tu-dominio.com/storage/ responde y, si hay logo,
# que https://tu-dominio.com/storage/branding/<archivo> carga la imagen.

# 8. Cachés de producción
# Atajo: 'php artisan optimize' ejecuta config:cache + route:cache + más en un solo paso.
php artisan optimize
# (Equivalente granular, si se prefiere por separado:)
#   php artisan config:cache
#   php artisan route:cache
#   php artisan view:cache
#   php artisan event:cache

# 9. Verificar que existe al menos un super_admin (no debe quedar el panel sin acceso)
php artisan tinker --execute "echo \App\Models\User::role('super_admin')->where('is_active', true)->count();"
# Debe imprimir >= 1.

# 10. Assets (si no vienen en el repositorio ni en el despliegue)
# npm ci && npm run build   ← solo si hay Node disponible en el servidor
```

> **Nota sobre `optimize`:** si se cambian variables de entorno tras cachear, ejecutar `php artisan optimize:clear` y volver a cachear. Nunca cachear configuración con `APP_DEBUG=true`.

---

## Variables de entorno mínimas

```dotenv
APP_NAME="Portal AMPA"
APP_ENV=production
APP_DEBUG=false                          # CRÍTICO: nunca true en producción
APP_KEY=                                 # generada con php artisan key:generate
APP_URL=https://tu-dominio.com           # HTTPS obligatorio

APP_LOCALE=es
APP_FALLBACK_LOCALE=es

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ampa_cortes
DB_USERNAME=usuario_db
DB_PASSWORD=contraseña_db

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true               # Requiere HTTPS
SESSION_LIFETIME=120
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

> **Importante:** con `SESSION_SECURE_COOKIE=true` las cookies solo se envían por HTTPS. Asegúrate de que el dominio tiene certificado SSL activo antes de activar esta opción.

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

## Seeders: guía de seguridad

| Seeder | ¿Cuándo usarlo? | ¿Seguro en producción? |
|---|---|---|
| `RoleSeeder` | Primer despliegue + cada actualización que añada permisos | ✅ Sí — idempotente |
| `AcademicYearSeeder` | Solo si se quieren años académicos base | ✅ Sí — idempotente |
| `AppSettingsSeeder` | Primer despliegue + defaults de marca si no existen | ✅ Sí — idempotente |
| `AdminUserSeeder` | **Solo local** — crea `admin@ampa.test / password` | ❌ NO |
| `DemoDataSeeder` | **Solo local** — crea familias y datos de prueba | ❌ NO |
| `LocalDemoSeeder` | **Solo local** — dataset completo para QA visual | ❌ NO |

Para crear el administrador en producción, usar **exclusivamente**:

```bash
php artisan ampa:create-admin
```

Este comando no hardcodea credenciales, valida el email y la contraseña, y solo asigna el rol si `RoleSeeder` se ha ejecutado previamente.

## Datos demo y entorno de staging

Los seeders de demo (`AdminUserSeeder`, `DemoDataSeeder`, `LocalDemoSeeder`) incluyen comprobaciones de entorno y se niegan a ejecutarse fuera de `local`. Si se ejecuta `php artisan db:seed --force` en producción, solo correrán `RoleSeeder` y `AcademicYearSeeder` (ambos seguros).

Para recrear un entorno demo/staging local desde cero:

```bash
php artisan migrate:fresh --seed
# O solo los datos visuales, sin recrear la BD:
php artisan db:seed --class=LocalDemoSeeder
```

---

## Storage y logos

- Los logos de marca se guardan en **`storage/app/public/branding/`** y se sirven a través del symlink `public/storage` → `storage/app/public` (creado con `php artisan storage:link`).
- Tras el `storage:link`, comprobar que la URL pública responde: `https://tu-dominio.com/storage/branding/<archivo>` debe cargar la imagen.
- Si **no hay logo configurado**, la aplicación usa un **fallback** con las iniciales del nombre del AMPA (no se produce error ni imagen rota).
- La carpeta `storage/` debe ser **escribible** por el usuario que ejecuta PHP (ver [Permisos de escritura](#permisos-de-escritura)).
- `public/storage` está en `.gitignore`: el symlink se crea en cada servidor; no se versiona.

---

## Backups

Antes de pasar a producción y de forma periódica:

- [ ] **Base de datos:** copia regular de la BD (mysqldump programado o backups de Plesk). Es el dato crítico (familias, inscripciones, consentimientos, respuestas).
- [ ] **`storage/app/public`:** copia de los archivos subidos (logos y futuros adjuntos). No está en git.
- [ ] Verificar que el backup se puede **restaurar** (probar una restauración en staging al menos una vez).
- [ ] **No** es necesario respaldar `public/build` ni `vendor/` (se regeneran).

---

## Checklist de demo (AMPA)

Preparación para enseñar el portal al AMPA (entorno local o staging, **sin datos reales**):

- [ ] Cargar datos mínimos/demo: `php artisan migrate:fresh --seed` o `php artisan db:seed --class=LocalDemoSeeder` (solo en `local`).
- [ ] Compilar assets: `npm run build` y verificar que `public/build` existe.
- [ ] Revisar **branding** (nombre del AMPA, colores, logo) en el panel admin → Configuración.
- [ ] Confirmar un **usuario admin** (`super_admin`) y poder entrar en `/admin`.
- [ ] Confirmar un **usuario familia** vinculado a un tutor con familia y alumno/a.
- [ ] **Login familiar:** entrar en `/familia/login` y comprobar redirección al dashboard.
- [ ] **Dashboard familiar:** ver tarjetas de resumen, avisos y accesos rápidos.
- [ ] **Extraescolar (flujo completo):**
  - [ ] Solicitud desde la familia (queda en *Solicitud enviada / Pending*).
  - [ ] Confirmación desde el admin.
  - [ ] Marcar *Pendiente de pago*.
  - [ ] Registrar pago (*Pago registrado*).
- [ ] **Formularios:**
  - [ ] Formulario **por familia** (una respuesta).
  - [ ] Formulario **por alumno/a** con **dos hijos** (responder uno deja el otro pendiente; al responder ambos, queda completo).
  - [ ] **Clonar** un formulario (queda en borrador, copia campos y público, sin respuestas).
- [ ] **Consentimientos:**
  - [ ] **Aceptar** un consentimiento pendiente.
  - [ ] **Rechazar** uno rechazable.
  - [ ] **Revocar** uno aceptado revocable.
  - [ ] **Volver a aceptar** uno revocado.
- [ ] Revisar **Usuarios y accesos familiares** (panel → Configuración → Usuarios; acción "Crear acceso familiar" en Tutores).
- [ ] Revisar **vista móvil básica** de la zona familiar (navegación, tarjetas, botones).

---

## Checklist de producción (pre-lanzamiento)

Verificación obligatoria **antes** de abrir el portal a usuarios reales:

**Configuración (`.env` definitivo):**
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false` *(crítico)*
- [ ] `APP_KEY` generada (`php artisan key:generate`)
- [ ] `APP_URL=https://dominio-real`
- [ ] `APP_LOCALE=es` y `APP_FALLBACK_LOCALE=es`
- [ ] `SESSION_SECURE_COOKIE=true` (requiere HTTPS activo)
- [ ] Credenciales de BD de producción (MySQL/MariaDB)

**Infraestructura:**
- [ ] **HTTPS** con certificado válido.
- [ ] Document root apuntando a `/public`.
- [ ] Permisos de escritura en `storage/` y `bootstrap/cache`.
- [ ] `php artisan storage:link` ejecutado y `/storage/...` accesible.
- [ ] `public/build` presente y actualizado (assets compilados).
- [ ] Cachés generadas (`php artisan optimize`).
- [ ] **Backups** de BD y de `storage/app/public` configurados.

**Datos y acceso:**
- [ ] `RoleSeeder` y `AppSettingsSeeder` ejecutados (idempotentes).
- [ ] **No** se han ejecutado seeders demo (`AdminUserSeeder`, `DemoDataSeeder`, `LocalDemoSeeder`).
- [ ] Al menos **un `super_admin` activo** (verificado con el comando del paso 9).

**Pruebas humo en producción:**
- [ ] **Login admin** correcto en `/admin`.
- [ ] **Login familia** correcto en `/familia/login`.
- [ ] **Escritura en storage:** subir un logo desde el panel y verlo servido por `/storage/branding/...`.
- [ ] **Una inscripción** de extraescolar de prueba (y revertirla/limpiarla).
- [ ] **Un formulario** de prueba (responder y ver respuesta).
- [ ] **Un consentimiento** de prueba (aceptar).

> Tras validar, eliminar cualquier dato de prueba creado durante las pruebas de humo.
