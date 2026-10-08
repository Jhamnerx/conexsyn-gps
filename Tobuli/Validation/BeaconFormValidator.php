<?php namespace Tobuli\Validation;

use Illuminate\Validation\Factory as IlluminateValidator;

class BeaconFormValidator extends Validator
{

    /**
     * @var array Validation rules for the test form, they can contain in-built Laravel rules or our custom rules
     */
    public $rules = [
        'create' => [
            'imei'                => 'required|imei|unique:devices,imei',
            'name'                => 'required|string|between:1,255',
            'icon_id'             => 'required|exists:device_icons,id',
            'tail_length'         => 'required|numeric|min:0|max:10',
            'group_id'            => 'exists:device_groups,id',
        ],
        'update' => [
            'imei'                => 'sometimes|required|imei|unique:devices,imei,%s',
            'name'                => 'sometimes|required|string|between:1,255',
            'icon_id'             => 'exists:device_icons,id',
            'tail_length'         => 'numeric|min:0|max:10',
            'group_id'            => 'exists:device_groups,id',
        ],
    ];

    public function __construct(IlluminateValidator $validator)
    {
        parent::__construct($validator);

        $this->rules['create']['group_id'] = 'nullable|exists:device_groups,id,user_id,' . auth()->user()->id;
        $this->rules['update']['group_id'] = 'nullable|exists:device_groups,id,user_id,' . auth()->user()->id;
    }

}