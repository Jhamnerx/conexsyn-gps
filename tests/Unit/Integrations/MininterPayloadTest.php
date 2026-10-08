<?php

use App\Services\Integrations\Mininter\MininterPayload;
use Tobuli\Entities\Mininter;

class MininterPayloadTest extends TestCase
{
    private function row(array $overrides = []): Mininter
    {
        $row = new Mininter(array_merge([
            'tipo'            => Mininter::TYPE_SERENAZGO,
            'alarma'          => '0',
            'altitud'         => 154.2,
            'angulo'          => 270,
            'distancia'       => 0.35,
            'timestamp'       => '1788793200', // 2026-09-07 15:00:00 UTC = 10:00:00 America/Lima
            'horasMotor'      => 12.5,
            'idMunicipalidad' => 'MUNI-01',
            'codigoComisaria' => null,
            'ignition'        => 1,
            'imei'            => '359632101234567',
            'latitud'         => -12.0464,
            'longitud'        => -77.0428,
            'motion'          => 0,
            'placa'           => 'ABC123',
            'totalDistancia'  => 1500.7,
            'totalHorasMotor' => 300.2,
            'ubigeo'          => '150101',
            'valid'           => 1,
            'velocidad'       => 37.0,
        ], $overrides));

        $row->id = 77;

        return $row;
    }

    /** @test */
    public function serenazgo_trama_uses_municipality_identifiers()
    {
        $trama = MininterPayload::fromRow($this->row());

        $this->assertSame(77, $trama['id']);
        $this->assertSame('MUNI-01', $trama['idMunicipalidad']);
        $this->assertSame('77ABC123', $trama['idTransmision']);
        $this->assertArrayNotHasKey('codigoComisaria', $trama);

        $this->assertSame('07/09/2026 10:00:00', $trama['fechaHora']);
        $this->assertSame('150101', $trama['ubigeo']);
        $this->assertSame(1, $trama['ignition']);
        $this->assertSame(0, $trama['motion']);
        $this->assertTrue($trama['valid']);
        $this->assertSame(37.0, $trama['velocidad']);
        $this->assertSame(270, $trama['angulo']);
        $this->assertSame('359632101234567', $trama['imei']);
    }

    /** @test */
    public function policial_trama_uses_police_station_identifiers()
    {
        $trama = MininterPayload::fromRow($this->row([
            'tipo'            => Mininter::TYPE_POLICIAL,
            'codigoComisaria' => 'COM-777',
            'idMunicipalidad' => 'TX-99',
        ]));

        $this->assertSame('COM-777', $trama['codigoComisaria']);
        $this->assertSame('TX-99', $trama['idTransmision']);
        $this->assertArrayNotHasKey('idMunicipalidad', $trama);
    }
}
