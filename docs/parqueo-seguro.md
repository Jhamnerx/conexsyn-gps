# Parqueo Seguro (Safe Parking)

Sistema para "blindar" un vehículo estacionado desde la app móvil. Al activarlo se crea
una geocerca circular alrededor del vehículo, una alerta de salida de esa geocerca y
—si el dispositivo lo soporta— se envía el comando de **corte de corriente**. Al
desactivarlo se elimina la geocerca y la alerta, y se envía el comando de
**restablecer corriente**.

---

## 1. Cambios de base de datos

Ejecutar las migraciones:

```bash
php artisan migrate
```

### Tabla `devices` (`..._add_safe_parking_to_devices_table.php`)

| Columna | Tipo | Descripción |
|---|---|---|
| `safe_parking` | `boolean` (default `0`) | Estado del parqueo seguro (1 = activo). |
| `safe_parking_geofence_id` | `int unsigned` nullable | Id de la geocerca creada al activar. |
| `safe_parking_alert_id` | `int unsigned` nullable | Id de la alerta creada al activar. |

### Tabla `command_templates` (`..._add_power_action_to_command_templates_table.php`)

| Columna | Tipo | Descripción |
|---|---|---|
| `power_action` | `string(20)` nullable | `null` = plantilla normal · `cut` = corte de corriente · `restore` = restablecer corriente. |

---

## 2. Configuración de comandos por protocolo (Admin)

Para que el parqueo seguro pueda cortar/restablecer la corriente, hay que registrar
las plantillas de comando GPRS en el panel de administración:

`Admin → Command templates → Crear`

Por **cada protocolo** que uses (ej. `gt06`, `teltonika`, `coban`, `pt502`...), crea
**dos** plantillas:

1. **Corte de corriente**
   - `type`: GPRS
   - `adapted`: `Protocol` → selecciona el protocolo
   - `power_action` (**Acción de energía**): `Corte de corriente (parqueo)`
   - `message`: el comando real del equipo (ej. `Relay,1#`, `DYD,000000#`, etc.)

2. **Restablecer corriente**
   - Igual que la anterior pero
   - `power_action`: `Restablecer corriente`
   - `message`: el comando de restablecer (ej. `Relay,0#`, `HFYD,000000#`, etc.)

También se puede adaptar por `Devices` o `Device types` en lugar de por protocolo.
La selección de la plantilla al activar/desactivar usa la misma lógica de
adaptación que el resto de comandos (`isAdaptedFromDevice`): se prefiere la
plantilla más específica y las del propio usuario antes que las comunes.

> Si un modelo/protocolo **no** tiene plantilla `cut`/`restore`, el parqueo seguro
> igual crea la geocerca + alerta; simplemente no envía comando
> (`command.reason = "no_template"`).

---

## 3. API para la app móvil

Autenticación: por `user_api_hash` (igual que el resto de la API móvil), como
parámetro `?user_api_hash=XXXX` o header `user-api-hash: XXXX`.
Base URL: `https://TU_DOMINIO/api`.

### 3.1 Consultar estado

```
GET /api/safe_parking/{device_id}?user_api_hash=XXXX
```

Respuesta:

```json
{
  "status": 1,
  "active": false,
  "geofence_id": null,
  "alert_id": null
}
```

### 3.2 Activar / Desactivar

```
POST /api/safe_parking
Content-Type: application/x-www-form-urlencoded  (o query string)

device_id=123            (requerido)
status=1                 (opcional: 1 activar, 0 desactivar; si se omite, alterna)
radius=100               (opcional, metros; solo aplica al activar; default 100)
user_api_hash=XXXX
```

**Activar** — `status=1` (o toggle desde estado inactivo):

```json
{
  "status": 1,
  "active": true,
  "message": "Parqueo seguro activado",
  "geofence_id": 45,
  "alert_id": 78,
  "radius": 100,
  "command": {
    "sent": true,
    "template_id": 12,
    "status": 1,
    "message": null
  }
}
```

**Desactivar** — `status=0` (o toggle desde estado activo):

```json
{
  "status": 1,
  "active": false,
  "message": "Parqueo seguro desactivado",
  "command": {
    "sent": true,
    "template_id": 13,
    "status": 1,
    "message": null
  }
}
```

### 3.3 Objeto `command`

| Campo | Descripción |
|---|---|
| `sent` | `true` si el comando se envió correctamente al equipo. |
| `reason` | Presente sólo si no se envió: `"no_template"` = no hay plantilla `cut`/`restore` para ese device. |
| `template_id` | Id de la plantilla usada. |
| `status` | Estado devuelto por el core de comandos (1 ok, 0 error). |
| `message` | Texto de error si lo hubo (ej. sin conexión GPRS). |

> El comando sólo se envía si el equipo está **conectado** (online). Si no lo está,
> `command.sent = false` y `command.message` explicará el motivo
> (`No hay conexión GPRS`), pero la geocerca y la alerta se crean igual.

### 3.4 Errores comunes

| Caso | Respuesta |
|---|---|
| Sin ubicación conocida al activar | `{ "status": 0, "active": false, "error": "El vehiculo no tiene una ubicacion conocida para proteger" }` |
| Device no pertenece al usuario / no existe | Excepción de permiso estándar (`{ "status": 0, ... }`). |
| Ya activo al activar | `{ "status": 1, "active": true, "message": "El parqueo seguro ya esta activo" }` |
| Ya inactivo al desactivar | `{ "status": 1, "active": false, "message": "El parqueo seguro no esta activo" }` |

---

## 4. Flujo interno (resumen técnico)

**Activar** (`Tobuli\Services\SafeParkingService::activate`):

1. Lee `device.latitude` / `device.longitude` (última posición válida).
2. Crea geocerca **circular** (`type = circle`, `center`, `radius` en metros).
3. Crea alerta `geofence_out` asociada al device + geocerca, con notificación push.
4. Guarda `safe_parking = 1` y los ids en el device.
5. Busca la plantilla `power_action = cut` adaptada al device y, si el equipo está
   conectado, envía el comando (`template_{id}` vía `SendCommandService::gprs`).

**Desactivar** (`::deactivate`):

1. Envía la plantilla `power_action = restore` (si existe y el equipo está conectado).
2. Elimina la geocerca (`safe_parking_geofence_id`) y la alerta
   (`safe_parking_alert_id`, con detach de pivotes).
3. Limpia `safe_parking` y los ids en el device.

Cuando el vehículo se mueve fuera del círculo, el motor de alertas dispara la alerta
`geofence_out` (evento `zone_out`) y envía la notificación push a la app.

---

## 5. Archivos involucrados

| Archivo | Rol |
|---|---|
| `database/migrations/*_add_safe_parking_to_devices_table.php` | Columnas en `devices`. |
| `database/migrations/*_add_power_action_to_command_templates_table.php` | Columna `power_action`. |
| `Tobuli/Entities/Device.php` | `$fillable` + cast `safe_parking`. |
| `Tobuli/Entities/CommandTemplate.php` | `power_action`, constantes, `getPowerActions()`, scope `powerAction`. |
| `Tobuli/Views/Admin/CommandTemplates/form.blade.php` | Selector "Acción de energía". |
| `app/Http/Controllers/Admin/CommandTemplatesController.php` | Pasa `powerActions` al formulario. |
| `Tobuli/Services/SafeParkingService.php` | Lógica de activar/desactivar. |
| `app/Http/Controllers/Frontend/SafeParkingController.php` | Endpoints API. |
| `routes/api.php` | Rutas `safe_parking`. |
| `resources/lang/{en,es}/front.php` | Textos. |
