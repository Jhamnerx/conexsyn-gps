<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Activa el tema GPSUbicar:
     * - setea el template global (main_settings.template_color)
     * - migra los usuarios cuyo override personal sea 'light-blue'
     *   (el default antiguo); respeta a quien eligió otro tema.
     *
     * @return void
     */
    public function up()
    {
        settings('main_settings.template_color', 'gpsubicar');

        $this->migrateUserOverrides('light-blue', 'gpsubicar');
    }

    /**
     * Revierte al tema por defecto anterior.
     *
     * @return void
     */
    public function down()
    {
        settings('main_settings.template_color', 'light-blue');

        $this->migrateUserOverrides('gpsubicar', 'light-blue');
    }

    private function migrateUserOverrides(string $from, string $to)
    {
        $users = DB::table('users')
            ->where('settings', 'like', '%template_color%')
            ->get(['id', 'settings']);

        foreach ($users as $user) {
            $settings = json_decode($user->settings, true);

            if (($settings['appearance']['template_color'] ?? null) === $from) {
                $settings['appearance']['template_color'] = $to;

                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['settings' => json_encode($settings)]);
            }
        }
    }
};
