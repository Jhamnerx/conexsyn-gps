<?php

namespace Tobuli\Helpers\Backup;

class ErrorTranslator
{
    public function translate(\Exception $e): string
    {
        return trans('front.unexpected_error');
    }
}