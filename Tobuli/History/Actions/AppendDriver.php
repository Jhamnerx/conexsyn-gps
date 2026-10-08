<?php

namespace Tobuli\History\Actions;


use Illuminate\Support\Facades\DB;

class AppendDriver extends ActionAppend
{
    const RADIO = 9000;

    protected $drivers = [];

    protected $next;

    protected $current;

    static public function required()
    {
        return [
            AppendDuration::class
        ];
    }

    public function boot(){
        $this->getDriversHistory();
        $this->setNext();
    }

    public function proccess(&$position)
    {
        $position->driver = $this->current;

        if (empty($this->next))
            return;

        if ($this->next->timestamp <= $position->timestamp) {
            $this->current = empty($this->next->id) ? null : $this->next;

            $position->driver = $this->current;

            $this->setNext();
        }
    }

    protected function setNext()
    {
        $this->next = array_shift($this->drivers);
    }

    protected function getDriversHistory()
    {
        $date_from = $this->history->getDateFrom();
        $date_to = $this->history->getDateTo();
        $device = $this->history->getDevice();

        $query = DB::table('user_driver_position_pivot AS dp')
            ->select('d.*', 'dp.date')
            ->leftjoin('user_drivers AS d', 'dp.driver_id', '=', 'd.id')
            ->where('dp.date', '>=', $date_from)
            ->where('dp.date', '<=', $date_to)
            ->where('dp.device_id', $device->id)
            ->orderBy('dp.date', 'desc')
            ->groupBy('dp.date');

        $rows = DB::table('user_driver_position_pivot AS dp')
            ->select('d.*', 'dp.date')
            ->leftjoin('user_drivers AS d', 'dp.driver_id', '=', 'd.id')
            ->where('dp.date', '<=', $date_from)
            ->where('dp.device_id', $device->id)
            ->orderBy('dp.date', 'desc')
            ->limit(1)
            ->union($query)
            ->get()
            ->all();

        foreach ($rows as &$row) {
            $row->timestamp = strtotime($row->date);
            $this->drivers[] = $row;
        }
    }
}