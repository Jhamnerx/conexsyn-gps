<?php namespace Tobuli\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tobuli\Entities\Alert;
use Tobuli\Entities\User;

class DefaultAlertsService
{
    /**
     * Prefijo de nombre que marca una alerta del usuario plantilla como
     * "alerta predeterminada" a clonar en cada usuario nuevo.
     */
    const TEMPLATE_PREFIX = '[PLANTILLA]';

    /**
     * Tipos de alerta que se pueden clonar de forma segura.
     *
     * Incluye los tipos "auto-contenidos" (su configuracion vive en la propia
     * alerta) y 'custom' (eventos personalizados): estos ultimos se comparten
     * desde el admin porque su evaluacion solo valida protocolo + condiciones,
     * sin importar el dueno del evento (ver EventCustomAlertCheck::check()).
     *
     * Se excluyen geofence_*, poi_* y driver* porque referencian entidades
     * propias del usuario plantilla (geocercas, POIs, conductores) que el
     * usuario nuevo no posee.
     */
    const CLONEABLE_TYPES = [
        'overspeed',
        'ignition',
        'sos',
        'stop_duration',
        'idle_duration',
        'ignition_duration',
        'offline_duration',
        'move_duration',
        'time_duration',
        'distance',
        'fuel_change',
        'move_start',
        'unplugged',
        'custom',
    ];

    /**
     * Resuelve de que usuario se toman las plantillas:
     * el creador (si es admin/manager) o, en su defecto, el admin principal.
     */
    public function resolveTemplateUser(?User $creator): ?User
    {
        if ($creator && ($creator->isAdmin() || $creator->isManager())) {
            return $creator;
        }

        return User::where('group_id', 1)->orderBy('id')->first();
    }

    /**
     * Punto de entrada para la creacion de un usuario nuevo: copia las alertas
     * plantilla del creador (o admin principal) hacia el usuario recien creado.
     * Defensivo: cualquier fallo se registra pero nunca interrumpe la creacion
     * del usuario.
     */
    public function applyTo(User $newUser, ?User $creator): void
    {
        try {
            $template = $this->resolveTemplateUser($creator);

            if (!$template || $template->id === $newUser->id) {
                return;
            }

            $this->copyTo($newUser, $template);
        } catch (\Throwable $e) {
            Log::error('DefaultAlertsService: no se pudieron copiar las alertas predeterminadas', [
                'user_id' => $newUser->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Aplica las plantillas a TODOS los usuarios existentes (backfill).
     * Idempotente: omite usuarios que ya tengan una alerta con el mismo nombre.
     *
     * @param User|null     $templateUser Plantilla (por defecto, admin principal).
     * @param callable|null $progress     fn(User $user, int $created): void
     * @return array{users:int, alerts:int}
     */
    public function applyToExistingUsers(?User $templateUser = null, ?callable $progress = null): array
    {
        $templateUser = $templateUser ?: User::where('group_id', 1)->orderBy('id')->first();

        $stats = ['users' => 0, 'alerts' => 0];

        if (!$templateUser) {
            return $stats;
        }

        User::where('id', '!=', $templateUser->id)
            ->chunkById(200, function ($users) use ($templateUser, &$stats, $progress) {
                foreach ($users as $user) {
                    $created = $this->copyTo($user, $templateUser);

                    $stats['users']++;
                    $stats['alerts'] += $created;

                    if ($progress) {
                        $progress($user, $created);
                    }
                }
            });

        return $stats;
    }

    /**
     * Clona las alertas plantilla del usuario indicado hacia el usuario destino.
     *
     * - Filtra por prefijo de nombre y por tipos clonables.
     * - Fuerza for_all_user_devices = 1 (cobertura automatica de TODOS los
     *   dispositivos presentes y futuros del usuario destino).
     * - No adjunta dispositivos: UserDevicesAlertsDevicesSync los enganchara
     *   cuando se le asignen vehiculos al usuario.
     * - Para alertas 'custom' comparte los mismos eventos personalizados del
     *   admin (la pivote alert_event_pivot apunta a las mismas filas).
     * - Idempotente: omite plantillas cuyo nombre ya exista en el destino.
     *
     * @return int Numero de alertas creadas.
     */
    public function copyTo(User $newUser, User $templateUser): int
    {
        $alerts = $templateUser->alerts()
            ->whereIn('type', self::CLONEABLE_TYPES)
            ->where('name', 'like', self::TEMPLATE_PREFIX . '%')
            ->with('events_custom')
            ->get();

        if ($alerts->isEmpty()) {
            return 0;
        }

        $existing = $newUser->alerts()->pluck('name')->all();
        $created = 0;

        DB::transaction(function () use ($alerts, $newUser, $existing, &$created) {
            foreach ($alerts as $tpl) {
                $name = $this->cleanName($tpl->name);

                if (in_array($name, $existing, true)) {
                    continue; // ya existe en el usuario, no duplicar
                }

                /** @var Alert $clone */
                $clone = $tpl->replicate(['id', 'user_id']);

                $clone->user_id              = $newUser->id;
                $clone->for_all_user_devices = 1;
                $clone->active               = 1;
                $clone->name                 = $name;

                $clone->save();

                $eventIds = $tpl->events_custom->pluck('id')->all();

                if ($eventIds) {
                    $clone->events_custom()->sync($eventIds);
                }

                $created++;
            }
        });

        return $created;
    }

    /**
     * Quita el prefijo de plantilla del nombre para el usuario final.
     */
    protected function cleanName(string $name): string
    {
        return trim(str_replace(self::TEMPLATE_PREFIX, '', $name));
    }
}
