<?php

namespace Tobuli\Helpers\SMS\Services;

use Plivo\Resources\Message\MessageCreateErrorResponse;
use Plivo\RestClient;
use Tobuli\Exceptions\ValidationException;

class SendSmsPlivo extends SendSmsManager
{
    private $senderPhone;
    private $senderId;
    private $senderToken;

    public function __construct($gateway_args)
    {
        $this->senderPhone = $gateway_args['senders_phone'];
        $this->senderId = $gateway_args['auth_id'];
        $this->senderToken = $gateway_args['auth_token'];
    }

    public function sendSingle($receiver_phone, $message_body)
    {
        $plivo_service = new RestClient($this->senderId, $this->senderToken);

        $response = $plivo_service->messages->create([
            'src' => $this->senderPhone,
            'dst' => $receiver_phone,
            'text' => $message_body,
        ]);

        if ($response instanceof MessageCreateErrorResponse)
            throw new ValidationException(['request_method' => $response->error]);

        return json_encode($response);
    }
}