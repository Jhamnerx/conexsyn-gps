<?php

namespace Tobuli\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Tobuli\Entities\FcmToken;
use Tobuli\Entities\FcmTokenableInterface;
use Tobuli\Helpers\FcmConfigurationService;

class FcmService
{
    public const MODE_PROD = 1;
    public const MODE_TEST = 2;

    private const DURATION_PROJECT_INSERT = 3;

    /**
     * @var Client[]
     */
    private array $clients = [];
    private FcmConfigurationService $fcmConfigurationService;
    private int $mode;

    public function __construct(int $mode = self::MODE_PROD)
    {
        $this->fcmConfigurationService = new FcmConfigurationService();
        $this->mode = $mode;
    }

    public function setFcmToken(FcmTokenableInterface $tokenable, string $fcmToken, ?string $projectId = null): void
    {
        $token = $tokenable->fcmTokens()->firstOrNew(['token' => $fcmToken]);
        $token->project_id = $projectId;
        $token->save();
    }

    /**
     * @param Model&FcmTokenableInterface $tokenable
     */
    public function send($tokenable, $title, $body, array $data = [])
    {
        if (!$tokenable instanceof FcmTokenableInterface) {
            return;
        }

        $tokens = $tokenable->fcmTokens()->latest()->get()->all();

        if (!$tokens) {
            return;
        }

        $payload = array_merge($data, ['title' => $title, 'content' => $body]);

        $this->sendToTokens($tokens, $title, $body, $payload);
    }

    /**
     * @param  FcmToken[]  $tokens
     */
    public function sendToTokens(array $tokens, string $title, string $body, ?array $payloadData = null): void
    {
        $message = $this->buildMessage($title, $body, $payloadData);

        foreach ($tokens as $token) {
            $projectId = $token->project_id ?: $this->fcmConfigurationService->getDefaultProjectId();

            if ($this->fcmConfigurationService->hasProjectConfig($projectId)) {
                $this->sendDirect($token, $message, $projectId);
                continue;
            }

            // user has custom config but did not upload firebase-config.json yet
            if (config('fcm.http.sender_id')) {
                continue;
            }

            $this->sendViaBridge($token, $message, $projectId);
        }
    }

    /**
     * @throws ClientException
     */
    private function sendDirect(FcmToken $token, array $message, string $projectId, int $attempt = 1): void
    {
        $message['message']['token'] = $token->token;

        $accessToken = $this->fcmConfigurationService->getAccessToken($projectId);

        try {
            $this->getClient($projectId)->post($this->getFirebaseUrl($projectId), [
                RequestOptions::JSON => $message,
                RequestOptions::HEADERS => ['Authorization' => "Bearer $accessToken"],
            ]);
        } catch (ClientException $e) {
            if ($this->mode === self::MODE_TEST) {
                throw $e;
            }

            $data = json_decode($e->getResponse()->getBody()->getContents(), true) ?? [];

            if ($this->isFailedAuthError($data) && $attempt < 2) {
                $this->fcmConfigurationService->resetAccessToken($projectId);

                $this->sendDirect($token, $message, $projectId, ++$attempt);

                return;
            }

            $success = $this->handleSendError($token, $e->getCode(), $data);

            if (!$success) {
                throw $e;
            }
        }
    }

    private function sendViaBridge(FcmToken $token, array $message, ?string $projectId): void
    {
        $message['message']['token'] = $token->token;

        if ($projectId) {
            $message['project_id'] = $projectId;
        }

        try {
            $this->getClient($projectId)->post(config('fcm.http.bridge_url'), [
                RequestOptions::JSON => $message,
            ])->getBody()->getContents();
        } catch (ClientException $e) {
            if ($this->mode === self::MODE_TEST) {
                throw $e;
            }

            $data = json_decode($e->getResponse()->getBody()->getContents(), true) ?? [];

            $success = $this->handleSendError($token, $e->getCode(), $data);

            if (!$success) {
                throw $e;
            }
        }
    }

    private function handleSendError(FcmToken $fcmToken, int $code, $data): bool
    {
        $token = $fcmToken->token;

        if (!is_array($data)) {
            $data = (array)$data;
        }

        if ($this->isFailedAuthError($data)) {
            FcmToken::where('token', $token)->delete();

            return true;
        }

        if ($code === Response::HTTP_UNPROCESSABLE_ENTITY) {
            if (isset($data['project_id'])) {
                return \Carbon::now()->subDays(self::DURATION_PROJECT_INSERT)->gte($fcmToken->created_at) && $fcmToken->delete();
            }

            return false;
        }

        if (!isset($data['error']['details'])) {
            return false;
        }

        $msg = $data['error']['message'] ?? '';
        $data = $data['error']['details'];

        if (!is_array($data)) {
            return false;
        }

        $success = true;

        foreach ($data as $details) {
            if (!isset($details['errorCode'])) {
                $success = false;
                continue;
            }

            $code = $details['errorCode'];

            // https://firebase.google.com/docs/reference/fcm/rest/v1/ErrorCode
            switch (true) {
                case $code === 'INVALID_ARGUMENT' && $msg === 'The registration token is not a valid FCM registration token':
                case $code === 'UNREGISTERED':
//                case $code === 'SENDER_ID_MISMATCH': // valid AT, RT from other project
                    FcmToken::where('token', $token)->delete();
                    break;
                default:
                    $success = false;
            }
        }

        return $success;
    }

    private function isFailedAuthError($data): bool
    {
        $error = $data['error'] ?? null;

        if ($error === null) {
            return false;
        }

        return $error['code'] === Response::HTTP_UNAUTHORIZED
            && $error['status'] === 'UNAUTHENTICATED';
    }

    private function buildMessage(string $title, string $body, ?array $payloadData = null): array
    {
        $message = [
            'token' => null,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
        ];

        if ($payloadData) {
            $message['data'] = $payloadData;
        }

        if (isset($message['data'])) {
            array_walk($message['data'], function (&$value) {
                if (is_string($value)) {
                    return;
                }

                if (is_scalar($value)) {
                    $value = (string)$value;
                } else {
                    $value = json_encode($value);
                }
            });
        }

        $channelId = config('fcm.channel_id');
        $sound = config('fcm.sound');
        $ttl = 20 * 60; // https://firebase.google.com/docs/cloud-messaging/concept-options#ttl

        $message['android']['ttl'] = $ttl . 's';
        $message['android']['priority'] = 'high';
        $message['apns']['headers'] = ['apns-expiration' => (string)(time() + $ttl)];
        // default apns-priority is immediate: https://developer.apple.com/documentation/usernotifications/sending-notification-requests-to-apns

        $android = array_filter([
            'sound' => $sound,
            'channel_id' => $channelId,
        ]);

        if ($android) {
            $message['android']['notification'] = $android;
        }

        $apple = array_filter(['sound' => $sound]);

        if ($apple) {
            $message['apns']['payload'] = ['aps' => $apple];
        }

        return ['message' => $message];
    }

    private function getFirebaseUrl(string $projectId): string
    {
        return "v1/projects/$projectId/messages:send";
    }

    private function getClient(?string $projectId): Client
    {
        if (isset($this->clients[$projectId])) {
            return $this->clients[$projectId];
        }

        $uri = $this->fcmConfigurationService->hasProjectConfig($projectId)
            ? 'https://fcm.googleapis.com'
            : null;

        // client does not handle access token without providing base_uri
        return $this->clients[$projectId] = new Client(array_filter(['base_uri' => $uri]));
    }

    public function setMode(int $mode): self
    {
        $this->mode = $mode;

        return $this;
    }
}
