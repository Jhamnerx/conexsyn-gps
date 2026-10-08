<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\AbstractPaginator;

abstract class AbstractIconController extends Controller
{
    protected bool $useDefault = true;
    protected bool $useNothing = false;

    public function index()
    {
        $data = request()->all();
        $filters = request()->get('filters', []);

        $data['items'] = $this->getIcons($filters);
        $data['useDefault'] = $this->useDefault;
        $data['useNothing'] = $this->useNothing;
        $data['currentIcon'] = $this->getQuery()->find($data['currentValue']);

        $data += $this->getIndexData();

        return view('front::IconsModal.index')->with($data);
    }

    public function table(?string $type)
    {
        $filters = request()->get('filters', []);

        if ($type) {
            $filters['type'] = $type;
        }

        $data = ['items' => $this->getIcons($filters)];

        return view('front::IconsModal.table')->with($data);
    }

    protected function getIcons(array $filters = []): AbstractPaginator
    {
        return $this->getQuery($filters)
            ->orderBy('order', 'DESC')
            ->orderBy('id', 'ASC')
            ->paginate();
    }

    protected function getIndexData(): array
    {
        return [];
    }

    abstract protected function getBaseQuery(): Builder;

    protected function getQuery(array $filters = []): Builder
    {
        $query = $this->getBaseQuery()->filter($filters);

        return $query;
    }
}