<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class DeviceCameraDeleted extends Event implements ShouldBroadcast
{
    use SerializesModels;

    public $message, $user;

    public function __construct($user, $message) {
        $this->user = $user;
        $this->message = $message;
    }

    public function broadcastOn() {
        return [
            $this->user->getSocketChannel()
        ];
    }

    public function broadcastAs() {
        return 'device_camera_delete';
    }

    public function broadcastWith()
    {
        return $this->message;
    }
}
