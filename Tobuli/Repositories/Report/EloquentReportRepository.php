<?php namespace Tobuli\Repositories\Report;

use Tobuli\Entities\Report as Entity;
use Tobuli\Repositories\EloquentRepository;

class EloquentReportRepository extends EloquentRepository implements ReportRepositoryInterface {

    public function __construct( Entity $entity )
    {
        $this->entity = $entity;
    }

    public function searchAndPaginate(array $data, $sort_by, $sort = 'asc', $limit = 10)
    {
        $data = $this->generateSearchData($data);
        $sort = array_merge([
            'sort' => $sort,
            'sort_by' => $sort_by
        ], $data['sorting']);

        $items = $this->entity
            ->orderBy($sort['sort_by'], $sort['sort'])
            ->with('geofences')
            ->withCount(['devices as devices_count' => function ($query) {
                // escape deattached users devices
                $query
                    ->join('user_device_pivot', function ($join) {
                        $join->on('user_device_pivot.device_id', '=', 'report_device_pivot.device_id');
                    })
                    ->whereRaw('user_device_pivot.user_id = reports.user_id');
            }])
            ->where(function ($query) use ($data) {
                if (method_exists($this->entity,'scopeSearch')) {
                    $query->search($data['search_phrase'] ?? null);
                }

                if (count($data['filter'])) {
                    foreach ($data['filter'] as $key=>$value) {
                        $query->where($key, $value);
                    }
                }
            })
            ->paginate($limit);

        $items->sorting = $sort;

        return $items;
    }
}