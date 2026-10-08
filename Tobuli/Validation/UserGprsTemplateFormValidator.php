<?php namespace Tobuli\Validation;

use CustomFacades\Repositories\TrackerPortRepo;
use Illuminate\Validation\Factory as IlluminateValidator;
use Tobuli\Entities\UserGprsTemplate;

class UserGprsTemplateFormValidator extends Validator {

    /**
     * @var array Validation rules for the test form, they can contain in-built Laravel rules or our custom rules
     */
    public $rules = [
        'create' => [
            'title'   => 'required',
            'message' => 'required',
            'adapted'    => '',
            'devices' => 'array',
            'devices.*' => 'integer',
            'selected_devices'  => 'array',
            'device_types' => 'required_if:adapted,device_types|array',
            'device_types.*' => 'integer',
        ],
        'update' => [
            'title' => 'required',
            'message' => 'required',
            'adapted'    => '',
            'devices' => 'array',
            'devices.*' => 'integer',
            'selected_devices'  => 'array',
            'device_types' => 'required_if:adapted,device_types|array',
            'device_types.*' => 'integer',
        ]
    ];

    public function __construct( IlluminateValidator $validator ) {
        $this->_validator = $validator;

        $protocols = TrackerPortRepo::getProtocolList();

        $this->rules['create']['protocol'] = 'required_if:adapted,protocol|in:' . implode(',', array_keys($protocols));
        $this->rules['update']['protocol'] = 'required_if:adapted,protocol|in:' . implode(',', array_keys($protocols));

        $adapties = UserGprsTemplate::getAdapties();

        $this->rules['create']['adapted'] = 'in:' . implode(',', array_keys($adapties));
        $this->rules['update']['adapted'] = 'in:' . implode(',', array_keys($adapties));

        if (request()->get('adapted') == 'devices') {
            $this->rules['create']['devices']          .= '|required_without:selected_devices';
            $this->rules['create']['selected_devices'] .= '|required_without:devices';
            $this->rules['update']['devices']          .= '|required_without:selected_devices';
            $this->rules['update']['selected_devices'] .= '|required_without:devices';
        }
    }

}   //end of class


//EOF