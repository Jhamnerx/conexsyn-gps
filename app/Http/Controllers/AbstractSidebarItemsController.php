<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Tobuli\Entities\AbstractGroup;

abstract class AbstractSidebarItemsController extends Controller
{
    const LOAD_LIMIT = 100;

    protected string $repo;
    protected string $viewDir;
    protected string $nextRoute;
    protected string $groupClass;

    protected AbstractGroup $groupModel;
    protected Model $itemModel;

    public function __construct()
    {
        parent::__construct();

        $this->groupModel = new $this->groupClass();
        $this->itemModel = $this->groupModel->items()->getModel();
    }

    public function index()
    {
        return request()->filled('oPage') ? $this->items() : $this->groups();
    }

    public function groups()
    {
        $this->checkException($this->repo, 'view');

        $groups = $this->getGroups();

        return view("$this->viewDir.groups")->with(compact('groups'));
    }

    public function items()
    {
        $this->checkException($this->repo, 'view');

        $items = $this->getGroupItems(
            request()->group_id,
            request()->s,
            request()->filters,
        );

        return view("$this->viewDir.items")->with(['items' => $items]);
    }

    protected function getGroups()
    {
        $search = request()->get('s');
        $filters = request()->get('filters');

        $groups = $this->groupModel->userOwned($this->user)
            ->whereHas('items', function (Builder $q) use ($search, $filters) {
                $q->when($search, function ($q) use ($search) {
                    $q->search($search);
                });
                $q->when($filters, function ($q) use ($filters) {
                    $q->filter($filters);
                });
                $q->limit(1);
            })
            ->orderBy('title')
            ->paginate();

        $groups->loadCount(['items', 'itemsVisible']);
        if ($search || $filters) {
            $groups->loadCount(['items as items_filter_count' => function($q) use ($search, $filters) {
                $q->search($search);
                $q->filter($filters);
            }]);
        }

        if (request()->get('page', 1) <= 1) {
            $ungrouped = $this->groupModel->makeUngroupedWithCount($this->user);

            if ($ungrouped->items_count) {
                $groups->prepend($ungrouped);

                if ($search || $filters) {
                    $ungrouped->items_filter_count = $ungrouped->items()->search($search)->filter($filters)->count();
                }
            }
        }

        $groups->setCollection($groups->getCollection()->transform(function(AbstractGroup $group) use ($search, $filters) {
            $open = $group->open || $search;

            $items = $open ? $this->getGroupItems($group->id, $search, $filters) : collect()->paginate(1);

            return [
                'id'        => $group->id,
                'title'     => $group->title,
                'open'      => $open,
                'count'     => $open ? $items->total() : ($group->items_filter_count ?? $group->items_count),
                'active'    => $group->items_count === $group->items_visible_count,
                'next'      => route($this->nextRoute, [
                    'group_id'  => $group->id,
                    's'         => $search,
                    'filters'   => $filters,
                    'oPage'     => 1,
                ]),
                'items'     => $items
            ];
        })->filter(function($group) {
            return $group['count'];
        }));

        return $groups;
    }

    /**
     * @return Builder|Relation
     */
    protected function getGroupItemsQuery($groupId, $search, $filters)
    {
        $query = $this->itemModel->userOwned($this->user);

        $query->orderBy('name');

        if ($search) {
            $query->search($search);
        }

        if ($filters) {
            $query->filter($filters);
        }

        if ($groupId === null) {
            return $query;
        }

        if ($groupId) {
            return $query->where('group_id', $groupId);
        }

        return $query->whereNull('group_id');
    }

    protected function getGroupItems($groupId, $search, $filters)
    {
        return $this->getGroupItemsQuery($groupId, $search, $filters)
            ->paginate(self::LOAD_LIMIT, ['*'], 'oPage')
            ->setPath(route($this->nextRoute))
            ->appends([
                'group_id' => $groupId,
                's' => $search,
                'filters' => $filters
            ]);
    }
}
