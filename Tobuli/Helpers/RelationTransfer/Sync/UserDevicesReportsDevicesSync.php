<?php

namespace Tobuli\Helpers\RelationTransfer\Sync;

class UserDevicesReportsDevicesSync extends AbstractUserDevicesRelationSync
{
    protected string $pivotTable = 'report_device_pivot';
    protected string $entity = \Tobuli\Entities\Report::class;
}