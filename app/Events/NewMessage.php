<?php

namespace App\Events;

use App\Events\Event;
use App\Transformers\ChatMessageTransformer;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use FractalTransformer;

class NewMessage extends Event implements ShouldBroadcast
{
    use SerializesModels;

    public $message, $user;

    public function __construct($message) {
        $this->message = $message;
    }

    public function broadcastOn() {
        $channels = [];
        $channels[] = $this->message->chat->room_hash;

        foreach ($this->message->chat->participants as $participant)
        {
            if ( ! $participant->chattable)
                continue;

            if ($participant->isUser() && $participant->chattable->perm('chat', 'view')) {
                $channels[] = $participant->chattable->getSocketChannel();;
            }
            
            if ($participant->isDevice()) {
                $channels[] = $participant->chattable->getSocketChannel();
            }
        }

        return $channels;
    }

    public function broadcastAs() {
        return 'message';
    }

    public function broadcastWith()
    {
        return FractalTransformer::item($this->message, ChatMessageTransformer::class)->toArray();
    }
}
