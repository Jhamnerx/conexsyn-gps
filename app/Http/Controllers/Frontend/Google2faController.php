<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use PragmaRX\Google2FALaravel\Support\Authenticator as Google2fa;

class Google2faController extends Controller
{
    public function __construct()
    {
        if (!config('google2fa.enabled')) {
            abort(404);
        }

        parent::__construct();
    }

    public function __invoke(Google2fa $google2fa)
    {
        $this->validate(request(), ['one_time_password' => 'required']);

        $success = $google2fa->isAuthenticated();

        if (!$success) {
            return redirect()->back()->with('message', trans('auth.password'));
        }

        return redirect(route('home'));
    }
}
