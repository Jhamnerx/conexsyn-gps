<?php

namespace Tobuli\Services;

use CustomFacades\Validators\RouteGroupFormValidator;
use Tobuli\Entities\Route;
use Tobuli\Entities\RouteGroup;

class RouteGroupService extends ModelService
{
    public function __construct()
    {
        $this->setDefaults([
            'open' => true,
        ]);

        $this->setValidationRulesStore(
            RouteGroupFormValidator::getFacadeRoot()->rules['create']
        );

        $this->setValidationRulesUpdate(
            RouteGroupFormValidator::getFacadeRoot()->rules['update']
        );
    }

    public function store(array $data)
    {
        return RouteGroup::create($data);
    }

    public function update($routeGroup, array $data)
    {
        return $routeGroup->update($data);
    }

    public function delete($routeGroup)
    {
        return $routeGroup->delete();
    }

    public function syncItems($routeGroup, $items)
    {
        $routeGroup->routes()->update(['group_id' => null]);

        Route::whereIn('id', $items)->update(['group_id' => $routeGroup->id]);
    }

}
