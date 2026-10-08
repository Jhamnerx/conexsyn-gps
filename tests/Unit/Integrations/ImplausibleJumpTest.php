<?php

use App\Services\Integrations\IntegrationPositionRecorder;
use Tobuli\Entities\TraccarPosition;

class ImplausibleJumpTest extends TestCase
{
    private function position(float $speed, string $deviceTime): TraccarPosition
    {
        return (new TraccarPosition())->forceFill(['speed' => $speed, 'device_time' => $deviceTime]);
    }

    /** @test */
    public function stopped_to_fast_is_implausible()
    {
        $recorder = new IntegrationPositionRecorder();

        $this->assertTrue($recorder->isImplausibleJump(
            $this->position(60, '2026-09-03 10:10:00'),
            $this->position(0, '2026-09-03 10:00:00')
        ));
    }

    /** @test */
    public function big_speed_change_in_few_seconds_is_implausible()
    {
        $recorder = new IntegrationPositionRecorder();

        $this->assertTrue($recorder->isImplausibleJump(
            $this->position(20, '2026-09-03 10:00:04'),
            $this->position(55, '2026-09-03 10:00:00')
        ));
    }

    /** @test */
    public function gradual_changes_are_plausible()
    {
        $recorder = new IntegrationPositionRecorder();

        $this->assertFalse($recorder->isImplausibleJump(
            $this->position(45, '2026-09-03 10:00:30'),
            $this->position(20, '2026-09-03 10:00:00')
        ));

        $this->assertFalse($recorder->isImplausibleJump(
            $this->position(60, '2026-09-03 10:00:02'),
            $this->position(40, '2026-09-03 10:00:00')
        ));
    }
}
