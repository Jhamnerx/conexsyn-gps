<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Tobuli\Entities\User;
use Tobuli\Services\Auth\AzureAuth;
use Tobuli\Services\AuthManager;

class AzureController extends Controller
{
    private $azureAuth;
    private $loginService;

    public function __construct(AzureAuth $azureAuth, AuthManager $loginService)
    {
        parent::__construct();

        $this->azureAuth = $azureAuth;
        $this->loginService = $loginService;
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|mixed
     */
    public function login(Request $request)
    {
        return Redirect::away($this->azureAuth->getAuthUrl());
    }

    public function loginCallback(Request $request)
    {
        $code = $request->input('code');

        try {
            $contents = $this->azureAuth->getClient()->v1GetAccessTokenWithAuthCode($code);
        } catch (RequestException $e) {
            $this->fail($request, $e);
        }

        $accessToken = $contents->access_token;
        $refreshToken = $contents->refresh_token;
        $profile = json_decode(base64_decode(explode('.', $contents->id_token)[1]));

        $request->session()->put('_azure_access_token', $accessToken);
        $request->session()->put('_azure_refresh_token', $refreshToken);
        $request->session()->put('_azure_expires_at', time() + $contents->expires_in);

        return $this->success($request, $profile);
    }

    private function fail(Request $request, \Exception $e)
    {
        $error = $this->azureAuth->getFailedAuthError($request);

        if (is_array($error)) {
            abort($error['http_code'], $error['msg']);
        }

        abort(400, str_replace(PHP_EOL, ' ', $e->getMessage()));
    }

    private function success(Request $request, $profile)
    {
        $user = User::where('email', $profile->upn)->first(); // user principal name

        if (!$user) {
            return $this->callbackFailure($request, new AuthorizationException(trans('front.user_not_found'), 404));
        }

        if (!$this->loginService->isAuthEnabledToUser($user, AzureAuth::getKey())) {
            return $this->callbackFailure($request, new AuthorizationException(trans('front.login_method_unavailable'), 401));
        }

        \Auth::login($user, true);

        return Redirect::intended('/');
    }

    /**
     * @throws AuthorizationException
     */
    private function callbackFailure(Request $request, AuthorizationException $exception): RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            throw $exception;
        }

        return Redirect::route('login')->with('message', $exception->getMessage());
    }

    public function logout(Request $request)
    {
        $this->azureAuth->prepareLogout($request->user());

        return Redirect::away($this->azureAuth->getLogoutUrl());
    }
}