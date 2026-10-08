<?php

namespace Tobuli\Plugins\Services;

use Tobuli\Plugins\Contracts\PluginInterface;
use Tobuli\Plugins\Contracts\ValidationAware;
use Tobuli\Plugins\Contracts\ValidationTrait;

class AlertSharingPlugin implements PluginInterface, ValidationAware
{
    use ValidationTrait;

    protected array $customAttributes;
    protected array $rules = [
        'options.duration.value' => 'required_if:options.duration.active,1|integer',
    ];

    public function __construct()
    {
        $this->customAttributes = [
            'options.duration.value' => trans('front.duration'),
        ];
    }
}