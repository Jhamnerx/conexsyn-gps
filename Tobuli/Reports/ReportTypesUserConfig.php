<?php

namespace Tobuli\Reports;

use Illuminate\Support\Facades\Validator;
use Tobuli\Entities\User;
use Tobuli\Entities\UserReportTypePivot;
use Tobuli\Exceptions\ValidationException;

class ReportTypesUserConfig
{
    private ReportManager $reportManager;

    public function __construct()
    {
        $this->reportManager = new ReportManager();
    }

    public function store(User $user, array $input): void
    {
        $this->validate($input);

        UserReportTypePivot::where('user_id', $user->id)->delete();

        $relations = [];

        foreach ($input as $reportId) {
            $relations[] = ['user_id' => $user->id, 'report_type_id' => $reportId];
        }

        if ($relations) {
            UserReportTypePivot::insert($relations);
        }
    }

    private function validate(array $input): void
    {
        Validator::validate(['reports' => $input], [
            'reports' => 'array',
            'reports.*' => 'integer',
        ]);

        $types = $this->reportManager->getAvailableList();

        foreach ($input as $id) {
            if (!isset($types[$id]) || !$types[$id]->isReasonable()) {
                throw new ValidationException(["reports.$id" => trans('front.unsupported') . " - $id"]);
            }
        }
    }
}