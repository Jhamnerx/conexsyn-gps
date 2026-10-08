<?php

namespace App\Http\Controllers\Admin;

use GuzzleHttp\Exception\ClientException;
use Tobuli\Entities\FcmToken;
use Tobuli\Entities\User;
use Tobuli\Helpers\FcmConfigurationService;
use Tobuli\Services\FcmService;

class FcmTestController extends BaseController
{
    private FcmService $fcmService;

    public function __construct()
    {
        parent::__construct();

        $this->fcmService = new FcmService(FcmService::MODE_TEST);
    }

    public function create()
    {
        return view('Admin.FcmTest.create');
    }

    public function store()
    {
        $this->validate(request(), ['user_id' => 'required']);

        $tokens = User::findOrFail(request('user_id'))->fcmTokens;

        if ($tokens->isEmpty()) {
            return [
                'html' => view('Admin.FcmTest.partials.result')
                    ->with(['stats' => [], 'error' => trans('front.no_fcm_tokens_found')])
                    ->render()
            ];
        }

        $defaultProjectId = (new FcmConfigurationService())->getDefaultProjectId();

        foreach ($tokens as $token) {
            $error = $this->send($token);

            $projectId = $token->project_id ?: $defaultProjectId;

            if (!isset($stats[$projectId])) {
                $stats[$projectId] = [
                    'success'   => 0,
                    'failed'    => 0,
                    'errors'    => [],
                ];
            }

            if ($error) {
                $stats[$projectId]['errors'][$error] = $error;
                $stats[$projectId]['failed']++;
            } else {
                $stats[$projectId]['success']++;
            }
        }

        return ['html' => view('Admin.FcmTest.partials.result')->with(['stats' => $stats])->render()];
    }

    private function send(FcmToken $token): ?string
    {
        try {
            $this->fcmService->sendToTokens([$token], 'Test title', 'Test body');
        } catch (ClientException $e) {
            return $e->getResponse()->getBody()->getContents();
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return null;
    }
}
