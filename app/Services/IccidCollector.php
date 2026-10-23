<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class IccidCollector
{
    const TABLE = 'gt06_iccids';

    /**
     * Verifica si ya existe una entrada para el IMEI dado.
     */
    public function has(string $imei): bool
    {
        return DB::table(self::TABLE)->where('imei', $imei)->exists();
    }

    /**
     * Inserta o actualiza un registro name|imei|iccid garantizando que:
     *  - Cada IMEI tenga una sola fila (columna única).
     *  - Cada ICCID quede asociado a un solo IMEI.
     *
     * Si llega un ICCID que ya existía en otra fila con distinto IMEI, esa fila
     * quedó obsoleta (la SIM se movió a este equipo) y se elimina para no dejar
     * el mismo ICCID en dos IMEI distintos.
     *
     * Retorna true si se insertó/actualizó, false si no hubo cambios.
     */
    public function add(string $name, string $imei, string $iccid): bool
    {
        if (empty($imei) || empty($iccid)) {
            return false;
        }

        // Elimina filas obsoletas con el mismo ICCID pero distinto IMEI.
        DB::table(self::TABLE)
            ->where('iccid', $iccid)
            ->where('imei', '<>', $imei)
            ->delete();

        $existing = DB::table(self::TABLE)->where('imei', $imei)->first();

        if ($existing) {
            // Refresca solo si cambió el ICCID o el nombre.
            if ($existing->iccid !== $iccid || $existing->name !== $name) {
                DB::table(self::TABLE)
                    ->where('id', $existing->id)
                    ->update([
                        'name'       => $name,
                        'iccid'      => $iccid,
                        'created_at' => now(),
                    ]);

                return true;
            }

            return false;
        }

        DB::table(self::TABLE)->insert([
            'name'       => $name,
            'imei'       => $imei,
            'iccid'      => $iccid,
            'created_at' => now(),
        ]);

        return true;
    }

    /**
     * Retorna todas las entradas como array [imei => ['name' => ..., 'iccid' => ...]].
     */
    public function all(): array
    {
        return DB::table(self::TABLE)
            ->get()
            ->keyBy('imei')
            ->map(fn($row) => ['name' => $row->name, 'iccid' => $row->iccid])
            ->all();
    }
}
