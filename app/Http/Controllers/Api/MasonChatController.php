<?php

namespace App\Http\Controllers\Api;

use App\Events\MasonChatMessageCreated;
use App\Http\Controllers\Controller;
use App\Models\MasonChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasonChatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->integer('limit', 100), 1), 500);

        $messages = MasonChatMessage::query()
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (MasonChatMessage $message) => $this->format($message));

        return response()->json([
            'success' => true,
            'count' => $messages->count(),
            'messages' => $messages,
        ]);
    }

    public function inbox(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->integer('limit', 20), 1), 100);

        $messages = MasonChatMessage::query()
            ->where('sender', 'human')
            ->where('status', 'pending')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(fn (MasonChatMessage $message) => $this->format($message))
            ->values();

        return response()->json([
            'success' => true,
            'count' => $messages->count(),
            'messages' => $messages,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sender' => 'nullable|in:human,mason,system',
            'body' => 'required|string|max:5000',
            'in_reply_to_id' => 'nullable|integer|exists:mason_chat_messages,id',
            'related_story_id' => 'nullable|integer|exists:stories,id',
            'metadata' => 'nullable|array',
        ]);

        $sender = $validated['sender'] ?? 'human';
        $status = $sender === 'human' ? 'pending' : 'answered';

        $message = MasonChatMessage::query()->create([
            'sender' => $sender,
            'status' => $status,
            'body' => $validated['body'],
            'in_reply_to_id' => $validated['in_reply_to_id'] ?? null,
            'related_story_id' => $validated['related_story_id'] ?? null,
            'metadata' => $validated['metadata'] ?? [],
            'answered_at' => $status === 'answered' ? now() : null,
        ]);

        if (! empty($validated['in_reply_to_id'])) {
            MasonChatMessage::query()
                ->where('id', $validated['in_reply_to_id'])
                ->update([
                    'status' => 'answered',
                    'answered_at' => now(),
                ]);
        }

        event(new MasonChatMessageCreated($this->format($message)));

        return response()->json([
            'success' => true,
            'message' => $this->format($message),
        ], 201);
    }

    private function format(MasonChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'sender' => $message->sender,
            'status' => $message->status,
            'body' => $message->body,
            'in_reply_to_id' => $message->in_reply_to_id,
            'related_story_id' => $message->related_story_id,
            'metadata' => $message->metadata ?? [],
            'answered_at' => $message->answered_at?->toIso8601String(),
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }
}
