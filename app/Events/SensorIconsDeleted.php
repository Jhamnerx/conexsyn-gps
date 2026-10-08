<?php
 
namespace App\Events;
 
class SensorIconsDeleted extends Event
{
    public array $ids;

    public function __construct(array $ids)
    {
        $this->ids = $ids;
    }
}
