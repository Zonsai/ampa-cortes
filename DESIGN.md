# Sistema de diseño — Portal AMPA Cortés de Aragón

> Contrato visual del proyecto. Cualquier tarea de UI (nueva pantalla, refactor visual, revisión) debe leer este documento primero y respetarlo salvo decisión explícita en contra.
> Generado a partir del CSS y las vistas reales del repositorio (`resources/views/public/layouts/app.blade.php`, `resources/views/familia/layouts/app.blade.php`), no de una plantilla genérica.
> Ámbito: front público (`/`) y zona familiar (`/familia`). El panel admin (`/admin`) usa el tema de Filament 4 y queda fuera de este documento salvo en la sección de tokens de marca compartidos (colores de marca configurables).

## 0. Cómo usar este documento

- Es aditivo: describe y ordena lo que ya existe, no reemplaza el CSS actual de golpe. Las fases de aplicación están en `DESIGN.md` §25.
- **Identidad definitiva (actualizado):** el violeta queda retirado como color principal recomendado. La identidad del proyecto es **petróleo + terracota** — ver §3. Esta decisión se tomó tras comparar 3 variantes cromáticas aplicadas al mismo diseño (Home/Dashboard/Calendario) y quedó fijada como definitiva.
- Los tokens de marca (`--brand-primary`, `--brand-accent` — documentado como *brand-secondary*, ver §3.1) son **configurables por el AMPA** vía `AppSettings`/`BrandingSettings` — nunca se hardcodean colores de marca en una vista nueva; siempre se referencian estas variables. Los valores de §3.1 son los **valores por defecto** del mecanismo, no un color fijo: cualquier AMPA puede seguir sustituyéndolos desde el panel sin tocar código.
- Este proyecto no usa ningún framework CSS ni componentes de terceros. Todo se implementa en Blade + CSS plano dentro de los `<style>` de cada layout (`public/layouts/app.blade.php`, `familia/layouts/app.blade.php`). Este documento respeta esa restricción explícitamente: ninguna recomendación requiere Tailwind, un bundler nuevo o una librería de iconos instalada vía npm.

## 1. Principios de diseño

1. **Claridad antes que estilo.** Una familia con prisa por la mañana debe entender en 3 segundos qué tiene pendiente. La jerarquía visual (tamaño, peso, espaciado) hace el trabajo; el color decora, no organiza.
2. **Confianza institucional, no corporativa fría.** Es una asociación de madres y padres, no una startup. El tono es cálido y cercano, pero serio: gestiona datos de menores, pagos y consentimientos legales.
3. **Cercanía sin infantilizar.** El destinatario es un adulto (madre, padre, tutor/a), no el alumnado. Nada de iconografía de app infantil, colores saturados tipo juguete, ni tono desenfadado en exceso.
4. **Una fuente de verdad visual, no dos.** Hoy `pub-*` (público) y `family-*` (familias) definen tokens casi idénticos por separado. Este documento es el paso previo a unificarlos; mientras tanto, cualquier valor nuevo debe ser coherente entre ambos.
5. **Accesible por defecto, no como añadido.** El público incluye personas con distinto nivel de destreza digital, posible baja visión o uso desde el móvil en la calle. Contraste, tamaño de toque y foco visible no son opcionales.
6. **Ligero de verdad.** Sin build nuevo, sin JS de terceros, sin fuentes externas descargadas en cada visita. La velocidad percibida es parte de la confianza.

## 2. Personalidad visual

**Es:** cercano, ordenado, sereno, legible, adulto-cálido, institucional-humano (piensa en una intranet escolar bien hecha, o en el tono de Google for Education — organizado y con propósito, sin ser corporativo).

**No es:** SaaS genérico de startup, corporativo gris y frío, infantil/juguetón, recargado de decoración, glassmorphism, degradados como recurso decorativo por defecto, tarjetas anidadas dentro de tarjetas, ni con aspecto de plantilla generada automáticamente.

Referencia conceptual (sin copiar marca): interfaces educativas institucionales modernas — mucho blanco, un acento de color con propósito (nunca decorativo puro), tipografía del sistema, iconografía lineal discreta, jerarquía por espaciado más que por color.

### 2.1 Personalidad por área

El proyecto tiene **tres contextos con tono distinto**, no un único registro para todo el frontend:

| Área | Tono | Notas |
|---|---|---|
| **Público** (`/`) | Más expresivo, editorial y comunitario. Composición menos rígida, mayor contraste tipográfico, superficies cálidas puntuales, fotografía/ilustración real cuando exista el asset. Debe transmitir colegio y comunidad, no software. | Ver §26 Home para la dirección aprobada (documentada, pendiente de implementar). |
| **Familias** (`/familia`) | Más calmado y funcional. Prioriza comprensión inmediata, accesibilidad y tareas (pendientes, hijos/as, horarios, formularios, consentimientos). Puede tener personalidad, nunca a costa de usabilidad. | Ver §27 Familias. |
| **Admin** (`/admin`) | Filament 4, operativo. **Fuera del alcance de este rediseño visual** — no se toca su tema. Solo comparte los tokens de marca configurables (§3.1) a través de `AppSettings`. | Sin cambios previstos. |

## 3. Paleta y tokens semánticos

### 3.1 Marca — identidad definitiva (configurable, no tocar el mecanismo)

El violeta queda **retirado** como color principal recomendado. Identidad aprobada: **petróleo + terracota**, elegida tras comparar 3 variantes cromáticas (petróleo/terracota, azul tinta/mostaza, verde bosque/arena) aplicadas al mismo diseño de Home/Dashboard/Calendario. Se descartó el violeta por acercar el portal a una estética SaaS genérica; se descartó azul tinta por leer demasiado "portal institucional frío"; se descartó verde bosque por riesgo de leerse como marca ecológica.

```css
--brand-primary:   {{ $branding['primary_color'] ?? '#245b63' }};  /* petróleo — color de marca, configurable por el AMPA */
--brand-secondary: {{ $branding['accent_color']  ?? '#4f7c70' }};  /* verde petróleo claro — acento secundario, configurable por el AMPA */
```

**Nota de implementación:** en el CSS real, `--brand-secondary` se implementa como `--brand-accent` (mismo campo `accent_color` de `AppSettings`, sin renombrar la variable en el código para no romper el campo ya existente) — mismo significado, nombre distinto por continuidad con el campo de base de datos.

`--brand-primary-dark` (`#183f46`) se documenta como referencia para futuros estados oscuros/hover del primary, pero **no está cableado como variable CSS todavía**: el hover actual (`filter: brightness(.93)` en `.pub-btn--primary`/`.family-btn--primary`) ya logra el mismo efecto sin necesitar un tercer token. Si en el futuro se cablea, debe derivarse con `color-mix(in srgb, var(--brand-primary) 82%, black)` — nunca un hex fijo — para seguir funcionando si una AMPA configura un primary distinto.

**Mecanismo de configuración (sin cambios):** `primary_color`/`accent_color` siguen siendo los dos únicos campos editables desde `BrandingSettings` (Filament) vía `AppSettings::$defaults` / `AppSettingsSeeder`. Los valores de arriba son ahora los **valores por defecto** del sistema — no un color hardcodeado — cualquier AMPA que despliegue el proyecto puede seguir sustituyéndolos desde el panel sin tocar código ni migraciones.

### 3.2 Acento cálido de identidad (nuevo — terracota)

```css
--brand-warm:      #d87552;  /* terracota — acento de identidad/comunidad */
--brand-warm-soft: #f4e3da;  /* fondo suave del acento cálido, para insignias o superficies puntuales */
```

**Restricciones — regla dura:**
- Es un acento de **identidad**, no de marca ni de estado. **Nunca** sustituye a `--brand-primary` en botones, enlaces o foco.
- Uso permitido: detalles decorativos puntuales (guion junto a un eyebrow, insignia de sección, filete de footer), iconos aislados, superficies pequeñas (`--brand-warm-soft`).
- **Nunca** se usa como color semántico — no sustituye a success/warning/danger/info (§3.4).
- **Nunca** como color de texto de cuerpo pequeño: su contraste sobre blanco es ~3,2:1 (verificado con la fórmula WCAG), suficiente para iconos/objetos gráficos y texto grande, insuficiente para texto normal.
- **No es configurable** vía `AppSettings` — es un token fijo del sistema de diseño, igual que los tokens semánticos de §3.4. Solo `--brand-primary`/`--brand-secondary` lo son.

### 3.3 Superficies y texto

```css
--surface-warm: #faf8f4;  /* superficie cálida — uso puntual, principalmente público */
--surface-soft: #f3f5f4;  /* superficie neutra sunken — unifica --pub-soft y --fam-bg */
--ink:          #172026;
--ink-muted:    #66727a;
--ink-faint:    #9ca3af;  /* solo decorativo, nunca texto con información — ver §20 */
--border:       #dee4e1;
```

Estos nombres son los definitivos y sustituyen a `--color-surface-sunken`/`--color-ink`/`--color-ink-muted`/`--color-border` de un borrador anterior de este documento — mismo significado y misma regla de declarar una sola vez (ver §25 Fase 0), solo cambia el nombre final. Declarar en un partial compartido, no duplicar en cada `<style>` de layout.

### 3.4 Estado (sin cambios — independientes de la identidad)

```css
--color-success:      #15803d;  --color-success-bg: #dcfce7;
--color-warning:      #b45309;  --color-warning-bg: #fef3c7;
--color-danger:       #b91c1c;  --color-danger-bg:  #fee2e2;
--color-info:         #1d4ed8;  --color-info-bg:    #dbeafe;
--color-neutral:      #6b7280;  --color-neutral-bg: #f3f4f6;
```

Ninguno de estos tokens cambia con la identidad de marca — regla explícita de esta fase: el color de estado nunca depende del color de marca ni del acento cálido.

### 3.5 Paleta de hijos/as (calendario)

Con el violeta retirado de la identidad, se sustituye también la paleta de 6 colores por hijo/a del calendario (antes protagonizada por el violeta como swatch 1). Nueva paleta, coherente con petróleo + terracota pero deliberadamente **fuera** del sistema de marca y del sistema semántico — codifica identidad de alumno/a, no estado (implementada en `.family-cal-swatch-1..6` / `.family-cal-session--s1..6`, `resources/views/familia/layouts/app.blade.php`):

```css
--child-1: #3b6e8f;  /* azul petróleo */
--child-2: #0e9488;  /* teal */
--child-3: #c2670f;  /* naranja */
--child-4: #b5697a;  /* rosa apagado */
--child-5: #a6862c;  /* ocre */
--child-6: #4b5a63;  /* pizarra */
```

Los seis dan ≥3:1 de contraste sobre blanco (uso como borde/punto de color, nunca como texto de cuerpo). Ninguno coincide con los tokens semánticos de §3.4 — el naranja `#c2670f` es deliberadamente distinto del ámbar de aviso (`#b45309`/`#d97706`), para no confundir visualmente "hijo/a" con "estado pendiente de pago".

**Regla:** ningún componente nuevo usa un hex suelto para texto, fondo o borde salvo estos 6 colores de identidad de alumno/a (§23), deliberadamente fuera del sistema semántico y de marca.

### 3.6 Uso de color con propósito

- El petróleo (`--brand-primary`) se reserva para: marca, enlaces activos, acción principal, foco. **No** se usa como fondo decorativo de secciones enteras.
- El terracota (`--brand-warm`) se reserva para identidad/comunidad — ver restricciones en §3.2.
- El verde (`--color-success`) significa siempre "completado/pagado/aceptado" — nunca decorativo.
- El ámbar (`--color-warning`) significa "pendiente de acción de la familia".
- El rojo (`--color-danger`) se reserva para cancelaciones, bajas y errores — nunca para simple énfasis.
- Ningún estado se comunica solo con color: siempre va acompañado de texto o icono (ya se cumple en los `family-status-badge` actuales, que llevan texto; mantenerlo).

## 4. Tipografía

Se mantiene la pila de fuentes del sistema ya usada en ambos layouts — es la decisión correcta para este proyecto y no debe cambiarse a una fuente de Google Fonts:

```css
font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
```

**Por qué se mantiene (no es una omisión):** cero peticiones de red, cero parpadeo de carga (FOIT/FOUT), renderizado nativo y coherente con el SO de cada familia, y evita depender de un CDN externo — coherente con "sin dependencias nuevas". Es además la elección tipográfica típica de portales institucionales serios (gob.es, universidades, banca) frente a fuentes de display propias de producto SaaS.

### 4.1 Escala tipográfica (formalización de lo ya usado)

| Token | Tamaño | Peso | Uso actual |
|---|---|---|---|
| `--text-display` | `clamp(1.9rem, 4vw, 2.8rem)` | 800 | `h1` público (hero) |
| `--text-h1` | `1.5rem` | 700 | `.family-page-header h1` |
| `--text-h2` | `clamp(1.4rem, 3vw, 1.9rem)` | 700 | `h2` secciones públicas |
| `--text-h3` | `1.1rem` | 700 | `h3`, `.family-card__title` |
| `--text-body` | `1rem` (16px) | 400 | párrafos, tablas |
| `--text-small` | `0.85–0.9rem` | 400–500 | metadatos, `.family-row__sub` |
| `--text-micro` | `0.7–0.76rem` | 600–700, uppercase, `letter-spacing: .04–.06em` | etiquetas, eyebrows, badges |

**Regla:** nunca bajar de `0.72rem` para texto con información (no solo decorativo), y nunca de `16px` en inputs (evita el auto-zoom de iOS en formularios — ya se cumple en `.family-input`/`.pub-btn`, mantenerlo).

## 5. Espaciado

No existe hoy una escala explícita; los valores están cerca de una escala de 4px pero con excepciones sueltas (`14px`, `22px`). Se propone adoptar y converger hacia:

```
4, 8, 12, 16, 20, 24, 32, 40, 56, 64px
```

Mapeo con lo ya existente: padding de tarjeta (`.pub-card`, `.family-card__body`) → `16–24px`; gap entre elementos de fila → `8–12px`; separación entre secciones (`.family-section`, `.pub-section`) → `24–56px`. No es necesario reescribir los valores actuales de golpe; los componentes nuevos deben usar solo valores de esta escala.

## 6. Anchos máximos

| Contenedor | Ancho actual | Nota |
|---|---|---|
| `.pub-container` | `1080px` | Front público |
| `.family-container` | `1040px` | Zona familiar |
| `.family-auth` | `420px` | Login |
| `.pub-article`, `.pub-prose` | `760px` | Texto largo — correcto para legibilidad (65–75 caracteres/línea) |

Los 1080/1040px son deliberadamente más estrechos que el `1200–1280px` típico de SaaS — es una fortaleza (mejor legibilidad, menos sensación de "dashboard vacío"). Se recomienda converger ambos a **1080px** en la fase de unificación (§25), sin prisa: no es una inconsistencia visible para el usuario final.

## 7. Radios

| Token | Valor | Uso |
|---|---|---|
| `--radius-sm` | `10px` | inputs, botones pequeños, iconos |
| `--radius-md` | `14–16px` | tarjetas, paneles |
| `--radius-full` | `999px` | chips, badges, pills |

Se unifica `--pub-radius: 16px` y `--fam-radius: 14px` a un único valor medio (**14px**) en la fase de unificación. **No subir de 16px** en ningún radio nuevo: por encima de eso el lenguaje visual empieza a leerse como "claymorphism"/infantil, que es explícitamente lo que se quiere evitar.

## 8. Bordes

Un solo estilo en todo el proyecto: `1px solid var(--color-border)` (`#e5e7eb`). No se usan bordes gruesos (3–4px) ni de color saturado — eso pertenece a estilos tipo claymorphism/brutalism, fuera de la dirección elegida. Los bordes con color de marca (`color-mix(in srgb, var(--brand-primary) 40%, var(--pub-border))`) se reservan para estados de *hover* puntuales, no para el estado de reposo.

## 9. Sombras / elevación

Ya existe una escala sutil y correcta — es una fortaleza a conservar tal cual:

```css
--shadow-1: 0 1px 2px rgba(16,24,40,.04), 0 1px 3px rgba(16,24,40,.05); /* reposo: family-card, pub-card */
--shadow-2: 0 6px 18px rgba(16,24,40,.09);                              /* hover / elevado */
```

**Regla:** máximo 2 niveles. Nunca sombras duras (`box-shadow: 4px 4px 0 #000`, estilo neobrutalismo) ni sombras de color saturado. Nunca `backdrop-filter: blur()` como recurso decorativo (ver §16 Don't — el header público actual lo usa y se recomienda retirarlo).

## 10. Botones

Ya existe un sistema coherente entre zonas (`.pub-btn` / `.family-btn`) — formalizado aquí como referencia única:

| Variante | Fondo | Texto | Uso |
|---|---|---|---|
| Primario | `var(--brand-primary)` | blanco | una sola acción principal por vista |
| Ghost/secundario | blanco, borde `--color-border` | `--color-ink` | acciones secundarias |
| Muted | `#f3f4f6` | `#374151` | acciones terciarias, "Esta semana" |
| Warning | `--color-warning-bg` | `--color-warning` | acciones de baja severidad con matiz |
| Danger | `--color-danger-bg` | `--color-danger` | cancelar, dar de baja |

Tamaño táctil mínimo: **44×44px de área de toque** en móvil (ya se cumple con `padding: 9px 16px` + `font-size: .88rem`, verificar en cada componente nuevo). Radio `--radius-sm`. Un único botón primario por pantalla/sección — no dos CTAs primarios compitiendo.

## 11. Enlaces

- Enlace de texto: `color: var(--brand-primary)`, sin subrayado en reposo, subrayado al *hover* (ya implementado en `.family-link`, `.pub-back`). Mantener.
- Enlace de navegación activo: color de marca + indicador de borde inferior de 2px (ya implementado en `.family-nav a.is-active`). Mantener — es una forma no dependiente-solo-del-color de indicar el estado (hay además peso de fuente 600).

## 12. Tarjetas

Un único nivel de tarjeta: fondo blanco, borde `1px solid var(--color-border)`, radio `--radius-md`, `--shadow-1`. **Regla dura pedida explícitamente: nunca una tarjeta dentro de otra tarjeta.** Si un bloque necesita subdivisión interna, se usa una fila con separador (`border-top: 1px solid #f4f4f5`, patrón ya usado en `.family-row + .family-row`), no una tarjeta anidada.

Los iconos decorativos dentro de tarjeta (`.pub-card__icon`, círculo de color relleno) se mantienen pero se simplifican: un solo tono por icono (no relleno saturado), y se sustituye el emoji actual por SVG lineal (ver §15).

## 13. Formularios

Ya siguen las convenciones correctas de Laravel/Blade (`@error`, `old()`, `@csrf` — verificado en `AuthController`, `EnrollmentController`). El sistema visual:

- Label siempre visible encima del campo (`.family-label`), nunca solo *placeholder* como label.
- Error específico debajo del campo (`.family-field-error`, color `--color-danger`), nunca solo un resumen arriba del formulario.
- Foco: borde de marca + halo (`box-shadow: 0 0 0 3px color-mix(...)`) — ya implementado en `.family-input:focus`, es el patrón correcto, replicar en cualquier campo nuevo.
- Campos obligatorios: asterisco en el label (`.family-label .req`), no solo validación silenciosa.
- Altura mínima de campo: 44px de área táctil en móvil.
- Validar en *blur*, no en cada pulsación, para no interrumpir a alguien escribiendo con poca destreza digital.

## 14. Tablas / listados

Dos patrones ya existentes, mantener ambos según densidad de datos:

- **Tabla real** (`.family-table`) para listados densos con columnas comparables.
- **Lista de filas** (`.family-row`, separador superior) para listados con metadatos variables por ítem (más legible en móvil que una tabla con scroll horizontal).

**Regla:** nunca scroll horizontal forzado en móvil. Si una tabla no cabe, se convierte en lista de filas por debajo de 640px en vez de envolverla en `overflow-x: auto` sin más (hoy `.family-table-wrap { overflow-x: auto }` es un parche aceptable a corto plazo, no el objetivo final).

## 15. Badges y estados

El sistema `.family-status-badge` + modificadores (`.status-success`, `.status-warning`, `.status-info`, `.status-pending`, `.status-danger`, `.status-muted`) ya mapea 1:1 con los tokens semánticos de §3.4 — es correcto, solo hay que asegurarse de que todo estado nuevo (por ejemplo, futuros estados de calendario) reutiliza esta paleta de 6 en vez de inventar colores nuevos.

## 16. Navegación

- Público: barra superior con marca + navegación horizontal + acciones (`Acceso familias`, `Gestión AMPA`) + menú móvil por `<details>`/checkbox CSS (sin JS). Mantener el patrón, pero ver §20 sobre el `backdrop-filter`.
- Familias: barra superior + pestañas horizontales con scroll (`family-nav`), estado activo por color + borde inferior, contador de pendientes en pestañas (`family-nav__count`). Correcto y accesible (no depende solo de color). Al añadir "Calendario" a esta nav (ya hecho en `feature/family-calendar`, sin tocar) se mantiene el mismo patrón — no requiere cambios de este sistema de diseño.
- **Regla:** la navegación nunca cambia de patrón entre páginas de la misma zona. No mezclar tabs + sidebar + bottom nav.

## 17. Estados vacíos

Patrón ya existente y correcto (`.family-empty-state`, `.pub-empty`): título breve + una frase de ayuda + una acción clara (enlace o botón). Aplicado ya en el calendario en desarrollo ("No hay actividades esta semana" + enlace a Extraescolares). Mantener esta fórmula para cualquier estado vacío nuevo: **nunca una pantalla en blanco sin explicación.**

## 18. Feedback y alertas

`.family-flash` (éxito/error tras una acción) y `.family-alert`/`.pub-*` equivalentes ya cubren info/warning/success/danger con los mismos tonos de §3.4. Reglas a mantener:

- Confirmación antes de acciones destructivas (baja, cancelación) — ya implementado en los controladores de familia.
- Mensaje de error con causa + cómo solucionarlo, no un genérico "Ha ocurrido un error".
- Un flash desaparece de la vista tras navegar (ya es el comportamiento con `session('success')`/`session('error')`), no requiere temporizador JS.

## 19. Responsive

Mobile-first ya aplicado de forma consistente (`@media (min-width: …)` para ampliar, nunca para ocultar contenido esencial). Puntos de corte ya en uso, formalizados:

```
560px  → 2 columnas en grids de estadística/acción
640px  → 2 columnas en grids públicos
768px  → (reservado, no usado aún — usar si aparece un caso intermedio)
860px  → navegación pública pasa a menú móvil; 3ª columna en grids públicos; grid del hero
920px  → 4 columnas en `.family-stats-grid`
```

**Regla:** nunca deshabilitar el zoom (`user-scalable=no`) — el `<meta viewport>` actual ya es correcto, no tocarlo. Ancho mínimo de prueba: 375px. Nada de scroll horizontal salvo listas explícitamente diseñadas para ello (`.family-nav`, con scroll-snap implícito).

## 20. Accesibilidad

- Contraste mínimo 4.5:1 para texto normal. Verificar especialmente `--color-ink-faint` (`#9ca3af`) sobre blanco — está en el límite y **no debe usarse para texto con información**, solo para elementos puramente decorativos (ya se usa así en `.family-empty-state__title` complementado con negro/gris oscuro para el título).
- El foco visible ya existe en inputs (`.family-input:focus`) pero **falta en enlaces y botones** de ambos layouts — no hay ningún `:focus-visible` definido fuera de los campos de formulario. Es el hueco de accesibilidad más importante detectado (ver §21 diagnóstico).
- Los iconos decorativos junto a texto visible deben llevar `aria-hidden="true"`; los iconos-botón sin texto necesitan `aria-label`.
- Ningún estado se comunica solo por color (ya cumplido, mantener).

## 21. Focus

Se define un anillo de foco único para todo el proyecto, visible con teclado (`:focus-visible`, no `:focus` a secas para no mostrarlo en clic de ratón):

```css
:focus-visible {
    outline: 2px solid var(--brand-primary);
    outline-offset: 2px;
    border-radius: 4px;
}
```

Esto reutiliza literalmente el halo que ya existe en `.family-input:focus` pero lo extiende a enlaces, botones y chips, que hoy no tienen indicador de foco propio.

## 22. Iconografía

**Situación actual:** el front público usa **emoji como iconos estructurales** (🎨 📝 ✅ 📣 en `home.blade.php`, ☰ como icono de menú). Es el hallazgo de diseño más claro de esta revisión: los emoji son dependientes de la fuente del sistema operativo, no se pueden themar, cambian de aspecto entre Windows/Mac/Android y son la señal más reconocible de "interfaz genérica/generada". La zona familiar ya hace lo correcto en algunos puntos (icono SVG inline en `.family-pending-item__icon`).

**Regla:** iconos SVG en línea (inline `<svg>`, sin librería npm — coherente con "sin dependencias nuevas"), trazo lineal (`stroke`, no relleno sólido saturado), grosor de trazo consistente (1.5–2px), tamaño en pasos fijos (16/20/24px). Referencia de estilo si se necesita elegir trazado nuevo: familia visual tipo Phosphor/Heroicons *outline* — se copia el `<path>` a mano en el Blade, no se instala el paquete.

## 23. Calendario semanal

### 23.1 Ya implementado (real, en `feature/family-calendar`, sin fusionar todavía)

- Consume `ScheduledSessionCalculator` sin duplicar lógica de recurrencia — correcto, no tocar.
- Excepciones `Modified` inline con indicación discreta + motivo; `Cancelled` como aviso separado en "Avisos de esta semana", nunca como sesión — correcto, coincide con §18 (feedback claro, sin ruido).
- Filtro por hijo/a y navegación semanal (`?week=`, `?student=`) — correctos, sin cambios.
- **Timetable desktop (≥860px)**: eje horario compartido a la izquierda, posición y duración de cada sesión calculadas en `CalendarController::buildTimetable()` en minutos desde el inicio del rango visible (sin JavaScript, sin librería de calendario) y expuestas como `top`/`height` vía `calc()`. Los solapamientos reales entre hijos/as se resuelven con partición de intervalos (`assignLanes()`/`layoutCluster()`): cada sesión recibe un `lane` y el total de `lanes` de su grupo de solape, y se muestran lado a lado dentro de la columna del día — nunca una tapa a otra.
- **Agenda vertical en móvil** (`.family-cal-agenda`, <860px): misma estructura de siempre, sin cambios, ocultada en desktop vía CSS (`display:none`) en favor del timetable.
- Paleta de 6 colores fija por hijo/a — **ver §3.5, ya no violeta**.
- Cubierto por 19 tests en `tests/Feature/FamilyCalendarTest.php` (acceso, filtros, navegación, excepciones, timetable: posición/duración/lanes/límites).

### 23.2 Refinamiento visual pendiente (documentado, no implementado — fuera de esta fase)

Diagnóstico tras revisar el timetable ya construido: es funcionalmente correcto, pero visualmente puede mejorar en tres puntos concretos, ninguno de los cuales toca `ScheduledSessionCalculator` ni la lógica de solapamiento ya resuelta:

1. **Contenedor propio, más ancho.** El calendario hoy hereda `.family-container` (1040px), igual que el resto de la zona familiar. Con un contenedor dedicado (~1400px) el timetable deja de sentirse estrecho y los solapamientos de 2 lanes ganan legibilidad sin necesitar más cambios.
2. **Información mínima por sesión.** Hoy cada tarjeta de sesión siempre muestra hora + actividad + grupo + ubicación + alumno/a. Se propone reducir a hora + actividad + hijo/a (punto de color + nombre) siempre visibles; ubicación solo si cabe en una línea; **el nombre del grupo se omite salvo que dos grupos de la misma actividad convivan esa semana** (hoy siempre se muestra, redundante en la mayoría de los casos).
3. **Política de solapamientos con 3+ lanes.** El algoritmo de lanes ya soporta cualquier número de solapes simultáneos, pero visualmente no se ha decidido qué hacer a partir de 3 sesiones a la misma hora en un contenedor de ancho normal (columnas demasiado estrechas). Con el contenedor ancho de (1) es un caso raro; si aparece, la opción a explorar es limitar a 2 lanes visibles + indicador "+N", a decidir en la fase de implementación.

Estos tres puntos se abordan en su propia fase (ver §25), en su propia rama, después de fusionar `feature/family-calendar`.

## 24. Reglas Do / Don't

### Do
- Un único color de acción primaria por pantalla (petróleo de marca).
- Espaciado generoso entre secciones; la jerarquía la da el espacio, no el color.
- Un nivel de tarjeta, bordes de 1px, sombra sutil de 1–2 niveles.
- Iconos SVG lineales, mismo grosor de trazo en todo el proyecto.
- Foco visible en todo elemento interactivo (`:focus-visible`).
- Mensajes vacíos y de error con una acción de salida clara.
- Mobile-first real: agenda vertical en calendario, tarjetas apiladas, sin scroll horizontal forzado.
- Reutilizar los 6 tokens de estado (`success/warning/danger/info/neutral/pending`) para cualquier badge nuevo.
- El terracota (`--brand-warm`) solo en detalles/iconos/superficies puntuales — nunca en botones ni como sustituto del primary (§3.2).

### Don't
- **No** reintroducir el violeta como color principal — la identidad definitiva es petróleo + terracota (§3.1). Un componente nuevo que necesite un color de marca usa `--brand-primary`/`--brand-secondary`, nunca un hex de violeta.
- **No** usar emoji como icono estructural (sustituir 🎨📝✅📣☰ del front público en la fase de aplicación).
- **No** usar `backdrop-filter: blur()` decorativo (retirar del `.pub-header` en la fase de aplicación).
- **No** apilar más de un degradado por página (hoy el hero público y el CTA público llevan degradado cada uno — simplificar a uno, preferiblemente el CTA, y dejar el hero en color plano o con un único detalle de acento).
- **No** anidar una tarjeta dentro de otra tarjeta — usar filas con separador.
- **No** subir el radio de esquina por encima de 16px — evita la lectura "claymorphism"/infantil.
- **No** usar colores saturados de más de un acento por componente (un badge no lleva fondo Y texto Y borde todos de colores distintos).
- **No** instalar una fuente de Google Fonts ni una librería de iconos vía npm — todo vive en el `<style>` de cada layout o en SVG inline.
- **No** duplicar tokens: cualquier valor nuevo se añade al partial compartido descrito en §25, no a un `<style>` de layout suelto.
- **No** comunicar un estado solo con color sin texto o icono de apoyo.

## 25. Fases de aplicación recomendadas

Ninguna de estas fases está implementada todavía. Se listan en orden de riesgo creciente para poder aplicarlas de forma incremental sin romper funcionalidad ni tests existentes.

1. **Fase 0 — Extraer tokens compartidos.** Crear `resources/views/partials/design-tokens.blade.php` (o equivalente) con las variables de §3.3–3.4 y `@include` en ambos `<style>` de layout. Cero cambio visual: mismos valores, una sola fuente. Riesgo mínimo, sin tests que romper (no hay aserciones sobre CSS).
2. **Fase 1 — Foco visible.** Añadir la regla `:focus-visible` de §21 a ambos layouts. Cero cambio de layout/estructura, solo accesibilidad. Riesgo mínimo.
3. **Fase 2 — Iconografía del front público.** Sustituir los emoji de `public/home.blade.php` (y cualquier otro que aparezca en `about`/`extracurriculars`/`contact`) por SVG inline lineales. Cambia el marcado, no el texto ni las rutas — verificar con los tests de `HomepageTest.php` que siguen buscando el texto visible, no el emoji.
4. **Fase 3 — Simplificar degradados y `backdrop-filter`.** Retirar el `backdrop-filter: blur()` del `.pub-header` y reducir a un único degradado en la página pública (el del CTA). Cambio puramente visual en el layout público, sin tocar rutas ni controladores.
5. **Fase 4 — Unificar radios y anchos máximos.** Converger `--pub-radius`/`--fam-radius` a 14px y los contenedores a 1080px. Revisar visualmente ambas zonas tras el cambio (no hay aserciones de píxeles en los tests).
6. **Fase 5 — Timetable del calendario (§23.1). ✅ Completada.** Eje horario compartido, posición/duración proporcional y solapamientos por lanes implementados en `CalendarController`/`familia/calendar/index.blade.php`, con 19 tests en `FamilyCalendarTest.php`. Pendiente de fusionar `feature/family-calendar` a `main`.
7. **Fase 6 — Identidad definitiva (petróleo + terracota).** ✅ Completada en esta misma actualización: valores por defecto de `AppSettings`/`AppSettingsSeeder`/`LocalDemoSeeder`, fallbacks de ambos layouts y paleta de hijos/as del calendario. Pendiente: el resto de fases de este documento (0–4, 8–9) siguen sin aplicar sobre el nuevo color.
8. **Fase 7 — Refinamiento visual del calendario (§23.2).** Contenedor propio más ancho + jerarquía mínima de información por sesión. Toca `CalendarController` (qué campos expone) y `familia/calendar/index.blade.php`; requiere ampliar `FamilyCalendarTest.php`. En su propia rama, después de fusionar `feature/family-calendar`.
9. **Fase 8 — Recomposición del Home (§26).** Hero asimétrico, sección "Qué ofrecemos" editorial, nueva sección "Quiénes somos", anuncios con jerarquía. Solo `public/home.blade.php` y el layout público.
10. **Fase 9 — Refinamiento del dashboard familiar (§27).** Iconos por categoría, accesos rápidos con icono, ajustes de densidad. Solo Blade/CSS, sin tocar el controlador.

Cada fase es un commit/rama independiente y pequeña, siguiendo la convención ya establecida en el proyecto (`docs/PROJECT_STATE.md` §7: "mantener fases pequeñas y testeadas"). **Ninguna de las fases 7–9 está implementada todavía** — quedan documentadas como dirección aprobada, pendientes de ejecución explícita.

## 26. Home pública — dirección aprobada (documentada, no implementada)

Diagnóstico: la Home actual es correcta pero demasiado parecida a una landing SaaS (hero + 4 tarjetas idénticas + tablón + CTA con degradado). Dirección aprobada, a aplicar en la Fase 8:

- **Header**: un único CTA primario ("Acceso familias"); "Gestión AMPA" baja a enlace de texto — hoy hay dos botones `--primary` compitiendo (header + hero), viola la regla de §10 ("un único botón primario por pantalla").
- **Hero asimétrico**: titular con más contraste tipográfico (más grande, más peso, sin cambiar de fuente — ver §4). El panel lateral deja de ser un checklist de emoji sobre degradado y pasa a un módulo de "prueba de comunidad": cifras reales del curso (marcadas `[Nº]` hasta tener el dato) + una cita real de una familia o de la junta (placeholder explícito hasta recopilarla, nunca inventada).
- **"Qué ofrecemos"**: deja de ser un grid de 4 tarjetas idénticas (ver Don't de §24). Pasa a una composición editorial: un ítem destacado + 3 filas compactas con icono, sin tarjeta individual por ítem.
- **Nueva sección "Quiénes somos"**: no existe hoy. Texto breve sobre la propia comunidad + hueco de fotografía real marcado explícitamente como pendiente (`[FOTO — pendiente de asset real]`) — nunca stock genérico.
- **Anuncios**: el fijado gana tratamiento destacado; el resto pasa a filas compactas en vez de tarjetas repetidas — mismo patrón fila-con-separador que ya usa la zona familiar (§14).
- **CTA final**: un solo panel de color plano cálido (`--surface-warm`), sin degradado; un único botón, el resto como enlace de texto.
- **Iconografía**: sustituir los emoji por los SVG lineales de §22, coloreados con propósito (`--brand-primary` / `--brand-secondary` / `--brand-warm` según el caso, nunca los cuatro iguales).

## 27. Familias — refinamientos aprobados (documentados, no implementados)

Sin reconstrucción — la jerarquía y la lógica de "Pendiente de ti" ya son correctas. Refinamientos aprobados, a aplicar en la Fase 9:

- **Iconos de "Pendiente de ti" con color por categoría** en vez de un único tono indigo/petróleo para los 5 tipos: mapeados a los tokens ya definidos (`--brand-primary` para consentimientos, `--color-neutral` para formularios, `--color-warning` para pago pendiente, `--color-info` para solicitudes) — mejora el escaneo visual sin inventar colores nuevos ni volverse multicolor.
- **Accesos rápidos con icono**, igual que "Pendiente de ti" — hoy son solo texto + flecha, inconsistente con el resto de la iconografía SVG ya presente en la pantalla.
- **Stats**: la tarjeta con dato relevante gana peso tipográfico; las que están en 0 bajan visualmente (gris), para que la jerarquía la dé el contenido, no la posición.
- Todo lo demás se conserva tal cual: `.family-container`, estructura de secciones, tabla de inscripciones, empty states, badges de estado.

---

*Este documento se actualiza cuando cambien decisiones de diseño reales, igual que `docs/PROJECT_STATE.md` para el estado funcional. No debe quedar desincronizado del CSS real del proyecto.*
