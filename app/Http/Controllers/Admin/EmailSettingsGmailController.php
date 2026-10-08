<?php

namespace App\Http\Controllers\Admin;

use App\Services\Mail\GmailTransport;
use Illuminate\Http\Request;

class EmailSettingsGmailController extends BaseController
{
    public function __invoke(Request $request)
    {
        $code = $request->get('code');

        if (!$code) {
            return response('Missing code', 400);
        }

        $settings = settings('email');

        $response = GmailTransport::authenticate($settings + ['code' => $code]);

        if (isset($response['error'])) {
            return response('Failed to fetch token: ' . $response['error'], 500);
        }

        if (empty($response['refresh_token'])) {
            return response('Could not retrieve refresh token', 500);
        }

        settings('email.refresh_token', $response['refresh_token']);
        settings('email.current_auth_client_id', $settings['client_id'] . $settings['client_secret']);

        return redirect()->route('admin.email_settings.index')->withSuccess(trans('front.successfully_saved'));
    }
}
