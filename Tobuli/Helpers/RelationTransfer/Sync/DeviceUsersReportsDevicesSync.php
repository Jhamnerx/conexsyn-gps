<?php

namespace Tobuli\Helpers\RelationTransfer\Sync;

class DeviceUsersReportsDevicesSync extends AbstractDeviceUsersRelationSync
{
    protected string $pivotTable = 'report_device_pivot';
    protected string $entity = \Tobuli\Entities\Report::class;
}