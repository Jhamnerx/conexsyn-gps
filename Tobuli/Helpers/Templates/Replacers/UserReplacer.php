<?php

namespace Tobuli\Helpers\Templates\Replacers;

use Formatter;
use Tobuli\Entities\User;

class UserReplacer extends Replacer
{
    /**
     * @param User $user
     * @return array
     */
    public function replacers($user)
    {
        $list = [
            'email',
            'phone_number',
            'expiration_date'
        ];

        return $this->formatFields($user, $list);
    }

    /**
     * @return array
     */
    public function placeholders()
    {
        return [
            $this->formatKey('email') => 'User email',
            $this->formatKey('phone_number') => 'User phone number',
            $this->formatKey('expiration_date') => 'User expiration date',
        ];
    }

    protected function expirationDateField($user)
    {
        return Formatter::time()->human($user->expiration_date);
    }
}