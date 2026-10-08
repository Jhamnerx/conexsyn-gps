<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use PragmaRX\Google2FALaravel\Support\Authenticator as Google2fa;

class Google2faSetupController extends Controller
{
    public function __construct()
    {
        if (!config('google2fa.enabled')) {
            abort(404);
        }

        parent::__construct();
    }

    public function create()
    {
        // in case it's after 2FA reset
        (new Google2fa(request()))->logout();

        return view('Frontend.Google2fa.create');
    }

    public function store(Google2fa $google2fa)
    {
        if (request('enable')) {
            session()->put('google2fa_secret', $google2fa->generateSecretKey());

            return redirect(route('google_2fa_setup.create_confirm'));
        }

        $this->user->google2fa_secret = '';
        $this->user->save();

        return redirect(route('home'));
    }

    public function createConfirm()
    {
        $secret = $this->validateUnconfirmedSecret();

        return view('Frontend.Google2fa.create_confirm')->with('qrSecret', $secret);
    }

    public function storeConfirm(Google2fa $google2fa)
    {
        $secret = $this->validateUnconfirmedSecret();

        beginTransaction();

        $this->user->google2fa_secret = $secret;
        $this->user->save();

        $success = $google2fa->isAuthenticated();

        if (!$success) {
            rollbackTransaction();

            return redirect()->back()->with('message', trans('auth.password'));
        }

        commitTransaction();

        session()->forget(['google2fa_secret']);

        return redirect(route('home'));
    }

    private function validateUnconfirmedSecret(): string
    {
        $validated = Validator::make(
            session()->only(['google2fa_secret']),
            ['google2fa_secret' => 'required'],
        )->validate();

        return $validated['google2fa_secret'];
    }
}
