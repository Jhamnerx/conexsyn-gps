<?php

namespace Tobuli\Reports\Reports;

use Tobuli\History\Actions\Distance;
use Tobuli\History\Actions\Duration;
use Tobuli\History\Actions\GroupOverspeed;
use Tobuli\History\Actions\OverspeedStatic;
use Tobuli\History\Actions\Speed;
use Tobuli\History\Group;
use Tobuli\Reports\DeviceHistoryReport;

class OverspeedsReport extends DeviceHistoryReport
{
    const TYPE_ID = 5;

    protected $disableFields = ['geofences', 'stops'];
    protected $validation = ['speed_limit' => 'required'];

    private int $overspeedCount;
    private ?int $minDuration;

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.overspeeds');
    }

    public function getInputParameters(): array
    {
        return [
            \Field::number('duration', trans('validation.attributes.duration') . ' (' . trans('front.second_short') . ')')
                ->addValidation('integer'),
        ];
    }

    protected function getActionsList()
    {
        return [
            Duration::class,
            Distance::class,
            Speed::class,
            OverspeedStatic::class,


            GroupOverspeed::class,
        ];
    }

    protected function beforeGenerate()
    {
        parent::beforeGenerate();

        $this->minDuration = empty($this->parameters['duration']) ? null : $this->parameters['duration'];
    }

    protected function getTable($data)
    {
        $rows = [];
        $this->overspeedCount = 0;

        foreach ($data['groups']->all() as $group) {
            if (!$this->isValidSpeeding($group)) {
                continue;
            }

            $this->overspeedCount++;

            $rows[] = $this->getDataFromGroup($group, [
                'start_at',
                'end_at',
                'duration',
                'speed_max',
                'speed_avg',
                'location',
            ]);
        }

        return [
            'rows'   => $rows,
            'totals' => [],
        ];
    }

    protected function getTotals(Group $group, array $only = [])
    {
        $totals = parent::getTotals($group, ['overspeed_count']);

        $totals['overspeed_count']['value'] = $this->overspeedCount;

        return $totals;
    }

    protected function isEmptyResult($data): bool
    {
        if (
            empty($data) ||
            empty($data['root']->getStartPosition()) ||
            !$data['root']->hasStat('overspeed_count') ||
            empty($data['root']->getStat('overspeed_count')->value())
        ) {
            return true;
        }

        foreach ($data['groups']->all() as $group) {
            if ($this->isValidSpeeding($group)) {
                return false;
            }
        }

        return true;
    }

    private function isValidSpeeding(Group $group): bool
    {
        return !$this->minDuration || $group->getStat('duration')->value() >= $this->minDuration;
    }
}