<?php


namespace App\Http\Controllers\Api\ClientLite\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Auth\PasswordBroker;
use Tobuli\Exceptions\ValidationException;

class ForgotPasswordController extends Controller
{
    public function store(Request $request, PasswordBroker $passwordBroker)
    {
        $validator = Validator::make(request()->all(), [
            'email' => 'required|email',
        ]);

        if ($validator && $validator->fails())
            throw new ValidationException($validator->errors());

        $response = $passwordBroker->sendResetLink($request->only('email'));

        if ($response !== PasswordBroker::RESET_LINK_SENT) {
            return Response::json([
                'status' => 0,
                'message' => trans($response),
            ], 422);
        }

        return Response::json([
            'status' => 1,
        ]);
    }
}