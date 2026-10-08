<?php

namespace Tobuli\Helpers\RelationTransfer\Sync;

class UserDevicesAlertsDevicesSync extends AbstractUserDevicesRelationSync
{
    protected string $pivotTable = 'alert_device';
    protected string $entity = \Tobuli\Entities\Alert::class;
}