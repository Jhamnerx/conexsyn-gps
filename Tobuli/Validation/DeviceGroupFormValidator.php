<?php namespace Tobuli\Validation;

use Illuminate\Validation\Factory as IlluminateValidator;

class DeviceGroupFormValidator extends Validator {

    /**
     * @var array Validation rules for the test form, they can contain in-built Laravel rules or our custom rules
     */
    public $rules = [
        'create' => [
            'title'   => 'required',
        ],
        'update' => [
            'title'   => 'required',
        ]
    ];

    function __construct( IlluminateValidator $validator ) {
        parent::__construct( $validator );

        if ( config('tobuli.api') ) {
            $this->rules['create']['devices'] = 'required|array|exists:devices,id';
            $this->rules['update']['devices'] = 'required|array|exists:devices,id';
        } else {
            $this->rules['create']['selected_devices'] = 'required|array';
            $this->rules['update']['selected_devices'] = 'array';
        }
    }

}   //end of class


//EOF