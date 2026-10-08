<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\View;
use Tobuli\Entities\Backup;

class BackupController extends BaseController
{
    public function index()
    {
        return $this->getList('index');
    }

    public function table()
    {
        return $this->getList('table');
    }

    private function getList(string $view)
    {
        $items = Backup::toPaginator(
            10,
            request()->input('sorting.sort_by', 'name'),
            request()->input('sorting.sort', 'DESC')
        )->setPath(route('admin.backup.table'));

        return View::make("Admin.Backup.$view")->with(compact('items'));
    }

    public function processes(int $id)
    {
        $items = Backup::findOrFail($id)->processes;

        return View::make('Admin.Backup.partials.processes')->with(compact('items'));
    }
}
