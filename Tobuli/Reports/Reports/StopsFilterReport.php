<?php

namespace Tobuli\Reports\Reports;

use Tobuli\History\Actions\Drivers;
use Tobuli\History\Actions\DriveStop;
use Tobuli\History\Actions\Duration;
use Tobuli\History\Actions\GroupStop;
use Tobuli\History\Group;
use Tobuli\Reports\DeviceHistoryReport;

class StopsFilterReport extends DeviceHistoryReport
{
    const TYPE_ID = 95;

    protected $disableFields = ['geofences', 'speed_limit'];

    private ?int $minDuration;

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.stops') . ' (' . trans('validation.attributes.filter') . ')';
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
            DriveStop::class,
            Duration::class,

            GroupStop::class,
        ];
    }

    protected function beforeGenerate()
    {
        parent::beforeGenerate();

        $this->minDuration = empty($this->parameters['duration']) ? null : $this->parameters['duration'];
    }

    protected function generateDevice($device)
    {
        if ($error = $this->precheckError($device))
            return [
                'meta' => $this->getDeviceMeta($device),
                'error' => $error
            ];

        $data = $this->getDeviceHistoryData($device);

        if ($this->isEmptyResult($data))
            return null;

        $table = $this->getTable($data);

        if (empty($table)) {
            return null;
        }

        return [
            'meta' => $this->getDeviceMeta($device) + $this->getHistoryMeta($data['root']),
            'table'  => $table,
            'totals' => $this->getTotals($data['root'])
        ];
    }

    protected function getTable($data)
    {
        $rows = [];

        /** @var Group $group */
        foreach ($data['groups']->all() as $group) {
            if (!$this->isValidSpeeding($group)) {
                continue;
            }

            $row = $this->getDataFromGroup($group, [
                'start_at',
                'end_at',
                'duration',
                'drivers',
                'location',
            ]);

            $position = $group->getStartPosition();

            $row['coordinates'] = $this->getLocation($position, $position->latitude . ',' . $position->longitude);

            $rows[] = $row;
        }

        return $rows;
    }

    private function isValidSpeeding(Group $group): bool
    {
        return !$this->minDuration || $group->getStat('duration')->value() >= $this->minDuration;
    }
}