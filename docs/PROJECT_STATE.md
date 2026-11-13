# Estado del proyecto — Portal AMPA Cortés de Aragón

> Documento de contexto interno. Léelo al empezar cualquier sesión nueva antes de tocar código.
> Generado a partir del estado real del repositorio (código, migraciones, tests, `git log`), no de memoria de chat.
> Última actualización: 2026-09-23 (renovación visual completa del front público y la zona familiar — Home, Dashboard, calendario refinado, iconografía y formularios unificados — ver §4 y §10).

## 1. Stack actual

Versiones reales instaladas (`composer show --direct`, 2026-11-13):

| Componente | Versión |
|---|---|
| PHP | **8.5** — `composer.json` exige `^8.5`; desarrollo y tests con 8.5.12 (NTS) |
| Laravel | 13.42.0 (`laravel/framework ^13.0`) |
| Laravel Tinker | 3.1.0 |
| Filament | 5.11.2 (`filament/filament ^5.0`) |
| Livewire | 4.4.8 (dependencia de Filament; el proyecto no tiene componentes Livewire propios) |
| Spatie Laravel Permission | 8.5.0 (`^8.0`) |
| Laravel Excel (maatwebsite/excel) | 4.0.6 (`^4.0`), con `phpoffice/phpspreadsheet` 5.11.0 |
| PHPUnit | 13.4.6 (`^13.0`) |
| Herramientas de desarrollo | Pint 1.32.3, Pail 1.3.0, Sail 1.70.0, Collision 8.9.6, Boost 2.10.4, Mockery 1.6.15, Faker 1.24.1 |
| Base de datos | MySQL en local/producción (`utf8mb4_unicode_ci`; 8.4.3 en Laragon); SQLite en memoria para tests (`phpunit.xml`) |

`composer.json` usa `minimum-stability: stable`: no hay betas, RC ni ramas dev instaladas. `composer audit` → "No security vulnerability advisories found." y todas las dependencias directas están en su última versión estable.

**Historial de actualizaciones del stack:**

- Laravel 12 → 13 (rama técnica `upgrade/laravel-13`, ya fusionada), parches de seguridad previos (Filament 4.13, Livewire 3.8, Excel 3.1.70, PhpSpreadsheet 1.30.7).
- **Modernización completa** (rama `chore/modernize-stack`, en fases separadas): Laravel 13.32 → 13.42 y todas las dependencias dentro de sus rangos (cerró 15 advisories nuevos de Filament, Symfony y league/commonmark); `php ^8.2` → `^8.5`; Laravel Excel 3.1 → 4.0 (PhpSpreadsheet 1.x → 5.x, requisito para PHP 8.5, ya que la 1.x declara `php <8.5`); Filament 4 → 5 y Livewire 3 → 4 (script oficial `filament-v5`: sin APIs afectadas); Spatie Permission 6 → 8 (sin cambios de esquema; `config/permission.php` sincronizado); PHPUnit 11 → 13.
- Compatibilidad aplicada: `config/database.php` usa `Pdo\Mysql::ATTR_SSL_CA` (la constante `PDO::MYSQL_ATTR_SSL_CA` está deprecada en PHP 8.5) y se añadió `App\Policies\UserPolicy` (ver §7).

**Dependencias transitivas con major nueva que no se fuerzan** (las fija un paquete padre): `chillerlan/php-qrcode` 6 (Filament exige `^5.0`), `openspout/openspout` 5 (`filament/actions` exige `^4.23`) y `symfony/yaml` 8 (la rama 8.x requiere el resto de Symfony 8; Laravel 13 usa 7.4 y la 7.4.23 está al día de avisos).

No hay npm/JS custom más allá del scaffolding estándar de Laravel (Vite + Tailwind 4 para `resources/css/app.css`) y los assets publicados de Filament (`public/js/filament`, `public/css/filament`, versionados en git: se regeneran con `php artisan filament:upgrade` tras actualizar Filament). No usar paquetes nuevos sin decisión explícita.

## 2. Roles y permisos (Spatie Permission)

Definidos en [database/seeders/RoleSeeder.php](../database/seeders/RoleSeeder.php).

- **`super_admin`** — bypass total vía `before()` en policies; acceso completo a Filament.
- **`junta_ampa`** — CRUD completo sobre todas las entidades + `manage settings`.
- **`admin_extraescolares`** — extraescolares e inscripciones (ver/gestionar) + lectura de familias/tutores/alumnos/informes. Sin borrar inscripciones ni gestionar pagos.
- **`admin_formularios`** — formularios y consentimientos (ver/gestionar) + lectura de familias/tutores/alumnos.
- **`familia`** — sin permisos Filament; accede solo a la zona familiar (`/familia/*`), autenticación separada vía `Guardian.user_id`.

## 3. Módulos implementados

Todos con modelo, migración, Filament resource (cuando aplica), policy y tests.

- **Familias / tutores / alumnos**: `Family`, `Guardian`, `Student` (+ `student_classroom` pivot). Membresía AMPA vive en `Family` (`is_ampa_member`), no en `Guardian`.
- **Aulas y estructura académica**: `Classroom`, `Grade`, `SchoolStage`, `AcademicYear`.
- **Extraescolares**: `ExtracurricularActivity` → `ActivityGroup` (grupos con horario recurrente) → `Enrollment`.
- **Inscripciones**: ver flujo detallado en §6.
- **Pagos manuales**: campos en `Enrollment` (`amount`, `price_type`, `paid_at`, `payment_method`) gestionados vía `RegisterPaymentAction` / `VoidPaymentAction` (sin pasarela real).
- **Formularios**: `Form`, `FormField`, `FormResponse`, `FormResponseAnswer`, `FormTargetItem` — formularios dinámicos dirigidos a segmentos de familias/alumnos.
- **Consentimientos**: `ConsentType`, `ConsentVersion`, `ConsentResponse`, `ConsentHistory` — versionado de textos legales, aceptación/rechazo/revocación por familia, trazabilidad completa.
- **Anuncios**: `Announcement` (tablón admin + público, con audiencia y estado).
- **Branding**: `AppSetting` + página Filament `BrandingSettings` (marca configurable: nombre, colores, logo).
- **Audit log**: `AuditLog`, registro de actividad de escritura y trazabilidad de pagos (solo lectura en Filament).
- **Front público** (`PublicController`): home, "sobre el AMPA", extraescolares, tablón de anuncios, contacto — sin autenticación.
- **Zona familiar** (`app/Http/Controllers/Familia/*`, prefijo `/familia`): login propio, dashboard, hijos, extraescolares (catálogo + inscripción/baja), formularios (responder/editar), consentimientos (aceptar/rechazar/revocar).
- **Calendario técnico**: ver §4.

## 4. Estado del calendario

**Ya existe** (base técnica de horarios + calendario visual familiar):

- `ActivityGroup`: horario recurrente semanal — `weekdays` (array ISO 1–7), `starts_at`/`ends_at` (hora), `effective_from`/`effective_until` (rango de fechas del grupo, opcional; si es null cae al año académico de la actividad).
- `ActivityGroupException`: excepciones puntuales sobre una fecha recurrente — tres tipos (`App\Enums\ExceptionType`): `cancelled` (se cancela una sesión), `modified` (cambia fecha/hora/ubicación de una sesión), `extra` (sesión añadida fuera del patrón). Cada tipo tiene validación de coherencia en `booted()` (p. ej. una `extra` no puede duplicar una sesión regular).
- `Enrollment.attendance_from` / `attendance_until`: acota el periodo en que un alumno concreto asiste dentro del grupo (puede ser más corto que el rango del grupo). Gestionado por acciones dedicadas (`App\Actions\Enrollments\*`), probado en `tests/Feature/EnrollmentAttendanceActionsTest.php`.
- `App\Services\ScheduledSessionCalculator` + `App\Support\ScheduledSession`: calcula la lista concreta de sesiones (fecha, hora, ubicación) para un grupo o para una inscripción dentro de un rango de fechas, aplicando excepciones y periodo de asistencia. Es la pieza central que consume el calendario visual. Cubierto por `tests/Feature/ScheduledSessionCalculatorTest.php`.
- Filament: `ActivityGroupResource` con `RelationManager` de excepciones (`ExceptionsRelationManager`), tests en `ActivityGroupExceptionsFilamentTest.php`.
- **Calendario familiar semanal** (`/familia/calendario`, `App\Http\Controllers\Familia\CalendarController`, `resources/views/familia/calendar/index.blade.php`) — en `main` desde la fusión de `feature/family-calendar`; el refinamiento visual posterior (ver abajo) se desarrolló en `feature/visual-refresh` y también está ya en `main`:
  - Filtra por inscripciones activas de la familia (estados `Enrolled`/`PendingPayment`/`Paid`) y por hijo/a seleccionado (`?student=`, con guarda contra acceso cruzado entre familias).
  - Navegación semanal normalizada a lunes (`?week=`), con semana por defecto = semana actual si el curso activo cubre hoy, o primera semana del curso en caso contrario.
  - **Timetable de escritorio (≥860px)**: eje horario compartido y posición/duración de cada sesión calculados en PHP (`CalendarController::buildTimetable()`) en minutos desde el inicio del rango visible de la semana — sin JavaScript, sin librería de calendario. Solapamientos reales entre hijos/as resueltos por partición de intervalos (`assignLanes()`), mostrados en paralelo (nunca uno oculta a otro). Contenedor propio de hasta 1360px (independiente de `.family-container`), nombre de grupo mostrado solo cuando hace falta desambiguar (`CalendarController::$groupNameActivityIds`), cambio puntual inline junto a la hora, días sin sesiones discretos (sin texto visible, solo accesible).
  - **Agenda vertical en móvil** (<860px): tarjetas por sesión agrupadas por día, sin cambios respecto al primer diseño.
  - Excepciones `Modified` se muestran inline con indicación de cambio; `Cancelled` aparece como aviso en "Avisos de esta semana", nunca como sesión.
  - Paleta de 6 colores por hijo/a (identidad de alumno/a, independiente de la marca — ver `DESIGN.md` §3.5).
  - Cubierto por 22 tests en `tests/Feature/FamilyCalendarTest.php` (acceso, filtros, navegación, excepciones, cálculo del timetable, nombre de grupo por contexto).

**NO existe todavía**:

- Exportación `.ics`.
- Asistencia real (pase de lista por sesión). `attendance_from/until` es un **periodo de vigencia**, no un registro de asistencia por clase.
- Integración GIR.

## 5. Curso activo / histórico

- `AcademicYear.is_active` marca el curso vigente.
- Filament filtra por curso activo en Inscripciones, Extraescolares y Formularios (commit `38d898e`). El histórico de cursos anteriores sigue siendo accesible (no se oculta, solo cambia el filtro por defecto).

## 6. Flujo de inscripciones (`App\Enums\EnrollmentStatus`)

Estados: `pending` → `enrolled` | `waitlist` → `pending_payment` → `paid`; con salidas a `dropped` (baja) o `cancelled` (cancelada). `pending` es el estado real que genera hoy `RequestFamilyEnrollmentAction` cuando una familia solicita plaza desde el portal `/familia` y hay plazas físicamente disponibles: la plaza NO se reserva en ese momento (varias familias pueden quedar `pending` para el mismo hueco) hasta que el AMPA confirma la solicitud (`ConfirmEnrollmentAction`). Si no hay plazas disponibles, la solicitud entra directamente en `waitlist`.

Acciones principales (`app/Actions/Enrollments/`):

- `RequestFamilyEnrollmentAction` — solicitud desde el portal familiar.
- `ConfirmEnrollmentAction` — confirma con fecha de incorporación sugerida.
- `EnrollStudentAction` — alta directa desde admin (precio socio/no socio, anti-duplicado, deriva a waitlist si no hay plazas).
- `MoveToWaitlistAction` / `PromoteFromWaitlistAction` — gestión de lista de espera (promoción siempre manual, nunca automática).
- `MarkPendingPaymentAction` / `RegisterPaymentAction` / `VoidPaymentAction` — ciclo de pago manual.
- `DropEnrollmentAction` / `CancelEnrollmentAction` — baja / cancelación.
- `SyncGroupStatusAction` — sincroniza el estado del grupo (abierto/completo) según plazas; no pisa `closed` ni `archived`.

`delete` de inscripciones solo visible para `super_admin`; el flujo normal es dar de baja o cancelar.

## 7. Convenciones importantes

- No usar el modelo `Family` directamente como entidad gestionable en Filament fuera del `FamilyResource` existente; la relación familia↔usuario se resuelve siempre vía `Guardian.user_id` (ver `App\Models\User`), no se expone login de familias en el panel admin.
- No publicar cambios sin tests que los cubran (happy path + fallos + edge cases). Ver reglas PHPUnit en `CLAUDE.md`.
- No mezclar upgrades de framework o dependencias mayores con desarrollo de features; realizarlos siempre en una rama técnica separada.
- No añadir paquetes/dependencias npm o Composer sin decisión explícita del usuario.
- **Autorización en Filament vía Policy, no vía `Resource::canEdit()`/`canDelete()`**: al pasar de Filament 4.13.2 a 4.16.2 (y se mantiene en 5.x) las páginas de edición/borrado pasaron a autorizar con la policy (`authorizeEdit()` → Gate), por lo que sobreescribir `can*()` en el Resource solo afecta a la visibilidad de la UI, no protege la ruta. Toda regla de acceso por registro (p. ej. que `junta_ampa` no pueda editar a un `super_admin`) debe vivir en la policy (`app/Policies/UserPolicy.php`).
- Mantener fases pequeñas y testeadas — cada módulo se ha añadido con su propia migración, tests y (si aplica) resource Filament, no en bloques grandes.
- Convenciones de estructura Filament 4 (heredadas de memoria de sesiones previas, verificar si cambian):
  - `app/Filament/Resources/{Plural}/{Model}Resource.php`
  - `.../Schemas/{Model}Form.php` (usa `Filament\Schemas\Schema`, no `Filament\Forms`)
  - `.../Tables/{Plural}Table.php`
  - `.../Pages/List|Create|Edit{Model}.php`
  - Layout containers (`Section`, `Grid`, `Fieldset`) → `Filament\Schemas\Components\*`; campos de formulario siguen en `Filament\Forms\Components\*`.

## 8. Próximas fases recomendadas (no implementadas)

- Exportación `.ics`.
- Integración GIR.
- Importadores (de datos existentes, matrícula, etc.).
- Banco de libros.

## 9. Cómo levantar y verificar el proyecto en local

```bash
composer install
cp .env.example .env
php artisan key:generate
# Configurar DB_* en .env (MySQL recomendado; SQLite funciona para desarrollo rápido)
php artisan migrate --seed
npm install && npm run build   # o npm run dev / composer run dev para todo junto
```

- **Seeders siempre activos** (cualquier entorno, idempotentes, sin credenciales): `RoleSeeder`, `AcademicYearSeeder`, `AppSettingsSeeder`.
- **Seeders solo en entorno `local`**: `AdminUserSeeder` (usuario demo `admin@ampa.test` / `password`, rol `super_admin`), `DemoDataSeeder` (datos de ejemplo). Hay además `LocalDemoSeeder` para enriquecer datos de demo/QA visual — también restringido a `local`.
- **Producción**: crear el primer admin con `php artisan ampa:create-admin` (`App\Console\Commands\CreateAdminCommand`); `AdminUserSeeder` se niega a ejecutarse fuera de `local`.
- **Panel admin**: `/admin`. **Zona familiar**: `/familia`. **Front público**: `/`.
- **Tests**: `php artisan test --compact` (659 tests / 1759 aserciones pasando a fecha de este documento, sin deprecations, incluye los 22 de `FamilyCalendarTest.php`; DB SQLite en memoria vía `phpunit.xml`; requiere PHP 8.5 con `pdo_sqlite`). Para un archivo: `php artisan test --compact tests/Feature/NombreTest.php`. Para un test concreto: `php artisan test --compact --filter=nombreTest`.
- **Entorno PHP 8.5 en Windows/Laragon**: `composer.json` exige PHP `^8.5` y `vendor/autoload.php` aborta con una versión inferior. Laragon trae PHP 8.5 NTS (`C:/laragon/bin/php/php-8.5.*`), pero sin `php.ini` ni extensiones activas; hace falta un `php.ini` con `curl, fileinfo, gd, intl, mbstring, exif, mysqli, openssl, pdo_mysql, pdo_sqlite, sodium, sqlite3, xsl, zip`. Las compilaciones NTS no sirven para `mod_php` de Apache: el Apache de Laragon (configurado con `php-8.3.16 ... php8apache2_4.dll`) no puede servir este proyecto hasta cambiarlo a un PHP 8.5 vía FastCGI/Nginx. Con `php artisan serve` el subproceso no hereda `PHPRC`; usar `php -c <ini> -S 127.0.0.1:8000 -t public` desde `public/` o configurar el `php.ini` de Laragon.
- **Formato de código**: `vendor/bin/pint --dirty --format agent` tras tocar PHP.

## 10. Identidad visual

La identidad visual definitiva es **petróleo + terracota** (violeta retirado) — contrato completo en [`DESIGN.md`](../DESIGN.md). `primary_color`/`accent_color` de `AppSettings`/`BrandingSettings` siguen siendo configurables por el AMPA vía Filament; los hex de arriba son ahora sus **valores por defecto** (`AppSettings::$defaults`, `AppSettingsSeeder`, `LocalDemoSeeder`), no un color hardcodeado. La paleta de 6 colores por hijo/a del calendario familiar también se actualizó (ya no protagonizada por el violeta) — ver `DESIGN.md` §3.5.

**Renovación visual — implementada y fusionada a `main`** (rama `feature/visual-refresh`):

- **Home pública** rediseñada con enfoque de comunidad educativa — ver `DESIGN.md` §26.
- **Dashboard familiar** rediseñado con jerarquía de tareas ("Pendiente de ti" como panel dominante) — ver `DESIGN.md` §27.
- **Calendario semanal** refinado (contenedor propio, información mínima por sesión) — ver `DESIGN.md` §23.2.
- **Resto del front público** (`about`, `extracurriculars`, `contact`, `announcements`) homogeneizado al mismo lenguaje visual que Home: sin emoji estructurales (SVG lineal en su lugar), CTA plano sin degradado, navegación activa no dependiente solo del color, menú móvil accesible por teclado.
- **Formularios familiares** (`familia/forms/partials/field.blade.php`) migrados del sistema de utilidades Tailwind (paralelo, no documentado) al sistema `family-*` del resto de la zona familiar.
- **Foco visible** (`:focus-visible`) global en ambos layouts, no solo en campos de formulario.

Documento de referencia completo, incluida la lista de fases y lo que queda pendiente (solo extracción de tokens compartidos y unificación de radios/anchos máximos): `DESIGN.md` §25.
