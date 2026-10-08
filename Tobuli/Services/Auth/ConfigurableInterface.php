<?php

namespace Tobuli\Services\Auth;

use Tobuli\Exceptions\ValidationException;

interface ConfigurableInterface extends AuthInterface
{
    /**
     * @throws ValidationException
     */
    public function storeConfig(array $input);

    public function getConfig();

    public function renderConfigForm(): string;

    public function checkConfigErrors(array $config): array;
}