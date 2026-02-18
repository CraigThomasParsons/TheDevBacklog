@extends('layouts.app')

@section('title', 'Current Sprint')

@section('header')
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Current Sprint Board</h1>
            @if ($currentSprint)
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    {{ $currentSprint->title }}
                    <span class="mx-2">•</span>
                    {{ $currentSprint->stories->count() }} stories
                    <span class="mx-2">•</span>
                    {{ $currentSprint->total_points }} pts
                </p>
            @else
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">No sprint is currently available.</p>
            @endif
        </div>

        @if ($currentSprint)
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('mason.state.start') }}">
                    @csrf
                    <button type="submit"
                            title="Start Mason sprint execution loop. Mason will take one story at a time until the sprint is done."
                            @if ($masonRunControl->is_running) disabled @endif
                            class="inline-flex items-center px-4 py-2 rounded-md text-sm font-semibold text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Start Sprint
                    </button>
                </form>
                @if ($masonRunControl->is_running)
                    <form method="POST" action="{{ route('mason.state.stop') }}">
                        @csrf
                        <button type="submit"
                                title="Stop Mason sprint execution loop after current control cycle."
                                class="inline-flex items-center px-4 py-2 rounded-md text-sm font-semibold text-white bg-red-600 hover:bg-red-700">
                            Stop Sprint
                        </button>
                    </form>
                @endif
                <a href="{{ route('mason.state') }}"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    Mason State
                </a>
                <a href="{{ route('sprints.show', $currentSprint) }}"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    View Sprint Details
                </a>
            </div>
        @endif
    </div>
@endsection

@section('content')
    @if (! $currentSprint)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
            <p class="text-gray-600 dark:text-gray-300">Create or activate a sprint to use the current sprint board.</p>
            <a href="{{ route('sprints.index') }}" class="mt-4 inline-flex items-center text-sm text-green-600 hover:text-green-800 dark:text-green-400">
                Go to Sprints →
            </a>
        </div>
    @else
        <div class="mb-4 rounded-md border px-4 py-3 text-sm
            {{ $masonRunControl->is_running ? 'border-green-300 bg-green-50 text-green-900' : 'border-yellow-300 bg-yellow-50 text-yellow-900' }}">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <span class="font-semibold">Sprint Status:</span>
                    {{ $masonRunControl->is_running ? 'Running' : 'Stopped / Waiting' }}
                    <span class="mx-2">•</span>
                    <span class="font-semibold">Heartbeat:</span>
                    {{ $masonHeartbeatFresh ? 'Fresh' : 'Stale' }}
                    @if ($masonRunControl->last_heartbeat_at)
                        <span class="mx-2">•</span>
                        Last: {{ $masonRunControl->last_heartbeat_at->toDateTimeString() }} UTC
                    @endif
                </div>
                <div class="text-xs opacity-80">
                    {{ $masonRunControl->last_status_message ?? 'No status message yet.' }}
                </div>
            </div>
        </div>

        @php
            $columns = [
                ['key' => 'todo', 'label' => 'To Do', 'stories' => $toDoStories, 'header' => 'bg-gray-100 text-gray-800'],
                ['key' => 'in_progress', 'label' => 'In Progress', 'stories' => $inProgressStories, 'header' => 'bg-yellow-100 text-yellow-800'],
                ['key' => 'in_review', 'label' => 'In Review', 'stories' => $inReviewStories, 'header' => 'bg-blue-100 text-blue-800'],
                ['key' => 'done', 'label' => 'Done', 'stories' => $doneStories, 'header' => 'bg-green-100 text-green-800'],
            ];
        @endphp

        <div id="board-save-message" class="mb-4 hidden rounded-md border border-green-300 bg-green-50 px-4 py-2 text-sm text-green-800">
            Board changes saved.
        </div>
        <div id="board-error-message" class="mb-4 hidden rounded-md border border-red-300 bg-red-50 px-4 py-2 text-sm text-red-800">
            Unable to save board changes. Please retry.
        </div>
        @if ($currentSprint->is_frozen)
            <div class="mb-4 rounded-md border border-blue-300 bg-blue-50 px-4 py-2 text-sm text-blue-800">
                Sprint is frozen. Drag-and-drop is disabled.
            </div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
            @foreach ($columns as $column)
                <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 flex flex-col min-h-[320px]">
                    <header class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between {{ $column['header'] }} rounded-t-lg">
                        <h2 class="font-semibold text-sm uppercase tracking-wide">{{ $column['label'] }}</h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-white/70 text-gray-800">
                            {{ $column['stories']->count() }}
                        </span>
                    </header>

                    <div class="p-4 space-y-3 flex-1 board-column" data-column-key="{{ $column['key'] }}">
                        @forelse ($column['stories'] as $story)
                            <article class="rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-3 hover:border-green-400 transition cursor-move board-card"
                                     data-story-id="{{ $story->id }}">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 leading-5">
                                        {{ $story->title }}
                                    </h3>
                                    @if ($story->est_points)
                                        <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                            {{ $story->est_points }} pts
                                        </span>
                                    @endif
                                </div>

                                @if ($story->narrative)
                                    <p class="mt-2 text-xs text-gray-600 dark:text-gray-300 line-clamp-3">
                                        {{ \Illuminate\Support\Str::limit($story->narrative, 140) }}
                                    </p>
                                @endif

                                <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <div class="flex items-center gap-2">
                                        @if ($story->persona)
                                            <span>👤 {{ $story->persona->name }}</span>
                                        @endif
                                        @if ($story->epic)
                                            <span>📁 {{ \Illuminate\Support\Str::limit($story->epic->title, 24) }}</span>
                                        @endif
                                    </div>
                                    <span>{{ $story->status?->name ?? 'Unknown' }}</span>
                                </div>

                                <div class="mt-3">
                                    <a href="{{ route('stories.show', $story) }}" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex items-center text-xs font-medium text-green-600 hover:text-green-800 dark:text-green-400">
                                        Open ticket ↗
                                    </a>
                                </div>
                            </article>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No stories in this column.</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>

        <script>
            (() => {
                const columns = Array.from(document.querySelectorAll('.board-column'));
                const saveMessage = document.getElementById('board-save-message');
                const errorMessage = document.getElementById('board-error-message');
                const saveUrl = @json(route('sprints.board.update', $currentSprint));
                const csrfToken = @json(csrf_token());
                const isFrozen = @json((bool) $currentSprint->is_frozen);
                let hideMessageTimeoutId = null;
                let hideErrorTimeoutId = null;

                const collectBoardPayload = () => {
                    const payload = {
                        columns: {
                            todo: [],
                            in_progress: [],
                            in_review: [],
                            done: [],
                        }
                    };

                    columns.forEach((columnElement) => {
                        const columnKey = columnElement.dataset.columnKey;
                        const cardElements = columnElement.querySelectorAll('.board-card');
                        payload.columns[columnKey] = Array.from(cardElements).map((cardElement) =>
                            Number(cardElement.dataset.storyId)
                        );
                    });

                    return payload;
                };

                const showSavedMessage = () => {
                    if (!saveMessage) {
                        return;
                    }

                    if (errorMessage) {
                        errorMessage.classList.add('hidden');
                    }

                    saveMessage.classList.remove('hidden');
                    if (hideMessageTimeoutId) {
                        clearTimeout(hideMessageTimeoutId);
                    }

                    hideMessageTimeoutId = setTimeout(() => {
                        saveMessage.classList.add('hidden');
                    }, 1500);
                };

                const showErrorMessage = (message) => {
                    if (!errorMessage) {
                        return;
                    }

                    if (saveMessage) {
                        saveMessage.classList.add('hidden');
                    }

                    errorMessage.textContent = message || 'Unable to save board changes. Please retry.';
                    errorMessage.classList.remove('hidden');

                    if (hideErrorTimeoutId) {
                        clearTimeout(hideErrorTimeoutId);
                    }

                    hideErrorTimeoutId = setTimeout(() => {
                        errorMessage.classList.add('hidden');
                    }, 3500);
                };

                const persistBoard = async () => {
                    if (isFrozen) {
                        return;
                    }

                    const payload = collectBoardPayload();

                    const response = await fetch(saveUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(payload),
                    });

                    if (!response.ok) {
                        let errorMessage = 'Unable to save board changes. Please retry.';
                        try {
                            const data = await response.json();
                            if (data?.error) {
                                errorMessage = data.error;
                            }
                        } catch (jsonError) {
                            // Keep default message when body is not JSON.
                        }
                        throw new Error(errorMessage);
                    }

                    showSavedMessage();
                };

                columns.forEach((columnElement) => {
                    new Sortable(columnElement, {
                        group: 'current-sprint-board',
                        animation: 150,
                        disabled: isFrozen,
                        ghostClass: 'sortable-ghost',
                        onEnd: async () => {
                            try {
                                await persistBoard();
                            } catch (error) {
                                showErrorMessage(error?.message);
                            }
                        },
                    });
                });
            })();
        </script>
    @endif
@endsection
