<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\View as FacadeView;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tobuli\Entities\ModelChangeLog;
use Tobuli\Entities\User;
use Tobuli\Exporters\EntityManager\ModelChangeLog\ExportManager;
use Tobuli\Helpers\Formatter\Facades\Formatter;
use Tobuli\Services\EntityLoader\UsersLoader;

class ModelChangeLogsController extends BaseController
{
    protected UsersLoader $usersLoader;

    protected function afterAuth($user)
    {
        $this->usersLoader = new UsersLoader($user);
        $this->usersLoader->setRequestKey('causer_id');
    }

    public function index(Request $request): View
    {
        $input = $request->input();

        $sort = $input['sorting'] ?? [];
        $subjects = $input['search_subjects'] ?? [];
        $causer = $input['search_causer'] ?? '';
        $descriptions = $input['search_descriptions'] ?? [];

        $query = $this->getListQuery($input);

        $items = $query->toPaginator(25, $sort['sort_by'] ?? 'created_at', $sort['sort'] ?? 'desc');

        if ($subjects) {
            $items->sorting['subjects'] = Arr::first($items->items())->log_name ?? trans('global.not_found');
        }

        $items->sorting['causer'] = $causer;
        $items->sorting['descriptions'] = $descriptions;

        $descriptions = $this->getDescriptions();

        return FacadeView::make('admin::ModelChangeLogs.' . ($request->ajax() ? 'table' : 'index'))
            ->with(compact('items', 'input', 'descriptions'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $attributes = [
            'causer_name',
            'subject_name',
            'subject_type',
            'description',
            'log_name',
            'attributes_count',
            'created_at',
            'ip',
        ];

        $query = $this->getListQuery($request->input());

        return (new ExportManager($query))
            ->download($attributes, 'csv');
    }

    private function getListQuery(array $input): Builder
    {
        $causer = $input['search_causer'] ?? '';
        $subjects = $input['search_subjects'] ?? [];
        $searchPhrase = $input['search_phrase'] ?? '';
        $descriptions = $input['search_descriptions'] ?? [];
        $dateFrom = $input['search_date_from'] ?? null;
        $dateTo = $input['search_date_to'] ?? null;

        $query = ModelChangeLog::with(['subject', 'causer'])->search($searchPhrase);

        if ($causer) {
            $query->where('causer_id', $causer);
        }

        if ($subjects) {
            $query->where(function (Builder $query) use ($subjects) {
                foreach ($subjects as $subject) {
                    $query->orWhere(function (Builder $query) use ($subject) {
                        try {
                            [$subjectType, $subjectId] = explode('-', $subject, 2);;
                        } catch (\ErrorException $e) {
                            return;
                        }

                        $query->where('subject_type', $subjectType);
                        $query->where('subject_id', $subjectId);
                    });
                }
            });
        }

        if ($dateFrom) {
            $query->where('created_at', '>=', Formatter::time()->reverse($dateFrom));
        }

        if ($dateTo) {
            $query->where('created_at', '<=', Formatter::time()->reverse($dateTo));
        }

        if ($descriptions) {
            $query->whereIn('description', $descriptions);
        }

        if (!$this->user->isGod()) {
            $query->where('causer_id', '!=', User::getGodID());
        }

        return $query;
    }

    public function show($id): View
    {
        $item = ModelChangeLog::find($id, ['id', 'properties']);

        return FacadeView::make('admin::ModelChangeLogs.show')->with(['item' => $item]);
    }

    public function causers()
    {
        $items = $this->usersLoader->get();

        return response()->json($items);
    }

    private function getCausers(): array
    {
        return \Cache::remember('model_change_logs_causers', 1, function () {
            return User::whereIn('id', function (\Illuminate\Database\Query\Builder $query) {
                $query->select('causer_id')
                    ->from((new ModelChangeLog())->getTable());
            })
                ->pluck(User::$displayField, 'id')
                ->prepend(trans('front.nothing_selected'), '')
                ->all();
        });
    }

    private function getDescriptions(): array
    {
        return [
            'created'       => 'created',
            'updated'       => 'updated',
            'deleted'       => 'deleted',
            'login_fail'    => 'login_fail',
            'login_success' => 'login_success',
        ];
    }
}