<?php namespace Tobuli\Services\Commands;

use CustomFacades\Field;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Tobuli\Entities\User;

class DevicesCommandsSmsCustom implements DevicesCommands
{
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param EloquentCollection|Builder $devices
     */
    public function get($devices, bool $intersect): Collection
    {
        $command = [
            'type'  => 'custom',
            'title' => trans('front.custom_command'),
            'connection' => SendCommandService::CONNECTION_SMS,
            'attributes' => collect([
                Field::text('message', trans('validation.attributes.message'))
            ])
        ];

        return collect([$command]);
    }
}