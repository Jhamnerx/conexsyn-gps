# Tema Conexsyn "Self Security" — identidad visual a partir del logo

**Fecha:** 2026-10-12

## Punto de partida

El tema `conexsyn` reutilizaba los colores de light-blue y no se distinguía del
template por defecto. Se rediseña a partir del logo de la marca: escudo con S y
laureles en rojo carmesí puro (`#bc0000`, muestreado del archivo), wordmark
"SELF SECURITY P4" en tipografía cuadrada tecnológica. Esto revierte la decisión
anterior de "respetar los colores del template original".

## Identidad

- **Acento único:** carmesí `#bc0000` (contraste 6,6:1 sobre blanco). Hover
  `#a30000`, activo `#8a0000`. En modo nocturno sube a `#e0262e` y los enlaces a
  `#ff6b6b` para mantener contraste sobre grafito.
- **Grafito:** cabecera y rail de navegación en `#17191e` / `#22252c`, texto
  claro `#f1f2f4`, atenuado `#b4b9c3`. Transmite "centro de control".
- **Superficies claras:** fondo `#eceef1`, superficie `#ffffff`, superficie 2
  `#f5f6f8`, bordes `#dde0e5` / `#e8eaee`, texto `#1b1d22` → `#d2d6dd` en 9
  pasos fríos.
- **Modo nocturno:** fondo `#0f1114`, superficies `#171a1f` / `#1e2228` /
  `#262b32`, texto `#eef0f3` → `#3b424d`, cabecera más oscura que la superficie.
- **Semánticos (no de marca):** éxito `#1f9d55`, info `#2563eb`, aviso
  `#d97706`, peligro `#dc2626`. Los puntos de estado de dispositivos conservan
  los literales verde/azul/amarillo/rojo: el rojo sigue significando alerta.
- **Tipografía:** Titillium Web 400/600/700 (cuerpo 14 px, interlineado 1,45),
  IBM Plex Mono para telemetría (placas, velocidades, horas, KPI).
  Etiquetas de rail, cabeceras de tabla y títulos de panel en mayúsculas 600 con
  tracking 0,04 em, eco del wordmark.
- **Marca recurrente:** franja carmesí de 3 px en la cabecera (pseudo-elemento,
  sin alterar los 42 px de alto), en el borde superior de los modales y del
  panel de login; barra lateral de 3 px en el ítem activo del rail y del
  dispositivo seleccionado.
- **Forma:** radios 4/8/10, dos niveles de sombra, transiciones 120 ms, foco
  visible carmesí.
- **Login:** fondo grafito con un resplandor radial carmesí (overlay
  translúcido, así respeta el color configurado en Apariencia), panel blanco con
  franja superior, botón primario a ancho completo.

## Archivos

| Archivo | Papel |
|---|---|
| `colouring/_main-conexsyn.scss` | marca: carmesí, hover/activo, grafito, semánticos |
| `colouring/_base-conexsyn.scss` | base clara: neutros, cabecera, componentes, tipografía, forma (`!default`) |
| `colouring/_base-conexsyn-dark.scss` | base nocturna (se importa antes y gana) |
| `layout/_conexsyn.scss` | capa de componentes: franjas, rail, sidebar, botones, modales, login, KPI |
| `templates/conexsyn.scss`, `conexsyn-dark.scss` | entradas |
| `templates/report*.scss` | solo cambian de marca (`main-conexsyn`); neutros de impresión intactos |

## Verificación

- Compilan los cuatro CSS; en conexsyn.css aparece `#bc0000` y la cabecera
  es grafito; no queda `#1b99bd`.
- Contrastes AA de los pares principales (texto/superficie, marca/blanco,
  texto atenuado sobre grafito) calculados con un script.
