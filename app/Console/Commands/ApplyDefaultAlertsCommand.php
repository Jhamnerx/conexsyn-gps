<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Tobuli\Entities\User;
use Tobuli\Services\DefaultAlertsService;

class ApplyDefaultAlertsCommand extends Command
{
    protected $signature = 'alerts:apply-defaults
        {--user= : ID de un usuario especifico (por defecto, todos los usuarios)}
        {--template= : ID del usuario plantilla (por defecto, el admin principal group_id=1)}';

    protected $description = 'Copia las alertas plantilla ([PLANTILLA] ...) del admin hacia los usuarios existentes.';

    public function handle(DefaultAlertsService $service): int
    {
        $template = null;

        if ($templateId = $this->option('template')) {
            $template = User::find($templateId);

            if (!$template) {
                $this->error("Usuario plantilla {$templateId} no encontrado.");
                return 1;
            }
        }

        // Un solo usuario
        if ($userId = $this->option('user')) {
            $user = User::find($userId);

            if (!$user) {
                $this->error("Usuario {$userId} no encontrado.");
                return 1;
            }

            $tpl = $template ?: $service->resolveTemplateUser(null);

            if (!$tpl) {
                $this->error('No hay usuario plantilla disponible (admin principal).');
                return 1;
            }

            $created = $service->copyTo($user, $tpl);
            $this->info("Usuario {$user->email}: {$created} alertas creadas.");

            return 0;
        }

        // Todos los usuarios
        $this->info('Aplicando alertas predeterminadas a usuarios existentes...');

        $stats = $service->applyToExistingUsers($template, function (User $user, int $created) {
            if ($created > 0) {
                $this->line("  {$user->email}: +{$created}");
            }
        });

        $this->info("Listo. Usuarios procesados: {$stats['users']}, alertas creadas: {$stats['alerts']}.");

        return 0;
    }
}
