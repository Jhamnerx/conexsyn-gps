<?php namespace Tobuli\Validation;

use Illuminate\Validation\Factory as IlluminateValidator;
use Illuminate\Validation\Rule;
use Tobuli\Reports\ReportManager;

class ReportSaveFormValidator extends ReportFormValidator {

    public function __construct(IlluminateValidator $validator)
    {
        parent::__construct($validator);

        $this->rules['create']['title'] = 'required';
    }

}