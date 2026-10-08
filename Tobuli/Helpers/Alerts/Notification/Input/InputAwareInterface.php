<?php

namespace Tobuli\Helpers\Alerts\Notification\Input;

use Tobuli\Entities\Alert;

interface InputAwareInterface
{
    public function getInput(Alert $alert): InputMeta;
}