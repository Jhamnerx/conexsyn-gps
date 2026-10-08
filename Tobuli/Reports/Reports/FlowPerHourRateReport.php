<?php

namespace Tobuli\Reports\Reports;

use Formatter;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Tobuli\Entities\Device;
use Tobuli\Entities\TraccarPosition;
use Tobuli\Entities\User;
use Tobuli\Reports\DeviceReport;
use Tobuli\Reports\ReportManager;

class FlowPerHourRateReport extends DeviceReport
{
    public const TYPE_ID = 84;

    private array $intervals = [];

    public function typeID(): int
    {
        return self::TYPE_ID;
    }

    public function title(): string
    {
        return trans('front.flow_per_hour_rate');
    }

    protected function defaultMetas(): array
    {
        $metas = parent::defaultMetas();
        $metas['device.group_id'] = trans('validation.attributes.group_id');

        return $metas;
    }

    protected function beforeGenerate(): void
    {
        $period = new \DatePeriod(
            new \DateTime(date('Y-m-d H:i:s', strtotime(Formatter::time()->human($this->date_from)))),
            new \DateInterval('PT1H'),
            new \DateTime(date('Y-m-d H:i:s', strtotime(Formatter::time()->human($this->date_to)))),
        );

        $dateTo = $period->end;

        if ($dateTo->format('i') !== '00') {
            $period = iterator_to_array($period);
            $period[] = $dateTo;
        }

        foreach ($period as $value) {
            $datetime = $value->format('Y-m-d H:i:s');

            $date = date('Y-m-d', strtotime($datetime));
            $hour = date('H', strtotime($datetime));

            $this->intervals[$date][$hour] = [
                'net_amount'    => null,
                'flow_rate'     => null,
                'speed'         => null,
                'location'      => null,
            ];
        }
    }

    protected function afterGenerate()
    {
        usort($this->items, fn ($a, $b) => strcmp(
            $a['meta']['device.group_id']['value'] ?? '',
            $b['meta']['device.group_id']['value'] ?? '',
        ));
    }

    protected function generateDevice(Device $device): array
    {
        $rows = $this->intervals;

        try {
            $positions = $device->positions()
                ->whereBetween('time', [$this->date_from, $this->date_to])
                ->cursor();

            foreach ($positions as $position) {
                $flow = $this->getFlowData($position);

                if (empty($flow)) {
                    continue;
                }

                $date = date('Y-m-d', strtotime(Formatter::time()->human($position->time)));
                $hour = date('H', strtotime(Formatter::time()->human($position->time)));

                foreach ($flow as $key => $value) {
                    if ($rows[$date][$hour][$key] > $value) {
                        continue;
                    }

                    $rows[$date][$hour][$key] = $value;

                    if ($rows[$date][$hour]['location'] === null) {
                        $rows[$date][$hour]['location'] = $this->getLocation($position);
                    }
                }
            }
        } catch (QueryException $e) {}

        return [
            'meta'       => $this->getDeviceMeta($device),
            'table'      => $rows,
        ];
    }

    private function getFlowData(TraccarPosition $position): ?array
    {
        $params = $position->getParametersAttribute();

        $result = [
            'net_amount' => $params['nettotal'] ?? null,
            'flow_rate' => $params['flowh'] ?? null,
            'speed' => $params['velocity'] ?? null,
        ];

        $result = array_filter($result, fn ($value) => $value !== null);
        $result = array_map(fn ($value) => round(floatval($value), 2), $result);

        return $result;
    }

    public static function isAvailable(): bool
    {
        return config('addon.report_fuel_tank_usage');
    }

    public static function isUserEnabled(User $user): bool
    {
        $metas = ReportManager::getMetaList($user);

        return isset($metas['device.group_id']);
    }
}