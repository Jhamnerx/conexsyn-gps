<?php

namespace Tobuli\Helpers;

use Illuminate\Support\Facades\Cache;

class GoogleAuthClient
{
    public function getOAuth2Token(string $endpoint, array $config, string $cacheKey): string
    {
        if ($token = Cache::get($cacheKey)) {
            return $token;
        }

        $response = $this->getAccessToken(
            $this->jwtToken($endpoint, $config)
        );

        Cache::put($cacheKey, $response->access_token, $response->expires_in - 5);

        return $response->access_token;
    }

    public function removeToken(string $cacheKey): bool
    {
        return \Cache::forget($cacheKey);
    }
    
    private function getAccessToken($jwt)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer&assertion=' . $jwt);

        $response = curl_exec($ch);
        $response = json_decode($response);

        if (empty($response->access_token)) {
            throw new \RuntimeException('Could not get access token');
        }

        return $response;
    }

    private function base64UrlEncode($text): string
    {
        return str_replace(
            ['+', '/', '='],
            ['-', '_', ''],
            base64_encode($text)
        );
    }

    private function jwtToken(string $endpoint, array $config)
    {
        $secret = openssl_get_privatekey($config['private_key']);

        $header = json_encode([
            'typ' => 'JWT',
            'alg' => 'RS256'
        ]);

        $start = time() - 60;
        $end = $start + 3600;

        $payload = json_encode([
            'iss' => $config['client_email'],
            'scope' => "https://www.googleapis.com/auth/$endpoint",
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $end,
            'iat' => $start
        ]);

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode($payload);

        openssl_sign($base64UrlHeader . '.' . $base64UrlPayload, $signature, $secret, OPENSSL_ALGO_SHA256);

        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
    }
}