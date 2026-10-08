<?php

namespace Tobuli\Services;

use Illuminate\Container\RewindableGenerator;
use Illuminate\Support\Arr;
use CustomFacades\Validators\AdminUserLoginMethodsValidator;
use Tobuli\Entities\User;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Services\Auth\AuthInterface;
use Tobuli\Services\Auth\ConfigurableInterface;
use Tobuli\Services\Auth\InternalInterface;

class AuthManager
{
    /**
     * @var AuthInterface[]
     */
    private array $auths;

    public function __construct($auths)
    {
        if ($auths instanceof RewindableGenerator) {
            $this->auths = iterator_to_array($auths);
        } else {
            $this->auths = $auths;
        }
    }

    /**
     * @throws ValidationException
     */
    public function storeGeneralSettings(array $input): void
    {
        AdminUserLoginMethodsValidator::validate('update', $input);

        $input['login_methods'] = Arr::only($input['login_methods'], $this->getAuthKeys());

        settings('user_login_methods.general', $input);
    }

    /**
     * @throws ValidationException
     */
    public function storeConfig(string $authKey, array $input)
    {
        return $this->getConfigurableAuthByKey($authKey)->storeConfig($input);
    }

    public function checkConfigErrors(string $authKey, array $config): array
    {
        return $this->getConfigurableAuthByKey($authKey)->checkConfigErrors($config);
    }

    private function getConfigurableAuthByKey(string $key): ConfigurableInterface
    {
        $auth = $this->getAuthByKey($key);

        if ($auth instanceof ConfigurableInterface) {
            return $auth;
        }

        throw new \InvalidArgumentException('Auth is not configurable');
    }

    public function getAuthByKey(string $key): AuthInterface
    {
        foreach ($this->auths as $auth) {
            if ($auth->getKey() === $key) {
                return $auth;
            }
        }

        throw new \InvalidArgumentException('Unknown auth: ' . $key);
    }

    public function getAuths(): array
    {
        return $this->auths;
    }

    public function getAuthKeys(): array
    {
        $keys = [];

        foreach ($this->auths as $auth) {
            $keys[] = $auth->getKey();
        }

        return $keys;
    }

    public function getEnabledAuths(): array
    {
        if (settings('user_login_methods.general.user_individual_config')) {
            return $this->auths;
        }

        return array_filter($this->auths, fn ($auth) => self::isAuthEnabledByDefault($auth->getKey()));
    }

    /**
     * @return InternalInterface&AuthInterface[]
     */
    public function getEnabledDefaultInternalAuths(): array
    {
        return array_filter($this->getEnabledAuths(), fn ($auth) => $auth instanceof InternalInterface);
    }

    public function getEnabledDefaultExternalAuths(): array
    {
        return array_filter($this->getEnabledAuths(), fn ($auth) => !$auth instanceof InternalInterface);
    }

    public function isAuthEnabledToUser(User $user, string $authKey): bool
    {
        $loginMethods = $user->loginMethods;

        $usesDefault = $loginMethods->isEmpty();

        if ($usesDefault) {
            return self::isAuthEnabledByDefault($authKey);
        }

        return $loginMethods->where('type', $authKey)->where('enabled', 1)->count();
    }

    public static function isAuthEnabledByDefault(string $authKey): bool
    {
        $defaultMethods = self::getDefaultAuths();

        return !empty($defaultMethods[$authKey]);
    }

    public static function getDefaultAuths()
    {
        return settings('user_login_methods.general.login_methods');
    }
}