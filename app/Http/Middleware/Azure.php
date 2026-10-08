<?php

namespace App\Http\Middleware;

use Closure;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Tobuli\Services\Auth\AzureAuth;

class Azure
{
    private $azureAuth;

    public function __construct(AzureAuth $azureAuth)
    {
        $this->azureAuth = $azureAuth;
    }

    public function handle(Request $request, Closure $next)
    {
        $accessToken = $request->session()->get('_azure_access_token');
        $refreshToken = $request->session()->get('_azure_refresh_token');

        if (!$accessToken || !$refreshToken) {
            return;
        }

        if (!$this->isExpired($request)) {
            return;
        }

        try {
            $contents = $this->azureAuth->getClient()->v1RefreshToken($refreshToken);
        } catch(RequestException $e) {
            $this->fail($request, $e);
        }

        if (empty($contents->access_token) || empty($contents->refresh_token)) {
            $this->fail($request, new \Exception('Missing tokens in response contents'));
        }

        $request->session()->put('_azure_access_token', $contents->access_token);
        $request->session()->put('_azure_refresh_token', $contents->refresh_token);
        $request->session()->put('_azure_expires_at', time() + $contents->expires_in);
    }

    private function isExpired(Request $request): bool
    {
        $expiresAt = $request->session()->get('_azure_expires_at');

        return $expiresAt && $expiresAt < time();
    }

    private function fail(Request $request, \Exception $e)
    {
        $error = $this->azureAuth->getFailedAuthError($request);

        if (is_array($error)) {
            abort($error['http_code'], $error['msg']);
        }

        abort(400, str_replace(PHP_EOL, ' ', $e->getMessage()));
    }
}