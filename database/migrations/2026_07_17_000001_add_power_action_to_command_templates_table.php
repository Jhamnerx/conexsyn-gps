<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPowerActionToCommandTemplatesTable extends Migration
{
    /**
     * power_action marca una plantilla de comando como accion de energia:
     *   null      => plantilla normal
     *   'cut'     => corte de corriente (se envia al activar el parqueo seguro)
     *   'restore' => restablecer corriente (se envia al desactivar el parqueo seguro)
     */
    public function up()
    {
        Schema::table('command_templates', function (Blueprint $table) {
            $table->string('power_action', 20)->nullable()->after('adapted');
        });
    }

    public function down()
    {
        Schema::table('command_templates', function (Blueprint $table) {
            $table->dropColumn('power_action');
        });
    }
}
