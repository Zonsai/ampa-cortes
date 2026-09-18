# Estado del proyecto — Portal AMPA Cortés de Aragón

> Documento de contexto interno. Léelo al empezar cualquier sesión nueva antes de tocar código.
> Generado a partir del estado real del repositorio (código, migraciones, tests, `git log`), no de memoria de chat.
> Última actualización: 2026-09-18 (parches de seguridad de dependencias sobre Laravel 13, ver nota abajo).

## 1. Stack actual

| Componente | Versión |
|---|---|
| PHP | 8.3 |
| Laravel | 13.32.0 (`laravel/framework ^13.0`) |
| Laravel Tinker | v3.0.2 (`^3.0`) |
| Filament | v4.13.2 |
| Livewire | v3.8.9 |
| Spatie Laravel Permission | v6.25 |
| Laravel Excel (maatwebsite/excel) | v3.1.70 |
| PHPUnit | v11.5 |
| Base de datos | MySQL en local/producción (`utf8mb4_unicode_ci`); SQLite en memoria para tests (`phpunit.xml`) |

Upgrade a Laravel 13 ya integrado en `main` (hecho originalmente en la rama técnica `upgrade/laravel-13`, fusionada por fast-forward), cambio mínimo (solo `laravel/framework` y `laravel/tinker`; sin tocar Filament/Livewire/Spatie/Excel/PHPUnit). Ningún breaking change de la guía oficial 12→13 resultó aplicable a este código tras auditoría (CSRF middleware sigue con alias `VerifyCsrfToken`, sin polimorfismos con pivote custom, sin `upsert()`, sin instanciación de modelos en `booted()`, sin notificaciones en cola). 634 tests / 1670 aserciones siguen en verde.

Posteriormente se aplicó una tanda de parches de seguridad (patch/minor, sin subir ninguna major): `filament/filament` 4.11.7→4.13.2, `livewire/livewire` 3.8.1→3.8.9, `maatwebsite/excel` 3.1.69→3.1.70 y `phpoffice/phpspreadsheet` (transitivo) 1.30.5→1.30.7. Resolvió los 8 advisories de `composer audit` vigentes en ese momento (3 en Filament, 1 en Livewire, 1 en Laravel Excel, 3 en PHPSpreadsheet). `composer audit` queda en "No security vulnerability advisories found." 634 tests / 1670 aserciones siguen en verde.

No hay npm/JS custom más allá del scaffolding estándar de Laravel (Vite + Filament assets). No usar paquetes nuevos sin decisión explícita.

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

**Ya existe** (base técnica de horarios, no calendario visual):

- `ActivityGroup`: horario recurrente semanal — `weekdays` (array ISO 1–7), `starts_at`/`ends_at` (hora), `effective_from`/`effective_until` (rango de fechas del grupo, opcional; si es null cae al año académico de la actividad).
- `ActivityGroupException`: excepciones puntuales sobre una fecha recurrente — tres tipos (`App\Enums\ExceptionType`): `cancelled` (se cancela una sesión), `modified` (cambia fecha/hora/ubicación de una sesión), `extra` (sesión añadida fuera del patrón). Cada tipo tiene validación de coherencia en `booted()` (p. ej. una `extra` no puede duplicar una sesión regular).
- `Enrollment.attendance_from` / `attendance_until`: acota el periodo en que un alumno concreto asiste dentro del grupo (puede ser más corto que el rango del grupo). Gestionado por acciones dedicadas (`App\Actions\Enrollments\*`), probado en `tests/Feature/EnrollmentAttendanceActionsTest.php`.
- `App\Services\ScheduledSessionCalculator` + `App\Support\ScheduledSession`: calcula la lista concreta de sesiones (fecha, hora, ubicación) para un grupo o para una inscripción dentro de un rango de fechas, aplicando excepciones y periodo de asistencia. Es la pieza central para cualquier vista de calendario futura. Cubierto por `tests/Feature/ScheduledSessionCalculatorTest.php`.
- Filament: `ActivityGroupResource` con `RelationManager` de excepciones (`ExceptionsRelationManager`), tests en `ActivityGroupExceptionsFilamentTest.php`.

**NO existe todavía**:

- Ninguna vista visual de calendario (ni admin ni familiar). El cálculo de sesiones existe como servicio de dominio, pero no hay UI que lo consuma.
- Calendario familiar semanal (portal `/familia`).
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
- Mantener fases pequeñas y testeadas — cada módulo se ha añadido con su propia migración, tests y (si aplica) resource Filament, no en bloques grandes.
- Convenciones de estructura Filament 4 (heredadas de memoria de sesiones previas, verificar si cambian):
  - `app/Filament/Resources/{Plural}/{Model}Resource.php`
  - `.../Schemas/{Model}Form.php` (usa `Filament\Schemas\Schema`, no `Filament\Forms`)
  - `.../Tables/{Plural}Table.php`
  - `.../Pages/List|Create|Edit{Model}.php`
  - Layout containers (`Section`, `Grid`, `Fieldset`) → `Filament\Schemas\Components\*`; campos de formulario siguen en `Filament\Forms\Components\*`.

## 8. Próximas fases recomendadas (no implementadas)

- Calendario familiar semanal (UI que consuma `ScheduledSessionCalculator`).
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
- **Tests**: `php artisan test --compact` (634 tests / 1670 aserciones pasando a fecha de este documento, DB SQLite en memoria vía `phpunit.xml`). Para un archivo: `php artisan test --compact tests/Feature/NombreTest.php`. Para un test concreto: `php artisan test --compact --filter=nombreTest`.
- **Formato de código**: `vendor/bin/pint --dirty --format agent` tras tocar PHP.
