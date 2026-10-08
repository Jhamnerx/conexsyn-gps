<?php

namespace Tobuli\Helpers\RelationTransfer\Sync;

class DeviceUsersAlertsDevicesSync extends AbstractDeviceUsersRelationSync
{
    protected string $pivotTable = 'alert_device';
    protected string $entity = \Tobuli\Entities\Alert::class;
}