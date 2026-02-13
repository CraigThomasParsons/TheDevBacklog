@extends('layouts.app')

@section('title', 'Backlog - Ready Stories')

@section('content')
<div class="max-w-6xl mx-auto">
    {{-- Page header with title and description --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">📋 Backlog</h1>
            <p class="text-gray-600 mt-1">Stories ready for sprint planning</p>
        </div>
        <a href="{{ route('sprints.create') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition">
            + New Sprint
        </a>
    </div>

    {{-- Summary statistics card --}}
    <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
        <div class="grid grid-cols-3 gap-4 text-center">
            <div>
                <span class="text-2xl font-bold text-green-600">{{ $stories->total() }}</span>
                <p class="text-gray-500 text-sm">Ready Stories</p>
            </div>
            <div>
                <span class="text-2xl font-bold text-blue-600">{{ $stories->sum('story_points') ?? 0 }}</span>
                <p class="text-gray-500 text-sm">Total Story Points</p>
            </div>
            <div>
                <span class="text-2xl font-bold text-purple-600">{{ $stories->groupBy('epic_id')->count() }}</span>
                <p class="text-gray-500 text-sm">Epics Represented</p>
            </div>
        </div>
    </div>

    {{-- Story list container --}}
    @if($stories->count() > 0)
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="p-4 border-b bg-gray-50">
                <h2 class="font-semibold text-gray-800">Ready for Sprint Planning</h2>
            </div>

            {{-- List of stories ready to be added to sprints --}}
            <div class="divide-y">
                @foreach($stories as $story)
                    <div class="p-4 hover:bg-gray-50 transition">
                        <div class="flex justify-between items-start">
                            {{-- Story title and metadata --}}
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-sm text-gray-500">#{{ $story->id }}</span>
                                    <h3 class="font-medium text-gray-900">{{ $story->title }}</h3>
                                </div>

                                {{-- Epic and persona badges --}}
                                <div class="flex items-center gap-2 text-sm">
                                    @if($story->epic)
                                        <span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded">
                                            📁 {{ $story->epic->name }}
                                        </span>
                                    @endif
                                    @if($story->persona)
                                        <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded">
                                            👤 {{ $story->persona->name }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Story points and priority badges --}}
                            <div class="flex items-center gap-3">
                                @if($story->story_points)
                                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-medium">
                                        {{ $story->story_points }} pts
                                    </span>
                                @endif
                                <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-sm">
                                    P{{ $story->priority ?? 3 }}
                                </span>
                            </div>
                        </div>

                        {{-- User story format display --}}
                        @if($story->user_story)
                            <p class="text-gray-600 text-sm mt-2 italic">
                                "{{ Str::limit($story->user_story, 150) }}"
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Pagination controls --}}
            @if($stories->hasPages())
                <div class="p-4 border-t bg-gray-50">
                    {{ $stories->links() }}
                </div>
            @endif
        </div>
    @else
        {{-- Empty state when no stories are ready --}}
        <div class="bg-white rounded-lg shadow-sm border p-12 text-center">
            <div class="text-6xl mb-4">📭</div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No Stories Ready</h3>
            <p class="text-gray-500 mb-6">
                Stories marked as "Ready" in TheWritersRoom will appear here for sprint planning.
            </p>
            <a href="{{ route('sprints.index') }}" class="text-green-600 hover:text-green-800">
                View Existing Sprints →
            </a>
        </div>
    @endif
</div>
@endsection
