<?php

namespace Tobuli\History\Actions;


use Tobuli\History\Stats\Distance AS DistanceStat;
use Tobuli\History\Stats\Duration AS DurationStat;
use Tobuli\History\Stats\StatCount;

class DriveStop extends ActionStat
{
    /**
     * @var null|bool
     */
    protected $state;

    static public function required()
    {
        return [
            Duration::class,
            AppendDistance::class,
            AppendMoveState::class,
        ];
    }

    public function boot()
    {
        $this->registerStat('drive_distance', new DistanceStat());
        $this->registerStat('drive_duration', new DurationStat());
        $this->registerStat('drive_count', new StatCount());
        $this->registerStat('stop_duration', new DurationStat());
        $this->registerStat('stop_count', new StatCount());
    }

    public function proccess($position)
    {
        if ($this->isChanged($position)) {
            switch ($position->moving) {
                case AppendMoveState::MOVING:
                    $this->history->applyStat("drive_count", 1);
                    break;
                case AppendMoveState::STOPED:
                    $this->history->applyStat("stop_count", 1);
                    break;
            }
        }

        $isMoving = $this->isStateCalcable($position, 'moving');

        switch ($isMoving) {
            case AppendMoveState::MOVING:
                $this->history->applyStat("drive_duration", $position->duration);
                $this->history->applyStat("drive_distance", $position->distance);

                break;
            case AppendMoveState::STOPED:
                $this->history->applyStat("stop_duration", $position->duration);
                break;
        }

        $this->state = $position->moving;
    }

    protected function isChanged($position)
    {
        return is_null($this->state) || $this->isStateChanged($position, 'moving');
    }

    protected function isResumeAfterOffline($position)
    {
        return 300 < $position->duration;
    }
}