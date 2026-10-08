<?php namespace Tobuli\Validation;

use Illuminate\Validation\Factory as IlluminateValidator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class UserAccountSettingsFormValidator extends Validator {

    /**
     * @var array Validation rules for the test form, they can contain in-built Laravel rules or our custom rules
     */
    public $rules = [
        'update' => [
            'timezone_id' => 'exists:timezones,id',
            'unit_of_distance' => 'in:km,mi,nm',
            'unit_of_capacity' => 'in:lt,gl',
            'unit_of_altitude' => 'in:mt,ft'
        ]
    ];

    function __construct( IlluminateValidator $validator ) {
        parent::__construct( $validator );
        $this->rules['update']['date_format'] = Rule::in(array_keys(config('tobuli.date_formats')));
        $this->rules['update']['time_format'] = Rule::in(array_keys(config('tobuli.time_formats')));
    }
}
