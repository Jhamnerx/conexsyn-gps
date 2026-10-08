<?php

namespace App\Http\Controllers\Frontend;

use App\Events\NoticeEvent;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Tobuli\Entities\User;

class EmailVerificationController extends Controller
{
    public function notice()
    {
        $url = session()->get('url', route('home'));

        return View::make('verification', [
            'url' => $url,
        ]);
    }

    public function verify(string $token)
    {
        try {
            list($hash, $id) = explode(';', $token, 2);
        } catch (\Exception $exception) {
            $hash = $id = null;
        }

        $user = User::find($id);

        if (!($user && hash_equals($hash, sha1($user->email)))) {
            return redirect()->route('login');
        }

        $user->markEmailAsVerified();

        $message = trans('global.verified_email', ['email' => $user->email]);

        if (config('verification.autologin')) {
            Auth::loginUsingId($user->id);
        }

        $url = config('verification.redirect');

        if (!$url) {
            return view('verification_success')->with(compact('message', 'url'));
        }

        event(new NoticeEvent($user, NoticeEvent::TYPE_SUCCESS, $message));

        return redirect()->to($url)->with('success', $message);
    }
}