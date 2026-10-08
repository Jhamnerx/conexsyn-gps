<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\AbstractIconController;
use Illuminate\Database\Eloquent\Builder;
use Tobuli\Entities\SensorIcon;

class SensorIconController extends AbstractIconController
{
    protected bool $useDefault = false;
    protected bool $useNothing = false;

    protected function getIndexData(): array
    {
        return [
            'nothingIcon' => new SensorIcon([
                'path' => 'assets/images/no-icon.png'
            ]),
            'types' => [
                'icon' => trans('front.icon')
            ],
        ];
    }

    protected function getBaseQuery(array $filters = []): Builder
    {
        return SensorIcon::where(function (Builder $query) {
            $query->whereNull('user_id')
                ->orWhere('user_id', $this->user->id);
        });
    }
}