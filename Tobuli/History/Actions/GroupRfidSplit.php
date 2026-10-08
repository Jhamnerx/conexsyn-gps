<?php

namespace Tobuli\History\Actions;

use Tobuli\History\Group;

class GroupRfidSplit extends ActionQuit
{
    public const KEY = 'rfid';

    public static function required()
    {
        return [AppendRfid::class];
    }

    public function boot()
    {
    }

    public function proccess(&$position)
    {
        if (!$position->rfid) {
            return;
        }

        $this->history->groupEnd(self::KEY, $position);

        if ($this->isQuitable($position)) {
            $position->quit = true;
            return;
        }

        $this->history->groupStart(new Group(self::KEY), $position);
    }

    protected function isQuitable($position): bool
    {
        return false;
    }
}