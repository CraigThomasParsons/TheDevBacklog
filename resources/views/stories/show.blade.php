@extends('layouts.app')

@section('title', $story->title)

@section('header')
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $story->title }}</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                Ticket details
                <span class="mx-2">•</span>
                {{ $story->status?->name ?? 'Unknown status' }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            @if ($story->sprints->isNotEmpty())
                <a href="{{ route('sprints.show', $story->sprints->first()) }}"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    View Sprint
                </a>
            @endif
            <a href="{{ route('sprints.current') }}"
               class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                Current Sprint Board
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Narrative</h2>
                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $story->narrative ?: 'No narrative provided.' }}</p>
            </section>

            <section class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Acceptance Criteria</h2>
                @if ($story->acceptance_criteria)
                    <pre class="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{{ $story->acceptance_criteria }}</pre>
                @else
                    <p class="text-gray-600 dark:text-gray-300">No acceptance criteria provided.</p>
                @endif
            </section>

            <section class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Mason Task Plan</h2>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $story->tasks->count() }} task(s)</span>
                </div>

                @if ($story->tasks->isEmpty())
                    <p class="text-sm text-gray-600 dark:text-gray-300">No Mason tasks recorded yet for this story.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($story->tasks as $task)
                            <article class="rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-3">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $task->sort_order + 1 }}. {{ $task->title }}</h3>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                        {{ $task->state }}
                                    </span>
                                </div>
                                <p class="mt-2 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $task->description }}</p>

                                @if (!empty($task->success_criteria))
                                    <ul class="mt-2 list-disc pl-5 text-xs text-gray-600 dark:text-gray-300 space-y-1">
                                        @foreach ($task->success_criteria as $criterion)
                                            <li>{{ $criterion }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                <div class="mt-3 grid grid-cols-1 sm:grid-cols-4 gap-2 text-xs text-gray-500 dark:text-gray-400">
                                    <span>Mode: {{ $task->mode ?? '—' }}</span>
                                    <span>Priority: {{ $task->priority }}</span>
                                    <span>Attempts: {{ $task->max_attempts }}</span>
                                    <span>Provider: {{ $task->last_provider ?? '—' }}</span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <aside class="space-y-6">
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Details</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $story->status?->name ?? 'Unknown' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Priority</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $story->priority ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Story Points</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $story->est_points ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Type</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $story->story_type ?? 'feature' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Persona</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $story->persona?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Epic</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100 text-right">{{ $story->epic?->title ?? '—' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Sprint Assignments</h2>
                @if ($story->sprints->isEmpty())
                    <p class="text-sm text-gray-600 dark:text-gray-300">Not currently assigned to a sprint.</p>
                @else
                    <ul class="space-y-2">
                        @foreach ($story->sprints as $sprint)
                            <li>
                                <a href="{{ route('sprints.show', $sprint) }}" class="text-sm text-green-600 hover:text-green-800 dark:text-green-400">
                                    {{ $sprint->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Code Context</h2>
                @php($project = $story->epic?->project)
                @if (! $project)
                    <p class="text-sm text-gray-600 dark:text-gray-300">No project context mapped yet.</p>
                @else
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Project</dt>
                            <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $project->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Local Location</dt>
                            <dd class="font-mono text-xs text-gray-900 dark:text-gray-100 break-all">{{ $project->local_location ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Code Folder</dt>
                            <dd class="font-mono text-xs text-gray-900 dark:text-gray-100 break-all">{{ $project->code_folder ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">GitHub</dt>
                            <dd class="font-mono text-xs text-gray-900 dark:text-gray-100 break-all">{{ $project->github_repo ?: '—' }}</dd>
                        </div>
                    </dl>
                @endif
            </section>
        </aside>
    </div>
@endsection
