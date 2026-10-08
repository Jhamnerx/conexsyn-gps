<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\AbstractIconController;
use Illuminate\Database\Eloquent\Builder;
use Tobuli\Entities\DeviceIcon;

class DeviceIconController extends AbstractIconController
{
    protected function getIndexData(): array
    {
        return [
            'defaultIcon' => $this->getQuery()->find(0),
            'types' => [
                'rotating' => trans('front.rotating_icon'),
                'icon' => trans('front.icon')
            ],
        ];
    }

    protected function getBaseQuery(array $filters = []): Builder
    {
        return DeviceIcon::where(function (Builder $query) {
            $query->whereNull('user_id')
                ->orWhere('user_id', $this->user->id);
        });
    }
}