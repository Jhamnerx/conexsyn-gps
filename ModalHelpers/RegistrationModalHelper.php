<?php namespace ModalHelpers;

use Bugsnag\BugsnagLaravel\Facades\Bugsnag as Bugsnag;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tobuli\Entities\EmailTemplate;
use Tobuli\Services\RegistrationFieldsService;
use Tobuli\Services\UserClientService;
use Tobuli\Services\UserService;

class RegistrationModalHelper extends ModalHelper
{
    public function create()
    {
        $userService = new UserService();

        $customFields = settings('main_settings.custom_registration_fields');

        try {
            $rules = empty($customFields['enabled'])
                ? ['email' => 'required|email|unique:users,email']
                : (new RegistrationFieldsService($customFields['fields']))->getRules();

            Validator::validate($this->data, $rules);
        } catch (ValidationException $e) {
            return [
                'status' => 0,
                'errors' => $e->errors()
            ];
        }

        $data = Arr::only($this->data, array_keys($rules)) + ['password' => $userService->generatePassword()];

        $user = $userService->registration($data);

        $clientData = isset($data['client']) ? array_filter($data['client']) : null;

        if ($clientData) {
            (new UserClientService($user))->update($clientData);
        }

        $this->sendRegistrationEmail($user, $data);

        return ['status' => 1, 'message' => trans('front.registration_successful')];
    }

    public function sendRegistrationEmail($user, $data)
    {
        $email_template = EmailTemplate::getTemplate('registration', $user);

        try {
            sendTemplateEmail($user->email, $email_template, $data);
        } catch (\Exception $e) {
            Bugsnag::notifyException($e);
        }
    }
}