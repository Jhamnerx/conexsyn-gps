<?php

namespace App\Http\Controllers\Admin;

use Tobuli\Entities\User;
use Tobuli\Services\AuthManager;
use Tobuli\Services\CustomValuesService;

class ClientsLoginMethodsController extends BaseController
{
    function __construct() {
        parent::__construct();

        $this->customValueService = new CustomValuesService();
    }

    public function index($userId)
    {
        $user = $userId ? User::find($userId) : null;

        $loginMethods = AuthManager::getDefaultAuths();
        $loginMethodsChoices = $user ? $user->loginMethods()->pluck('enabled', 'type')->all() : [];
        $defaultLoginMethod = empty($loginMethodsChoices);

        return view('admin::Clients.partials.login_methods')->with(compact(
            'loginMethods',
            'loginMethodsChoices',
            'defaultLoginMethod'
        ));
    }
}
