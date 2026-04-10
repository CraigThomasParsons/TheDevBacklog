<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MasonChatMessageCreated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public array $message)
    {
    }

    public function broadcastOn(): array
    {
        return [new Channel('mason-chat')];
    }

    public function broadcastAs(): string
    {
        return 'mason.chat.message.created';
    }
}
