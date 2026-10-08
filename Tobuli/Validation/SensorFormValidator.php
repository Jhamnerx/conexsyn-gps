<?php namespace Tobuli\Validation;

use Illuminate\Validation\Factory as IlluminateValidator;

class SensorFormValidator extends Validator {

    /**
     * @var array Validation rules for the test form, they can contain in-built Laravel rules or our custom rules
     */
    public $rules = [

    ];

    public function __construct( IlluminateValidator $validator ) {
        $this->_validator = $validator;
    }

}