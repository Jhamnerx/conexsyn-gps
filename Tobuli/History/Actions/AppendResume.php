<?php

namespace Tobuli\History\Actions;


class AppendResume extends ActionAppend
{
    protected $offline_timeout;

    static public function required()
    {
        return [
            AppendDuration::class
        ];
    }

    public function boot()
    {
        $this->offline_timeout = settings('main_settings.default_object_online_timeout') * 60;
    }

    public function proccess(&$position)
    {
        $position->resumed = $position->duration > $this->offline_timeout;
    }
}