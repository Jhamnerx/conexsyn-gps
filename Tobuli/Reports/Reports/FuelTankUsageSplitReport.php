<?php

namespace Tobuli\Reports\Reports;

use Tobuli\History\Actions\GroupRfidSplit;

class FuelTankUsageSplitReport extends FuelTankUsageReport
{
    const TYPE_ID = 96;

    protected string $actionGroupRfid = GroupRfidSplit::class;

    public function title()
    {
        return trans('front.fuel_tank_usage_split');
    }
}