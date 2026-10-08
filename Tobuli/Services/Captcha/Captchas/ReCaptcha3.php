<?php

namespace Tobuli\Services\Captcha\Captchas;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Validator;

class ReCaptcha3 implements CaptchaInterface
{
    private const MIN_SCORE = .5;

    /**
     * Returns html code with captcha form
     *
     * @return string
     */
    public function render()
    {
        return view('Frontend.Captcha.Captchas.recaptcha3');
    }

    /**
     * Checks validity of captcha code
     *
     * @return bool
     */
    public function isValid()
    {
        return ! $this->getValidator()
            ->fails();
    }

    /**
     * Get array of validation messages
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->getValidator()
            ->messages()
            ->all();
    }

    /**
     * Get request validator to check CAPTCHA's validity
     *
     * @return \Illuminate\Support\Facades\Validator
     */
    public function getValidator()
    {
        return Validator::make(request()->all(), [
                'g-recaptcha-response' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        if (! $this->verify()) {
                            $fail(trans('validation.wrong_captcha'));
                        }
                    },
                ]
            ]);
    }

    /**
     * Verify recaptcha using google api
     *
     * @return boolean
     */
    private function verify()
    {
        $client = new Client();

        $response = $client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
            'form_params' => [
                'secret'   => settings('main_settings.recaptcha_secret_key'),
                'response' => request()->input('g-recaptcha-response'),
            ],
        ]);

        $body = json_decode($response->getBody());

        if (empty($body->success)) {
            return false;
        }

        if ($body->score < self::MIN_SCORE) {
            return false;
        }

        return true;
    }
}
