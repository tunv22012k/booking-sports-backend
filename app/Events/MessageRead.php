<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chat_id;
    public $read_at;
    public $user_id; // The user who read the message

    /**
     * Create a new event instance.
     */
    public $target_user_channel_id; // Added property

    public function __construct($chat_id, $read_at, $user_id, $target_user_channel_id)
    {
        $this->chat_id = $chat_id;
        $this->read_at = $read_at;
        $this->user_id = $user_id;
        $this->target_user_channel_id = $target_user_channel_id;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->target_user_channel_id),
        ];
    }

    public function broadcastAs()
    {
        return 'MessageRead';
    }
}
