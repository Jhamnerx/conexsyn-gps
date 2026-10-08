<?php

namespace App\Extensions;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Contracts\Support\Arrayable;
use Tobuli\Entities\SecondaryCredentialsInterface;
use Tobuli\Entities\User;
use Tobuli\Entities\UserSecondaryCredentials;

class MultiCredentialUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials)
    {
        $entity = parent::retrieveByCredentials($credentials);

        if ($entity) {
            return $entity;
        }

        if ($this->model !== User::class) {
            return $entity;
        }

        $query = UserSecondaryCredentials::query();

        if (isset($credentials['email'])) {
            $value = $credentials['email'];

            if (is_array($value) || $value instanceof Arrayable) {
                $query->whereIn('email', $value);
            } else {
                $query->where('email', $value);
            }
        } else {
            $query->whereRaw('1 = 0');
        }

        /** @var UserSecondaryCredentials $secondaryCredentials */
        $secondaryCredentials = $query->first();

        if ($secondaryCredentials === null) {
            return null;
        }

        return $secondaryCredentials->user
            ->setLoginSecondaryCredentials($secondaryCredentials);
    }

    public function validateCredentials(UserContract $user, array $credentials)
    {
        if (!isset($credentials['password'])) {
            $credentials['password'] = '';
        }

        if ($user instanceof SecondaryCredentialsInterface && $secondaryCred = $user->getLoginSecondaryCredentials()) {
            return $this->hasher->check($credentials['password'], $secondaryCred->password);
        }

        return parent::validateCredentials($user, $credentials);
    }
}