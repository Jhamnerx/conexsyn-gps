<?php

namespace Tobuli\Services;

use CustomFacades\Validators\GeofenceGroupFormValidator;
use Tobuli\Entities\Geofence;
use Tobuli\Entities\GeofenceGroup;


class GeofenceGroupService extends ModelService
{
    public function __construct()
    {
        $this->setDefaults([
            'open' => true,
        ]);

        $this->setValidationRulesStore(
            GeofenceGroupFormValidator::getFacadeRoot()->rules['create']
        );

        $this->setValidationRulesUpdate(
            GeofenceGroupFormValidator::getFacadeRoot()->rules['update']
        );
    }

    public function store(array $data)
    {
        return GeofenceGroup::create($data);
    }

    public function update($geofenceGroup, array $data)
    {
        return $geofenceGroup->update($data);
    }

    public function delete($geofenceGroup)
    {
        return $geofenceGroup->delete();
    }

    public function syncItems($geofenceGroup, $items)
    {
        $geofenceGroup->items()->update(['group_id' => null]);

        Geofence::whereIn('id', $items)->update(['group_id' => $geofenceGroup->id]);
    }

}
