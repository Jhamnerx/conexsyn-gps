# Tema Conexsyn — colores del template original + estilos propios

**Fecha:** 2026-10-07

## Objetivo

El tema `gpsubicar` (rebranding del design system AirPatrol) pasa a llamarse
`conexsyn` y deja de imponer su propia paleta. Los colores de textos, botones,
fondos, bordes, inputs, tablas, modales, etc. son los del template original de
GPSWOX; se conservan los estilos personalizados (forma, tipografía, espaciado,
movimiento, sombras y los componentes propios).

## Decisiones

- **Template de referencia.** `conexsyn` usa los colores de `light-blue`
  (`colouring/main-blue` + `colouring/base-ligth` + `layout/variables`).
  `conexsyn-dark` usa los de `dark-blue` (`main-blue` + `base-dark`).
  `light-blue` era el default y el tema del que se migró a `gpsubicar`.
- **Qué se conserva (estilo).** IBM Plex Sans/Mono, tamaño base 13px, radios,
  sombras, pesos, paddings, transiciones, rail de navegación (`sidenav`),
  bloque KPI del dashboard, header sin cinta plegada, tiradores de colapso
  sólidos, popups/controles del mapa redondeados, datepicker con color de marca.
- **Qué se elimina (color).** Paleta GPSUbicar (`_main-gpsubicar`), neutros y
  colores de componentes AirPatrol (`_base-airpatrol`, `_base-airpatrol-dark`),
  remapeo de colores de estado (verde/azul/amarillo/rojo → semántica propia),
  fondo navy del login, hover/active propios de `.btn-primary`, y toda regla de
  la capa cuyo único efecto era cambiar un color que el template original ya
  asigna (labels, help-block, bordes de panel/modal/dropdown, cabeceras de
  tabla, etc.).
- **Colores en componentes propios.** Solo variables del template
  (`$brand-primary`, `$color-*`, `$brand-success|danger|warning`), nunca valores
  fijos. Así el mismo partial sirve para claro y oscuro.
- **Tokens CSS (`--ap-*`).** Se eliminan: ningún JS ni Blade los consume. Queda
  `--ap-rail` (ancho del rail), que sí es funcional.

## Estructura de archivos

| Antes | Después |
|---|---|
| `templates/gpsubicar.scss`, `gpsubicar-dark.scss` | `templates/conexsyn.scss`, `conexsyn-dark.scss` |
| `colouring/_main-gpsubicar.scss` | (eliminado; marca = `main-blue`) |
| `colouring/_base-airpatrol.scss`, `_base-airpatrol-dark.scss` | `colouring/_base-conexsyn.scss` (solo forma/tipografía, sin colores) |
| `layout/_airpatrol-tokens.scss` | (eliminado) |
| `layout/_airpatrol.scss` | `layout/_conexsyn.scss` (capa podada de colores) |
| `public/assets/css/gpsubicar*.css` | `public/assets/css/conexsyn*.css` |

Orden de import de un template: fuentes → `main-blue` → `base-ligth|base-dark`
→ `base-conexsyn` → `index` → `layout/conexsyn`.

Reportes (`report.scss`, `report-map.scss`): `main-blue` + `base-ligth` +
`layout/variables` + `base-conexsyn` (misma paleta que la app).

## Renombrado

- `config/tobuli.php`: claves `conexsyn` / `conexsyn-dark`.
- Migración `2026_10_07_000001_rename_gpsubicar_theme_to_conexsyn`: cambia
  `main_settings.template_color` y los overrides en `users.settings`
  (`appearance.template_color`); `down()` revierte.
- `loged.blade.php`: el rail se incluye cuando el template empieza por `conexsyn`.
- `gulpfile.js`: la tarea `sass` compila los dos templates conexsyn.
- `public/assets/custom/js.js`: detecta `link[href*="conexsyn"]`. En producción
  el skin se sirve desde `storage/custom/js.js` (editable en Admin → Custom
  assets): hay que actualizar esa copia también.

## Verificación

- Compilar `conexsyn`, `conexsyn-dark`, `report`, `report-map` con dart-sass +
  clean-css (misma cadena que gulp) sin errores.
- Comprobar en el CSS resultante que `$brand-primary` es `#1b99bd`, que no
  aparecen los hex de GPSUbicar (`#1565ad`, `#0f3d66`, `#f2b72f`, `#12233f`,
  `#101b30`) y que sí aparecen IBM Plex, el rail (`.sidenav`) y el KPI.
- Ejecutar la migración en local y confirmar `template_color = conexsyn`.
