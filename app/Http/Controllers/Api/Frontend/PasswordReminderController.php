<?php

namespace App\Http\Controllers\Api\Frontend;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tobuli\Entities\EmailTemplate;
use Tobuli\Entities\PasswordResetCode;
use Tobuli\Entities\User;
use Tobuli\Exceptions\ValidationException;

class PasswordReminderController extends BaseController
{
    public function create(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|exists:users',
        ]);

        PasswordResetCode::where('email', $request->email)->delete();

        do {
            $data['code'] = mt_rand(100000, 999999);

            $validator = Validator::make($data, ['code' => 'required|unique:password_reset_codes,code']);
        } while ($validator->fails());

        $codeData = PasswordResetCode::create($data);
        $template = EmailTemplate::getTemplate('reset_password_code', $this->user);

        try {
            sendTemplateEmail($data['email'], $template, $codeData->toArray());
        } catch (\Exception $e) {
            throw new ValidationException(['code' => 'Failed to send notify mail. Check email settings.']);
        }

        return ['success' => 1];
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'code' => 'required|exists:password_reset_codes',
            'password' => 'required|secure_password', // todo: after merge change to secure_password
        ]);

        $passwordReset = PasswordResetCode::firstWhere('code', $request->code);

        if (!$passwordReset) {
            return response('', 422);
        }

        if ($passwordReset->email !== $request->email) {
            return response('', 422);
        }

        if ($passwordReset->created_at > now()->addSeconds(PasswordResetCode::LIFESPAN_SECONDS)) {
            $passwordReset->delete();

            return response('', 422);
        }

        $user = User::firstWhere('email', $passwordReset->email);
        $user->password = $request->password;
        $user->save();

        $passwordReset->delete();

        event(new PasswordReset($user));

        return ['success' => 1];
    }
}
