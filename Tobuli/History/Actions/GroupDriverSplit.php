<?php

namespace Tobuli\History\Actions;

use Tobuli\History\Group;

class GroupDriverSplit extends ActionGroup
{
    protected ?int $currentId = null;

    public static function required()
    {
        return [
            AppendDriver::class
        ];
    }

    public function boot()
    {
    }

    public function proccess($position)
    {
        if (!$this->isChange($position)) {
            return;
        }

        $groups = $this->history->getGroups()->actives();

        foreach ($groups as $group) {
            $this->history->groupEnd($group->getKey(), $position);

            $regroup = new Group($group->getKey());
            $regroup->setMetaContainer($group->getMetaContainer());

            $this->history->groupStart($regroup, $position);
        }
    }

    protected function isChange($position): bool
    {
        $driverId = $position->driver ? $position->driver->id : 0;

        if ($this->currentId === null) {
            $this->currentId = $driverId;
        }

        if ($this->currentId === $driverId) {
            return false;
        }

        $this->currentId = $driverId;

        return true;
    }
}