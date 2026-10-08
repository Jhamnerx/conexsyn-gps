<?php

namespace Tobuli\Validation;

use Illuminate\Validation\Factory as IlluminateValidator;
use Tobuli\Entities\CommandSchedule;
use Tobuli\Services\EntityLoader\UserDevicesLoader;
use Tobuli\Validation\Rules\EntityLoaderLimit;

class SendCommandFormValidator extends Validator
{

    /**
     * @var array Validation rules for the test form, they can contain in-built Laravel rules or our custom rules
     */
    public $rules = [];

    function __construct(IlluminateValidator $validator)
    {
        parent::__construct($validator);

        $loader = new UserDevicesLoader(auth()->user());

        $id = request()->route('schedule');
        if ($id && $commandSchedule = CommandSchedule::find($id)) {
            $loader->setQueryStored($commandSchedule->devices());
        }

        $this->rules = [
            'sms' => [
                'devices' => new EntityLoaderLimit($loader, 1, config('tobuli.limits.command_sms_devices')),
                'type'    => 'required',
                'message' => 'required_if:type,custom',
                'gprs_template_id' => 'required_if:type,template'
            ],
            'gprs' => [
                'devices' => new EntityLoaderLimit($loader, 1, config('tobuli.limits.command_gprs_devices')),
                'type'    => 'required'
            ]
        ];
    }
}
