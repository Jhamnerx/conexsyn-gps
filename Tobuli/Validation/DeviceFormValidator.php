<?php namespace Tobuli\Validation;

use Illuminate\Validation\Factory as IlluminateValidator;
use Tobuli\Services\RequiredFields\DeviceRequiredFieldsService;

class DeviceFormValidator extends Validator
{

    /**
     * @var array Validation rules for the test form, they can contain in-built Laravel rules or our custom rules
     */
    public $rules = [
        'create' => [
            'imei'                => 'required|imei|unique:devices,imei,%s',
            'name'                => 'required|string|between:1,255',
            'icon_id'             => 'exists:device_icons,id',
            'model_id'            => 'exists:device_models,id',
            'fuel_quantity'       => 'numeric',
            'fuel_price'          => 'numeric',
            'fuel_measurement_id' => 'in:1,2,3,4,5',
            'fuel_type'           => 'in:petrol,diesel,biodiesel,biogas,hydrogen,electric,ethanol,petrol_electric,diesel_electric,propane,methanol',
            'fuel_emissions'      => 'numeric|min:0',
            'tail_color'          => 'css_color',
            'tail_length'         => 'numeric|min:0|max:10',
            'max_speed'           => 'numeric|min:1',
            'min_moving_speed'    => 'numeric|min:1|max:50',
            'min_fuel_fillings'   => 'numeric|min:1|max:1000',
            'min_fuel_thefts'     => 'numeric|min:1|max:1000',
            'sim_number'          => 'unique:devices,sim_number',
            //'expiration_date'     => 'required_if:enable_expiration_date,1|date',
            'installation_date'   => 'date',
            'sim_activation_date' => 'date',
            'sim_expiration_date' => 'date',
            'forward.protocol'    => 'required_if:forward.active,1|in:TCP,UDP',
            'msisdn'              => 'sometimes|regex:/^\d{6,20}$/',
            'lbs'                 => 'nullable|boolean',
            'fuel_detect_sec_after_stop' => 'nullable|numeric|min:60|max:300',
            'mtc'                 => 'nullable|boolean',
            'mininter'            => 'nullable|boolean',
            'mininter_type'       => 'nullable|in:serenazgo,policial',
        ],
        'update' => [
            'imei'                => 'sometimes|required|imei|unique:devices,imei,%s',
            'name'                => 'sometimes|required|string|between:1,255',
            'icon_id'             => 'exists:device_icons,id',
            'model_id'            => 'exists:device_models,id',
            'fuel_quantity'       => 'numeric',
            'fuel_price'          => 'numeric',
            'fuel_measurement_id' => 'in:1,2,3,4,5',
            'fuel_type'           => 'in:petrol,diesel,biodiesel,biogas,hydrogen,electric,ethanol,petrol_electric,diesel_electric,propane,methanol',
            'fuel_emissions'      => 'numeric|min:0',
            'tail_color'          => 'css_color',
            'tail_length'         => 'numeric|min:0|max:10',
            'max_speed'           => 'numeric|min:1',
            'min_moving_speed'    => 'numeric|min:1|max:50',
            'min_fuel_fillings'   => 'numeric|min:1|max:1000',
            'min_fuel_thefts'     => 'numeric|min:1|max:1000',
            'sim_number'          => 'unique:devices,sim_number,%s',
            //'expiration_date'     => 'required_if:enable_expiration_date,1|date',
            'installation_date'   => 'date',
            'sim_activation_date' => 'date',
            'sim_expiration_date' => 'date',
            'forward.protocol'    => 'required_if:forward.active,1|in:TCP,UDP',
            'msisdn'              => 'sometimes|regex:/^\d{6,20}$/',
            'lbs'                 => 'nullable|boolean',
            'fuel_detect_sec_after_stop' => 'nullable|numeric|min:60|max:300',
            'mtc'                 => 'nullable|boolean',
            'mininter'            => 'nullable|boolean',
            'mininter_type'       => 'nullable|in:serenazgo,policial',
        ],
    ];

    public function __construct(IlluminateValidator $validator)
    {
        $this->_validator = $validator;

        $this->rules['create']['group_id'] = 'exists_or_empty:device_groups,id,user_id,' . auth()->user()->id;
        $this->rules['update']['group_id'] = 'exists_or_empty:device_groups,id,user_id,' . auth()->user()->id;

        $maxIps = config('tobuli.limits.forward_ips');
        $this->rules['create']['forward.ip'] = "required_if:forward.active,1|semicolon_array:array_max:$maxIps|semicolon_element:host_port";
        $this->rules['update']['forward.ip'] = "required_if:forward.active,1|semicolon_array:array_max:$maxIps|semicolon_element:host_port";

        $extraRules = (new DeviceRequiredFieldsService())->getRules();

        appendRulesArray($this->rules['create'], $extraRules);
        appendRulesArray($this->rules['update'], $extraRules);
    }

}   //end of class

//EOF