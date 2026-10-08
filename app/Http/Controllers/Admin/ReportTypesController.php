<?php

namespace App\Http\Controllers\Admin;

use Tobuli\Reports\ReportTypesConfig;
use Tobuli\Reports\ReportManager;

class ReportTypesController extends BaseController
{
    public function index(ReportManager $reportManager)
    {
        $reports = $reportManager->getAvailableList();

        return view('Admin.ReportTypes.index')->with(compact('reports'));
    }

    public function store(ReportTypesConfig $reportConfig)
    {
        $reportConfig->store(request('reports'));

        return ['status' => 1];
    }
}
