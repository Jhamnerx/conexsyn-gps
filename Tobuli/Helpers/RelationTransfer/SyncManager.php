<?php

namespace Tobuli\Helpers\RelationTransfer;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tobuli\Entities\Device;
use Tobuli\Entities\User;
use Tobuli\Helpers\RelationTransfer\Sync\AbstractRelationSync;
use Tobuli\Helpers\RelationTransfer\Sync\DeviceUsersAlertsDevicesSync;
use Tobuli\Helpers\RelationTransfer\Sync\DeviceUsersReportsDevicesSync;
use Tobuli\Helpers\RelationTransfer\Sync\UserDevicesAlertsDevicesSync;
use Tobuli\Helpers\RelationTransfer\Sync\UserDevicesReportsDevicesSync;

class SyncManager
{
    private array $map;
    private bool $async = true;

    public function __construct()
    {
        $this->map = [
            [(new User())->devices(), [
                UserDevicesAlertsDevicesSync::class,
                UserDevicesReportsDevicesSync::class,
            ]],
            [(new Device())->users(), [
                DeviceUsersAlertsDevicesSync::class,
                DeviceUsersReportsDevicesSync::class,
            ]],
        ];
    }

    /**
     * @see \Illuminate\Database\Eloquent\Relations\Concerns\InteractsWithPivotTable::sync
     * Apply `sync` method results
     */
    public function applyChanges(BelongsToMany $relation, array $changes): void
    {
        $this->run($relation, 'applyChanges', $changes);
    }

    public function sync(BelongsToMany $relation): void
    {
        $this->run($relation, 'sync');
    }

    public function attach(BelongsToMany $relation, $items): void
    {
        $this->run($relation, 'attach', $items);
    }

    public function detach(BelongsToMany $relation, $items): void
    {
        $this->run($relation, 'detach', $items);
    }

    private function run(BelongsToMany $relation, string $method, $data = null): void
    {
        $syncs = $this->getRelationSyncs($relation);
        $parent = $relation->getParent();

        foreach ($syncs as $syncClass) {
            /** @var AbstractRelationSync $sync */
            $sync = new $syncClass($parent, $method, $data);

            $this->async
                ? dispatch($sync)
                : dispatch_sync($sync);
        }
    }

    private function getRelationSyncs(BelongsToMany $relation): array
    {
        foreach ($this->map as $meta) {
            /**
             * @var BelongsToMany $type
             */
            [$type, $syncs] = $meta;

            if (get_class($relation->getModel()) !== get_class($type->getModel())) {
                continue;
            }

            if (get_class($relation->getParent()) !== get_class($type->getParent())) {
                continue;
            }

            return $syncs;
        }

        throw new \InvalidArgumentException('Relation not supported');
    }

    public function setAsync(bool $async = true): self
    {
        $this->async = $async;

        return $this;
    }
}