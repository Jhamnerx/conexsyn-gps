<?php

namespace Tobuli\Services;

use CustomFacades\Validators\PoiGroupFormValidator;
use Tobuli\Entities\Poi;
use Tobuli\Entities\PoiGroup;


class PoiGroupService extends ModelService
{
    public function __construct()
    {
        $this->setDefaults([
            'open' => true,
        ]);

        $this->setValidationRulesStore(
            PoiGroupFormValidator::getFacadeRoot()->rules['create']
        );

        $this->setValidationRulesUpdate(
            PoiGroupFormValidator::getFacadeRoot()->rules['update']
        );
    }

    public function store(array $data)
    {
        return PoiGroup::create($data);
    }

    public function update($poiGroup, array $data)
    {
        return $poiGroup->update($data);
    }

    public function delete($poiGroup)
    {
        return $poiGroup->delete();
    }

    public function syncItems($poiGroup, $items)
    {
        $poiGroup->pois()->update(['group_id' => null]);

        Poi::whereIn('id', $items)->update(['group_id' => $poiGroup->id]);
    }

}
