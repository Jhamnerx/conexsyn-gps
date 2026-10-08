<?php namespace Tobuli\Validation;

use Illuminate\Validation\Factory as IlluminateValidator;
use Illuminate\Validation\Rule;
use Tobuli\Reports\ReportManager;

class ReportFormValidator extends Validator {

    /**
     * @var array Validation rules for the test form, they can contain in-built Laravel rules or our custom rules
     */
    public $rules = [
        'create' => [
            'speed_limit' => 'numeric',
            'devices' => 'array',
            'geofences' => 'array',
            'date_from' => 'date',
            'date_to' => 'date',
            'from_time' => 'regex:/^\d{2}:\d{2}(:\d{2})?$/',
            'to_time'   => 'regex:/^\d{2}:\d{2}(:\d{2})?$/',
        ]
    ];

    public function __construct(IlluminateValidator $validator)
    {
        parent::__construct($validator);

        $this->rules['create']['format'] = 'required|' . Rule::in(array_keys(ReportManager::getFormats()));
    }
}
