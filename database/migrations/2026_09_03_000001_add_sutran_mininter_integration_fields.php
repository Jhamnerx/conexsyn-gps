<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Campos y tablas de la retransmisión a SUTRAN / MININTER.
 *
 * Idempotente: producción ya tiene parte del esquema (tablas `sutran`/`mininter`,
 * `devices.mtc`, `devices.mininter`, `users.is_municipalidad|token_muni|ubigeo_muni`),
 * así que cada cambio se aplica solo si falta.
 */
return new class extends Migration
{
    public function up()
    {
        // Dispositivo: activación por integración y tipo de unidad MININTER.
        $this->addColumn('devices', 'mtc', fn (Blueprint $t) => $t->boolean('mtc')->default(0));
        $this->addColumn('devices', 'mininter', fn (Blueprint $t) => $t->boolean('mininter')->default(0));
        $this->addColumn('devices', 'mininter_type', fn (Blueprint $t) => $t->string('mininter_type', 16)->nullable());

        // Usuario propietario: datos de municipalidad / comisaría para MININTER.
        $this->addColumn('users', 'is_municipalidad', fn (Blueprint $t) => $t->boolean('is_municipalidad')->default(0));
        $this->addColumn('users', 'token_muni', fn (Blueprint $t) => $t->string('token_muni')->nullable());
        $this->addColumn('users', 'ubigeo_muni', fn (Blueprint $t) => $t->string('ubigeo_muni')->nullable());
        $this->addColumn('users', 'codigo_comisaria', fn (Blueprint $t) => $t->string('codigo_comisaria', 50)->nullable());

        $this->createSutranTable();
        $this->addColumn('sutran', 'queued_at', fn (Blueprint $t) => $t->dateTime('queued_at')->nullable());
        $this->addIndex('sutran', 'sutran_plate_index', ['plate']);
        $this->addIndex('sutran', 'sutran_queued_at_index', ['queued_at']);

        $this->createMininterTable();
        $this->addColumn('mininter', 'tipo', fn (Blueprint $t) => $t->string('tipo', 16)->nullable());
        $this->addColumn('mininter', 'codigoComisaria', fn (Blueprint $t) => $t->string('codigoComisaria', 50)->nullable());
        $this->addColumn('mininter', 'last_error', fn (Blueprint $t) => $t->text('last_error')->nullable());
        $this->addColumn('mininter', 'retry_count', fn (Blueprint $t) => $t->unsignedTinyInteger('retry_count')->default(0));
        $this->addColumn('mininter', 'failed_at', fn (Blueprint $t) => $t->dateTime('failed_at')->nullable());
        $this->addIndex('mininter', 'mininter_status_index', ['status']);
    }

    public function down()
    {
        // Las tablas `sutran` / `mininter` no se eliminan: pueden preexistir a esta migración.
        $this->dropColumns('mininter', ['tipo', 'codigoComisaria', 'last_error', 'retry_count', 'failed_at']);
        $this->dropColumns('sutran', ['queued_at']);
        $this->dropColumns('users', ['codigo_comisaria']);
        $this->dropColumns('devices', ['mininter_type']);
    }

    private function createSutranTable(): void
    {
        if (Schema::hasTable('sutran')) {
            return;
        }

        Schema::create('sutran', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('device_id')->nullable()->index();
            $table->string('plate')->nullable();
            $table->string('direction')->nullable();
            $table->double('latitud')->nullable();
            $table->double('longitud')->nullable();
            $table->double('speed')->nullable();
            $table->longText('other')->nullable();
            $table->dateTime('time_device')->nullable();
            $table->timestamps();
        });
    }

    private function createMininterTable(): void
    {
        if (Schema::hasTable('mininter')) {
            return;
        }

        Schema::create('mininter', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('device_id')->nullable()->index();
            $table->string('alarma')->default('0');
            $table->double('altitud')->default(0);
            $table->integer('angulo')->default(0);
            $table->double('distancia')->default(0);
            $table->dateTime('fechaHora');
            $table->string('timestamp');
            $table->double('horasMotor')->default(0);
            $table->text('idMunicipalidad');
            $table->boolean('ignition')->default(0);
            $table->text('imei');
            $table->double('latitud');
            $table->double('longitud');
            $table->boolean('motion')->default(0);
            $table->text('placa');
            $table->double('totalDistancia')->default(0);
            $table->double('totalHorasMotor')->default(0);
            $table->text('ubigeo');
            $table->boolean('valid')->default(1);
            $table->double('velocidad')->default(0);
            $table->longText('other')->nullable();
            $table->string('status', 20)->nullable();
            $table->timestamps();
        });
    }

    private function addColumn(string $table, string $column, Closure $definition): void
    {
        if (Schema::hasColumn($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($definition) {
            $definition($t);
        });
    }

    private function dropColumns(string $table, array $columns): void
    {
        $existing = array_values(array_filter($columns, fn ($c) => Schema::hasColumn($table, $c)));

        if (empty($existing)) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($existing) {
            $t->dropColumn($existing);
        });
    }

    private function addIndex(string $table, string $name, array $columns): void
    {
        if ($this->hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($columns, $name) {
            $t->index($columns, $name);
        });
    }

    private function hasIndex(string $table, string $name): bool
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return false;
        }

        return ! empty(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$name]));
    }
};
