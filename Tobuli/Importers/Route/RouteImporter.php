<?php

namespace Tobuli\Importers\Route;

use CustomFacades\Repositories\RouteRepo;
use Illuminate\Support\Facades\Cache;
use Tobuli\Entities\RouteGroup;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Importers\Importer;

class RouteImporter extends Importer
{
    protected $defaults = [
        'active' => true,
        'color'  => '#ffffff',
    ];

    protected function getDefaults()
    {
        return $this->defaults;
    }

    protected function importItem($data, $attributes = [])
    {
        $data = $this->mergeDefaults($data);
        $data = $this->setUser($data, $attributes);

        if ( ! $this->validate($data)) {
            return;
        }

        $this->normalize($data);

        if ($this->getRoute($data)) {
            return;
        }

        $this->create($data);
    }

    private function normalize(array &$data): array
    {
        return $data;
    }

    private function getRoute($data)
    {
        return RouteRepo::first($data);
    }

    private function create($data)
    {
        beginTransaction();
        try {
            if ( ! empty($data['group'])) {
                $this->createGroup($data);
            } else {
                $data['group_id'] = null;
            }

            RouteRepo::create($data);
        } catch (\Exception $e) {
            rollbackTransaction();
            throw $e;
        }
        commitTransaction();
    }

    private function createGroup(& $data)
    {
        $key = md5("{$data['user_id']}.{$data['group']}");

        $data['group_id'] = Cache::store('array')->rememberForever($key, function() use ($data) {
            $group =  RouteGroup::firstOrCreate([
                'title'   => $data['group'],
                'user_id' => $data['user_id']
            ]);

            return $group->id;
        });

        unset($data['group']);
    }

    public function getValidationBaseRules(): array
    {
        return [
            'name'          => 'required',
            'polyline'      => 'required',
            'color'         => 'required|min:7|max:7',
        ];
    }
}
