<?php

namespace App\Livewire;

use App\Events\MasonChatMessageCreated;
use App\Models\MasonChatMessage;
use Livewire\Attributes\On;
use Livewire\Component;

class MasonChatPanel extends Component
{
    public string $message = '';

    #[On('mason-chat-refresh')]
    public function refreshFromBroadcast(): void
    {
        // Event target for websocket-triggered re-render.
    }

    public function sendMessage(): void
    {
        $this->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $createdMessage = MasonChatMessage::query()->create([
            'sender' => 'human',
            'status' => 'pending',
            'body' => trim($this->message),
        ]);
        event(new MasonChatMessageCreated([
            'id' => $createdMessage->id,
            'sender' => $createdMessage->sender,
            'status' => $createdMessage->status,
            'body' => $createdMessage->body,
            'in_reply_to_id' => $createdMessage->in_reply_to_id,
            'related_story_id' => $createdMessage->related_story_id,
            'metadata' => $createdMessage->metadata ?? [],
            'answered_at' => $createdMessage->answered_at?->toIso8601String(),
            'created_at' => $createdMessage->created_at?->toIso8601String(),
        ]));

        $this->message = '';
    }

    public function render()
    {
        $messages = MasonChatMessage::query()
            ->orderByDesc('id')
            ->limit(120)
            ->get()
            ->reverse()
            ->values();

        return view('livewire.mason-chat-panel', [
            'messages' => $messages,
        ]);
    }
}
