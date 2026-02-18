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
            <a href="{{ route('sprints.show', $currentSprint) }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                View Sprint Details
            </a>
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
        @php
            $columns = [
                ['label' => 'To Do', 'stories' => $toDoStories, 'header' => 'bg-gray-100 text-gray-800'],
                ['label' => 'In Progress', 'stories' => $inProgressStories, 'header' => 'bg-yellow-100 text-yellow-800'],
                ['label' => 'In Review', 'stories' => $inReviewStories, 'header' => 'bg-blue-100 text-blue-800'],
                ['label' => 'Done', 'stories' => $doneStories, 'header' => 'bg-green-100 text-green-800'],
            ];
        @endphp

        <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
            @foreach ($columns as $column)
                <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 flex flex-col min-h-[320px]">
                    <header class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between {{ $column['header'] }} rounded-t-lg">
                        <h2 class="font-semibold text-sm uppercase tracking-wide">{{ $column['label'] }}</h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-white/70 text-gray-800">
                            {{ $column['stories']->count() }}
                        </span>
                    </header>

                    <div class="p-4 space-y-3 flex-1">
                        @forelse ($column['stories'] as $story)
                            <article class="rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-3 hover:border-green-400 transition">
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
    @endif
@endsection
