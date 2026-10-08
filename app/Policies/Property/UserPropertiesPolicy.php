<?php

namespace App\Policies\Property;

use Illuminate\Database\Eloquent\Model;
use Tobuli\Entities\User;

class UserPropertiesPolicy extends PropertyPolicy
{
    protected $entity = 'user';

    protected $viewable = [
        'login_token',
        'client_id',
        'only_one_session',
        'is_municipalidad',
        'ubigeo_muni',
        'token_muni',
        'codigo_comisaria',
    ];

    protected $editable = [
        'billing_plan_id',
        'login_token',
        'devices_limit',
        'subscription_expiration',
        'expiration_date',
        'group_id',
        'role_id',
        'manager_id',
        'client_id',
        'forwards',
        'login_periods',
        'password',
        'only_one_session',
        'is_municipalidad',
        'ubigeo_muni',
        'token_muni',
        'codigo_comisaria',
    ];

    protected $selfNotEditable = [
        'active',
        'billing_plan_id',
        'devices_limit',
        'subscription_expiration',
        'expiration_date',
        'group_id',
        'role_id',
        'manager_id',
        'login_periods',
    ];

    protected function managerIdEditPolicy(User $user, Model $model)
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    protected function forwardsEditPolicy(User $user, Model $model)
    {
        return config('addon.forwards') && ($user->isAdmin() || $user->isSupervisor());
    }

    protected function passwordEditPolicy(User $user, Model $model)
    {
        return $user->isMainLogin() || $user->id !== $model->id;
    }

    protected function loginPeriodsEditPolicy()
    {
        return settings('login_periods.enabled');
    }

    protected function onlyOneSessionViewPolicy()
    {
        return config('addon.one_session_per_user');
    }

    protected function onlyOneSessionEditPolicy()
    {
        return config('addon.one_session_per_user');
    }

    // Datos de retransmisión MININTER (municipalidad / comisaría): solo administradores.

    protected function isMunicipalidadViewPolicy(User $user)
    {
        return $user->isAdmin();
    }

    protected function isMunicipalidadEditPolicy(User $user)
    {
        return $user->isAdmin();
    }

    protected function ubigeoMuniViewPolicy(User $user)
    {
        return $user->isAdmin();
    }

    protected function ubigeoMuniEditPolicy(User $user)
    {
        return $user->isAdmin();
    }

    protected function tokenMuniViewPolicy(User $user)
    {
        return $user->isAdmin();
    }

    protected function tokenMuniEditPolicy(User $user)
    {
        return $user->isAdmin();
    }

    protected function codigoComisariaViewPolicy(User $user)
    {
        return $user->isAdmin();
    }

    protected function codigoComisariaEditPolicy(User $user)
    {
        return $user->isAdmin();
    }

    protected function _edit(User $user, Model $model, $property)
    {
        if ($this->canSelfEdit($user, $model, $property) === false)
            return false;

        return true;
    }

    private function canSelfEdit(User $user, Model $model, $property): bool
    {
        return !($model->id === $user->id && in_array($property, $this->selfNotEditable));
    }
}