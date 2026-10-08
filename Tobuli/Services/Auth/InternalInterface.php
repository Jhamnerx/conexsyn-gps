<?php

namespace Tobuli\Services\Auth;

interface InternalInterface
{
    public function getInputTitle(): string;

    public function getUserColumn(): string;
}