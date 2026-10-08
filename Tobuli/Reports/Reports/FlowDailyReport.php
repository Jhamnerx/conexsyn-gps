<?php

namespace Tobuli\Reports\Reports;

use Carbon\Carbon;
use Formatter;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Tobuli\Entities\Device;
use Tobuli\Entities\TraccarPosition;
use Tobuli\Entities\User;
use Tobuli\Reports\DeviceReport;
use Tobuli\Reports\ReportManager;

class FlowDailyReport extends DeviceReport
{
    public const TYPE_ID = 85;

    public array $intervals = [];
    private float $lastEngineHours;

    public function typeID(): int
    {
        return self::TYPE_ID;
    }

    public function title(): string
    {
        return trans('front.flow_daily');
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
            new \DateInterval('P1D'),
            new \DateTime(date('Y-m-d H:i:s', strtotime(Formatter::time()->human($this->date_to)))),
        );

        $dateTo = $period->end;

        $period = iterator_to_array($period);
        $period[] = $dateTo;

        foreach ($period as $value) {
            $date = $value->format('Y-m-d');

            $this->intervals[$date] = [
                'net_amount_min'    => null,
                'net_amount_max'    => null,
                'net_amount_diff'   => null,
                'engine_hours_from' => null,
                'engine_hours_to'   => null,
                'engine_hours_diff' => null,
                'rate_m3_h'         => null,
                'rate_l_s'          => null,
                'location'          => null,
            ];
        }
    }

    protected function afterGenerate()
    {
        $totalsStruct = [
            'net_amount_diff'   => null,
            'engine_hours_diff' => null,
            'rate_m3_h'         => null,
            'rate_l_s'          => null,
        ];

        usort($this->items, fn ($a, $b) => strcmp(
            $a['meta']['device.group_id']['value'] ?? '',
            $b['meta']['device.group_id']['value'] ?? '',
        ));

        foreach ($this->items as &$item) {
            $prevDate = null;
            $groupId = $item['meta']['device.group_id']['value'];

            $item['totals'] = $totalsStruct;

            if (!isset($this->totals[$groupId])) {
                $this->totals[$groupId] = $totalsStruct;
            }

            foreach ($item['table'] as $date => &$row) {
                if ($prevDate) {
                    $row['net_amount_min'] = $item['table'][$prevDate]['net_amount_max'];
                }

                if (isset($row['net_amount_min']) && isset($row['net_amount_max'])) {
                    $row['net_amount_diff'] = round($row['net_amount_max'] - $row['net_amount_min'], 2);

                    $this->totals[$groupId]['net_amount_diff'] += $row['net_amount_diff'];
                    $item['totals']['net_amount_diff'] += $row['net_amount_diff'];
                }

                if (isset($row['engine_hours_from']) && isset($row['engine_hours_to'])) {
                    $row['engine_hours_diff'] = round($row['engine_hours_to'] - $row['engine_hours_from'], 2);

                    $this->totals[$groupId]['engine_hours_diff'] += $row['engine_hours_diff'];
                    $item['totals']['engine_hours_diff'] += $row['engine_hours_diff'];
                }

                $prevDate = $date;
            }

            $this->calculateFlowRate($item['totals']);
        }

        array_walk($this->totals, function (&$item) {
            $this->calculateFlowRate($item);
        });
    }
    
    protected function calculateFlowRate(array &$data): void
    {
        if ($data['net_amount_diff'] === null || $data['engine_hours_diff'] === null) {
            return;
        }

        $data['rate_m3_h'] = $data['engine_hours_diff']
            ? round($data['net_amount_diff'] / $data['engine_hours_diff'], 2)
            : 0;

        $data['rate_l_s'] = round($data['rate_m3_h'] * 1000 / 3600, 2);
    }

    protected function generateDevice(Device $device): array
    {
        $this->lastEngineHours = 0;
        $rows = $this->intervals;

        try {
            $engineHoursAttr = $this->getDeviceEngineHoursAttribute($device);

            $positions = $device->positions()
                ->whereBetween('time', [$this->date_from, $this->date_to])
                ->cursor();

            foreach ($positions as $position) {
                $date = date('Y-m-d', strtotime(Formatter::time()->human($position->time)));

                $netAmount = $this->getNetAmount($position);
                $engineHours = $this->getEngineHours($position, $engineHoursAttr);

                if (!isset($rows[$date]['engine_hours_from'])) {
                    $rows[$date]['engine_hours_from'] = $engineHours;
                }

                $rows[$date]['engine_hours_to'] = $engineHours;
                $rows[$date]['engine_hours_diff'] = round($rows[$date]['engine_hours_to'] - $rows[$date]['engine_hours_from'], 2);

                if ($netAmount === null) {
                    continue;
                }

                if ($rows[$date]['net_amount_min'] > $netAmount || $rows[$date]['net_amount_min'] === null) {
                    $rows[$date]['net_amount_min'] = $netAmount;
                }

                if ($rows[$date]['net_amount_max'] < $netAmount || $rows[$date]['net_amount_max'] === null) {
                    $rows[$date]['net_amount_max'] = $netAmount;
                }

                if ($rows[$date]['location'] === null) {
                    $rows[$date]['location'] = $this->getLocation($position);
                }
            }

            foreach ($rows as &$row) {
                if (isset($row['net_amount_min']) && isset($row['net_amount_max'])) {
                    $row['net_amount_diff'] = $row['net_amount_max'] - $row['net_amount_min'];
                }

                if ($row['net_amount_diff'] !== null && $row['engine_hours_diff'] !== null) {
                    $row['rate_m3_h'] = $row['engine_hours_diff'] ? $row['net_amount_diff'] / $row['engine_hours_diff'] : 0;
                    $row['rate_l_s'] = $row['rate_m3_h'] * 1000 / 3600;

                    $row['rate_m3_h'] = round($row['rate_m3_h'], 2);
                    $row['rate_l_s'] = round($row['rate_l_s'], 2);
                }
            }
        } catch (QueryException $e) {}

        return [
            'meta'       => $this->getDeviceMeta($device),
            'table'      => $rows,
        ];
    }

    private function getEngineHours(TraccarPosition $position, string $attr): float
    {
        $value = $attr === TraccarPosition::ENGINE_HOURS_KEY
            ? $position->getEngineHours()
            : $position->getVirtualEngineHours();

        if (empty($value)) {
            return $this->lastEngineHours;
        }

        $value = round($value / 3600, 2);

        return $this->lastEngineHours = $value;
    }

    private function getNetAmount(TraccarPosition $position): ?float
    {
        $params = $position->getParametersAttribute();

        return isset($params['nettotal']) ? round((float)$params['nettotal'], 2) : null;
    }

    private function getDeviceEngineHoursAttribute(Device $device): string
    {
        $positions = $device->positions()
            ->orderliness('asc')
            ->whereBetween('time', [$this->date_from, $this->date_to])
            ->limit(1000)
            ->cursor();

        foreach ($positions as $position) {
            if (!$position->hasParameter(TraccarPosition::ENGINE_HOURS_KEY)) {
                continue;
            }

            return TraccarPosition::ENGINE_HOURS_KEY;
        }

        return TraccarPosition::VIRTUAL_ENGINE_HOURS_KEY;
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