<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\View;
use Tobuli\Entities\User;
use Tobuli\Entities\UserReportTypePivot;
use Tobuli\Reports\Report;
use Tobuli\Reports\ReportManager;

class ClientReportTypesController extends BaseController
{
    private ReportManager $reportManager;

    public function __construct(ReportManager $reportManager)
    {
        parent::__construct();

        $this->reportManager = $reportManager;
    }

    public function get(int $id)
    {
        $user = $id ? User::find($id) : null;

        $this->checkException('users', 'view', $user);

        $hasConfig = $user && UserReportTypePivot::where('user_id', $user->id)->count();

        $reportTypes = $this->getReportTypes();
        $reportTypesSelected = $this->getReportTypesSelected($user);

        return View::make('Admin.Clients.report_types')->with(compact('hasConfig', 'reportTypes', 'reportTypesSelected'));
    }

    private function getReportTypes(): array
    {
        $reports = $this->reportManager->getReasonableList();

        $transformed = [];

        foreach ($reports as $report) {
            $transformed[$report->typeID()] = $report->title();
        }

        return $transformed;
    }

    private function getReportTypesSelected(?User $user): array
    {
        $reports = $this->reportManager->getUsableList($user ?: new User());

        $transformed = [];

        foreach ($reports as $id => $report) {
            $transformed[$id] = $id;
        }

        return $transformed;
    }
}
