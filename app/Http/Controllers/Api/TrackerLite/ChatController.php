<?php

namespace App\Http\Controllers\Api\TrackerLite;

use App\Transformers\TrackerLite\ChatMessageTransformer;
use App\Transformers\TrackerLite\ChatTransformer;
use Tobuli\Entities\Chat;
use Tobuli\Services\ChatService;
use FractalTransformer;

class ChatController extends \App\Http\Controllers\Api\Tracker\ChatController
{

    public function initChat()
    {
        $chat = Chat::getRoomByDevice($this->deviceInstance);

        $this->chatService->markAsRead($chat, $this->deviceInstance);

        return response()->json(array_merge(
            ['status' => 1],
            FractalTransformer::item($chat, ChatTransformer::class)->toArray()
        ));
    }
    
}