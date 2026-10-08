# Retransmisión de posiciones a SUTRAN y MININTER

Fecha: 2026-09-03

## Objetivo

Completar la retransmisión de posiciones GPS a los servicios web de SUTRAN (MTC) y
MININTER (serenazgo y policial) de forma activable por dispositivo, con datos de
municipalidad/comisaría configurables por usuario, logs en archivo por integración y
limpieza automática de logs y colas.

## Modelo de datos

Migración idempotente (`Schema::hasColumn` / `hasTable`), porque producción ya tiene
parte del esquema.

- `devices`: `mtc` (bool), `mininter` (bool), `mininter_type` (`serenazgo|policial`).
- `users`: `is_municipalidad` (bool), `ubigeo_muni` (6 dígitos), `token_muni`
  (id municipalidad / id transmisión), `codigo_comisaria` (solo unidades policiales).
- `sutran`: cola de posiciones pendientes. Se añade `queued_at` para evitar envíos
  duplicados entre corridas del dispatcher.
- `mininter`: cola de posiciones pendientes. Se añaden `tipo`, `codigoComisaria`,
  `last_error`, `retry_count`, `failed_at`.

Estados de `mininter.status`: `pending` -> `queued` -> (borrado al éxito | `error`
con reintento | `failed` al agotar reintentos).

## Flujo

1. `PositionsWriter` delega en `IntegrationPositionRecorder`, que encola la posición en
   `sutran` y/o `mininter` según los flags del dispositivo. Cualquier excepción se
   registra y nunca interrumpe la escritura de posiciones.
2. `DispatchSutranJobs` / `DispatchMininterJobs` (programados cada minuto, únicos)
   toman filas pendientes por rangos de id y despachan lotes a las colas
   `web-services-sutran` / `web-services-mininter`.
3. `SendDataSutranBatch` / `SendDataMininter` construyen el payload
   (`SutranPayload` / `MininterPayload`), envían por HTTP y borran las filas aceptadas.
   SUTRAN: las rechazadas fila a fila (`error_plates` "F:<índice>") se borran y se
   registran porque son validaciones deterministas; los errores de transporte liberan
   `queued_at` para reintentar. MININTER: cada rechazo incrementa `retry_count` hasta
   `max_retries`, luego la fila pasa a `failed`.
4. `integrations:clean` (diario) borra logs antiguos, filas `failed` y filas más viejas
   que la ventana que aceptan los servicios.

## Configuración

`config/integrations.php` centraliza URLs, token, tamaños de lote, reintentos y
retención, todo sobreescribible por `.env`.

## Logs

Canales `sutran` y `mininter` en `config/logging.php` (driver `daily`, carpeta
`storage/logs/integrations`). `IntegrationLogger` añade contexto uniforme y trunca
cuerpos largos. Se elimina el log a base de datos (`LogService`/`LogEntry`) porque la
tabla `logs` tiene otro esquema y fallaba.

## UI

- Dispositivo (crear/editar): pestaña "Integraciones" con SUTRAN, MININTER y tipo de
  unidad. Visible/editable para admin y managers.
- Usuario (admin, crear/editar): pestaña "Integraciones" con el flag MININTER, ubigeo,
  id municipalidad y código de comisaría. Solo admin.
