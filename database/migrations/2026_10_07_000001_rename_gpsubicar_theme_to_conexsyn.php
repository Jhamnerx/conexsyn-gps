<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * El tema gpsubicar pasa a llamarse conexsyn (claro y nocturno).
     * Renombra el template global (main_settings.template_color) y los
     * overrides personales de cada usuario; respeta a quien tenga otro tema.
     */
    private const RENAMES = [
        'gpsubicar'      => 'conexsyn',
        'gpsubicar-dark' => 'conexsyn-dark',
    ];

    /**
     * @return void
     */
    public function up()
    {
        $this->renameTemplates(self::RENAMES);
    }

    /**
     * @return void
     */
    public function down()
    {
        $this->renameTemplates(array_flip(self::RENAMES));
    }

    private function renameTemplates(array $map)
    {
        $current = settings('main_settings.template_color');

        if (isset($map[$current])) {
            settings('main_settings.template_color', $map[$current]);
        }

        $users = DB::table('users')
            ->where('settings', 'like', '%template_color%')
            ->get(['id', 'settings']);

        foreach ($users as $user) {
            $settings = json_decode($user->settings, true);
            $template = $settings['appearance']['template_color'] ?? null;

            if (!isset($map[$template])) {
                continue;
            }

            $settings['appearance']['template_color'] = $map[$template];

            DB::table('users')
                ->where('id', $user->id)
                ->update(['settings' => json_encode($settings)]);
        }
    }
};
