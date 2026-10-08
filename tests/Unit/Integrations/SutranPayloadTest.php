<?php

use App\Services\Integrations\Sutran\SutranPayload;
use Tobuli\Entities\Device;
use Tobuli\Entities\Sutran;

class SutranPayloadTest extends TestCase
{
    /** @test */
    public function builds_the_trama_expected_by_sutran()
    {
        $row = new Sutran([
            'plate'       => ' abc-123 ',
            'latitud'     => -12.0464,
            'longitud'    => -77.0428,
            'direction'   => '90',
            'speed'       => 42.7,
            'time_device' => '2026-09-03 15:00:00',
        ]);
        $row->id = 55;
        $row->setRelation('device', new Device(['imei' => '359632101234567']));

        $trama = SutranPayload::fromRow($row);

        $expectedTime = (new DateTime('2026-09-03 15:00:00'))
            ->setTimezone(new DateTimeZone('America/Lima'))
            ->format('Y-m-d H:i:s');

        $this->assertSame(55, $trama['id']);
        $this->assertSame('ABC123', $trama['plate']);
        $this->assertSame([-12.0464, -77.0428], $trama['geo']);
        $this->assertSame(90, $trama['direction']);
        $this->assertSame('ER', $trama['event']);
        $this->assertSame(42, $trama['speed']);
        $this->assertSame($expectedTime, $trama['time_device']);
        $this->assertSame(359632101234567, $trama['imei']);
    }

    /** @test */
    public function reports_stopped_units_with_event_pa()
    {
        $row = new Sutran(['plate' => 'XYZ987', 'speed' => 0, 'time_device' => '2026-09-03 15:00:00']);
        $row->setRelation('device', new Device(['imei' => '1']));

        $this->assertSame('PA', SutranPayload::fromRow($row)['event']);

        $row->speed = 5;
        $this->assertSame('PA', SutranPayload::fromRow($row)['event']);

        $row->speed = 5.1;
        $this->assertSame('ER', SutranPayload::fromRow($row)['event']);
    }

    /** @test */
    public function validates_direction_range()
    {
        $this->assertTrue(SutranPayload::isValidDirection(0));
        $this->assertTrue(SutranPayload::isValidDirection('360'));
        $this->assertFalse(SutranPayload::isValidDirection(361));
        $this->assertFalse(SutranPayload::isValidDirection(-1));
        $this->assertFalse(SutranPayload::isValidDirection(null));
        $this->assertFalse(SutranPayload::isValidDirection('abc'));
    }

    /** @test */
    public function normalizes_plates()
    {
        $this->assertSame('ABC123', SutranPayload::normalizePlate(' abc-123 '));
        $this->assertSame('A1B234', SutranPayload::normalizePlate('A1B 234'));
        $this->assertSame('', SutranPayload::normalizePlate(null));
        $this->assertSame('', SutranPayload::normalizePlate(' - '));
    }
}
