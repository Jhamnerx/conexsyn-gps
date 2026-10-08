<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;


class PaymentsUnavailableException extends HttpException
{
    public function __construct(string $message = null)
    {
        parent::__construct(404, $message ?: trans('front.payments_service_unavailable'));
    }
}