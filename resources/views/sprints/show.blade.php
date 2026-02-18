@extends('layouts.app')

@section('title', $sprint->title)

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $sprint->title }}</h1>
            <div class="flex items-center gap-3 mt-2">
                @php
                    $statusColors = [
                        'draft' => 'bg-gray-100 text-gray-800',
                        'ready' => 'bg-blue-100 text-blue-800',
                        'active' => 'bg-green-100 text-green-800',
                        'closed' => 'bg-purple-100 text-purple-800',
                        'archived' => 'bg-yellow-100 text-yellow-800',
                    ];
                @endphp
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$sprint->status?->key] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ $sprint->status?->name ?? 'Draft' }}
                </span>
                @if ($sprint->is_frozen)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        🔒 Frozen
                    </span>
                @endif
            </div>
        </div>
        <div class="flex gap-2">
            @unless ($sprint->is_frozen)
                <div class="relative group">
                    <form method="POST" action="{{ route('sprints.freeze', $sprint) }}" 
                          onsubmit="return confirm('Are you sure? Once frozen, the sprint cannot be edited.');">
                        @csrf
                        <button type="submit"
                                title="Freezes editing and marks this sprint Ready. Current Sprint prioritizes Active, then Ready, then Draft."
                                class="px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                            🔒 Freeze Sprint
                        </button>
                    </form>
                    <div class="pointer-events-none absolute right-0 top-full mt-2 z-20 hidden w-72 rounded-md bg-gray-900 px-3 py-2 text-xs text-white shadow-lg group-hover:block group-focus-within:block">
                        Freezes editing and marks this sprint <strong>Ready</strong>. The Current Sprint board picks <strong>Active</strong> first, then <strong>Ready</strong>, then <strong>Draft</strong>.
                    </div>
                </div>
                <a href="{{ route('sprints.edit', $sprint) }}" 
                   class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    Edit
                </a>
            @else
                <a href="{{ route('sprints.export', $sprint) }}" 
                   class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                    📄 Export SprintSpec
                </a>
            @endunless
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sprint Details Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Goal -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">🎯 Sprint Goal</h2>
                <p class="text-gray-700 dark:text-gray-300">{{ $sprint->goal }}</p>
            </div>

            <!-- Success Criteria -->
            @if ($sprint->success_criteria)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">✅ Success Criteria</h2>
                    <div class="prose prose-sm dark:prose-invert max-w-none">
                        <pre class="whitespace-pre-wrap text-gray-700 dark:text-gray-300 text-sm">{{ $sprint->success_criteria }}</pre>
                    </div>
                </div>
            @endif

            <!-- Stats -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">📊 Sprint Stats</h2>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Stories</dt>
                        <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $sprint->stories->count() }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Total Points</dt>
                        <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $sprint->total_points }}</dd>
                    </div>
                    @if ($sprint->frozen_at)
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Frozen At</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ $sprint->frozen_at->format('M d, Y H:i') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        <!-- Stories List -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Sprint Stories</h2>
                </div>

                @if ($sprint->stories->count() > 0)
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($sprint->stories as $index => $story)
                            @php
                                $storyStatusColors = [
                                    'draft' => 'bg-gray-100 text-gray-800',
                                    'ready' => 'bg-blue-100 text-blue-800',
                                    'blocked' => 'bg-red-100 text-red-800',
                                    'in_progress' => 'bg-yellow-100 text-yellow-800',
                                    'done' => 'bg-green-100 text-green-800',
                                    'archived' => 'bg-gray-100 text-gray-600',
                                ];
                            @endphp
                            <li class="px-6 py-4">
                                <div class="flex items-start gap-4">
                                    <span class="flex-shrink-0 w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center text-sm font-medium text-gray-600 dark:text-gray-300">
                                        {{ $index + 1 }}
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ $story->title }}
                                            </span>
                                            @if ($story->est_points)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                                    {{ $story->est_points }} pts
                                                </span>
                                            @endif
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $storyStatusColors[$story->status?->key] ?? 'bg-gray-100 text-gray-800' }}">
                                                {{ $story->status?->name ?? 'Draft' }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-2 italic">
                                            "{{ $story->narrative }}"
                                        </p>
                                        @if ($story->acceptance_criteria)
                                            <details class="mt-3">
                                                <summary class="text-sm text-green-600 dark:text-green-400 cursor-pointer hover:text-green-800">
                                                    View Acceptance Criteria
                                                </summary>
                                                <div class="mt-2 p-3 bg-gray-50 dark:bg-gray-700 rounded text-sm">
                                                    <pre class="whitespace-pre-wrap text-gray-700 dark:text-gray-300 font-mono text-xs">{{ $story->acceptance_criteria }}</pre>
                                                </div>
                                            </details>
                                        @endif
                                        <div class="flex items-center gap-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                            @if ($story->persona)
                                                <span>👤 {{ $story->persona->name }}</span>
                                            @endif
                                            @if ($story->epic)
                                                <span>📁 {{ $story->epic->title }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="px-6 py-12 text-center">
                        <p class="text-gray-500 dark:text-gray-400">No stories in this sprint.</p>
                        @unless ($sprint->is_frozen)
                            <a href="{{ route('sprints.edit', $sprint) }}" 
                               class="mt-4 inline-flex items-center text-sm text-green-600 hover:text-green-800">
                                Add stories →
                            </a>
                        @endunless
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
