<?php

namespace Tobuli\Reports;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Tobuli\Exceptions\ValidationException;

class ReportTypesConfig
{
    private ReportManager $reportManager;

    public function __construct()
    {
        $this->reportManager = new ReportManager();
    }

    public function store(array $input): void
    {
        $this->validate($input);
        $this->normalize($input);

        $key = 'reports';

        if (count($input) === 1) {
            $key .= '.' . array_key_first($input);
            $input = Arr::first($input);
        }

        $current = settings($key) ?? [];
        settings($key, array_merge($current, $input));
    }

    private function validate(array $input): void
    {
        Validator::validate(['reports' => $input], [
            'reports' => 'array',
            'reports.*.status' => 'bool',
        ]);

        $types = $this->reportManager->getAvailableList();

        foreach ($input as $id => $report) {
            if (!isset($types[$id]) || !$types[$id]->isReasonable()) {
                throw new ValidationException(["reports.$id" => trans('front.unsupported') . " - $id"]);
            }
        }
    }

    private function normalize(array &$input): void
    {
        foreach ($input as &$report) {
            $report = Arr::only($report, ['status']);
        }
    }
}