<div wire:poll.10s class="space-y-4"
     data-reverb-key="{{ env('REVERB_APP_KEY', 'elasticgun-reverb-key') }}"
     data-reverb-path="{{ env('REVERB_WS_PATH', '/app') }}">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Mason Chat</h2>
        <p class="text-sm text-gray-600 dark:text-gray-300">
            Ask what Mason is doing now, blockers, or handoff details. Mason replies in this thread.
        </p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 h-[520px] overflow-y-auto space-y-3">
        @forelse ($messages as $msg)
            @php
                $isHuman = $msg->sender === 'human';
                $isMason = $msg->sender === 'mason';
                $cardClass = $isHuman
                    ? 'bg-blue-50 border-blue-200 text-blue-900 ml-10'
                    : ($isMason ? 'bg-green-50 border-green-200 text-green-900 mr-10' : 'bg-gray-50 border-gray-200 text-gray-900');
            @endphp
            <article class="rounded-md border p-3 {{ $cardClass }}">
                <div class="flex items-center justify-between gap-3 mb-1">
                    <span class="text-xs font-semibold uppercase tracking-wide">{{ $msg->sender }}</span>
                    <span class="text-xs opacity-75">{{ $msg->created_at?->diffForHumans() }}</span>
                </div>
                <p class="text-sm whitespace-pre-wrap">{{ $msg->body }}</p>
                <div class="mt-2 text-xs opacity-80">
                    Status: {{ $msg->status }}
                    @if ($msg->related_story_id)
                        • Story #{{ $msg->related_story_id }}
                    @endif
                </div>
            </article>
        @empty
            <p class="text-sm text-gray-600 dark:text-gray-300">No chat messages yet.</p>
        @endforelse
    </div>

    <form wire:submit.prevent="sendMessage" class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 space-y-3">
        <label for="chat_message" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Message Mason</label>
        <textarea id="chat_message"
                  wire:model.defer="message"
                  rows="3"
                  placeholder="What are you doing right now? Are you blocked?"
                  class="w-full rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-gray-100"></textarea>
        @error('message')
            <p class="text-xs text-red-600">{{ $message }}</p>
        @enderror
        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-semibold text-white bg-green-600 hover:bg-green-700">
            Send
        </button>
    </form>

    <script>
        (() => {
            const root = document.currentScript.closest('[data-reverb-key]');
            if (!root || typeof window.Pusher === 'undefined' || typeof window.Livewire === 'undefined') {
                return;
            }

            if (window.__masonChatSubscribed) {
                return;
            }
            window.__masonChatSubscribed = true;

            const key = root.dataset.reverbKey;
            const wsPath = root.dataset.reverbPath || '/app';
            const isSecure = window.location.protocol === 'https:';

            const pusher = new window.Pusher(key, {
                wsHost: window.location.hostname,
                wsPort: isSecure ? 443 : 80,
                wssPort: isSecure ? 443 : 80,
                wsPath: wsPath,
                forceTLS: isSecure,
                enabledTransports: ['ws', 'wss'],
                cluster: 'mt1',
            });

            const channel = pusher.subscribe('mason-chat');
            channel.bind('mason.chat.message.created', () => {
                window.Livewire.dispatch('mason-chat-refresh');
            });
        })();
    </script>
</div>
