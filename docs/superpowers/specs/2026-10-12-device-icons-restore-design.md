# Comando `device-icons:restore` — recuperar iconos de dispositivos

**Fecha:** 2026-10-12

## Problema

La tabla `device_icons` referencia 1035 iconos, pero en disco solo existen los
55 que están en git (`public/images/device_icons/v2` y `rotating`). Los otros
980 son subidas con nombre `uniqid` (en la raíz de `images/device_icons/` y
dentro de `v2/` y `rotating/`) que nunca se versionaron; 652 dispositivos los
usan y en la interfaz aparecen rotos. Todos esos archivos existen en la
instalación hermana `talentus-pro-tracking` (misma subcarpeta).

El seeder `DeviceIconsTableSeeder` no copia archivos: solo registra en la tabla
los que encuentra en `v2` y `rotating`, y usa el nombre de base `gpswox_web`
fijo en SQL crudo, por lo que falla en esta instalación (`gpswox_gpsubicar`).

## Diseño

`Tobuli\Services\DeviceIconRestoreService` (ruta pública inyectable para poder
probarlo sin Laravel):

- `missingRows()`: filas `id > 0` cuyo archivo no existe bajo `public/`.
- `restore($sourceDir, $rows, $dryRun)`: indexa el origen de forma recursiva;
  para cada fila busca primero la misma ruta relativa y, si no, el mismo nombre
  de archivo; copia a `public/<path>` creando carpetas. No sobrescribe. Devuelve
  copiados y no encontrados.
- `scanRegistrable($existingPaths)`: archivos de imagen de `v2` (tipo `icon`) y
  `rotating` (tipo `rotating`) con dimensiones válidas y sin fila. Puro.
- `register($dryRun)`: crea las filas de `scanRegistrable` con `order = 3`
  (como el seeder; `order` no es asignable en masa, se fija explícitamente).
- `ensureArrowIcon($dryRun)`: garantiza la fila `id 0` de
  `assets/images/arrow-ack.png` (tipo `arrow`) con el query builder, sin nombre
  de base fijo; elimina duplicados con otro id.
- `orphanFiles()`: archivos de la raíz de `images/device_icons` sin fila,
  ignorando los compañeros `_offline`, `_ack`, `_engine` de iconos por estado.
- `devicesUsing($rows)`: dispositivos cuyo `icon_id` apunta a esas filas.

`App\Console\Commands\DeviceIconsRestoreCommand`:

```
device-icons:restore {--from=} {--dry-run}
```

1. Informe inicial: filas, archivos faltantes, dispositivos afectados.
2. Con `--from`: restaura y lista lo no encontrado.
3. Registra archivos sin fila y asegura la flecha.
4. Informe final y aviso de archivos huérfanos en la raíz.

## Verificación

- Test unitario de `restore()` y `scanRegistrable()` con carpetas temporales.
- `php artisan device-icons:restore --dry-run` y luego con
  `--from=C:/laragon2/www/talentus-pro-tracking/public/images/device_icons`:
  los 980 faltantes deben quedar en 0.
