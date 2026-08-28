@extends('layouts.app')

@section('title', 'Sprints')

@section('header')
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Sprints</h1>
        <div class="flex items-center gap-3">
            <a href="{{ route('sprints.board.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900">Board View</a>
            <a href="{{ route('sprints.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                + New Sprint
            </a>
        </div>
    </div>
@endsection

@section('content')
    <!-- Filters -->
    <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <form method="GET" action="{{ route('sprints.index') }}" class="flex gap-4 items-end">
            <div class="w-48">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                <select name="status" 
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                    <option value="">All Statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->key }}" {{ request('status') == $status->key ? 'selected' : '' }}>
                            {{ $status->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" 
                    class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition">
                Filter
            </button>
            <a href="{{ route('sprints.index') }}" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800">
                Clear
            </a>
        </form>
    </div>

    <!-- Sprints Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($sprints as $sprint)
            @php
                $statusColors = [
                    'draft' => 'bg-gray-100 text-gray-800 border-gray-300',
                    'ready' => 'bg-blue-100 text-blue-800 border-blue-300',
                    'active' => 'bg-green-100 text-green-800 border-green-300',
                    'closed' => 'bg-purple-100 text-purple-800 border-purple-300',
                    'archived' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                ];
            @endphp
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-shadow border-l-4 {{ str_replace(['bg-', 'text-'], ['', 'border-'], $statusColors[$sprint->status?->key] ?? 'border-gray-300') }}">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <a href="{{ route('sprints.show', $sprint) }}" 
                               class="text-lg font-semibold text-gray-900 dark:text-gray-100 hover:text-green-600">
                                {{ $sprint->title }}
                            </a>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($sprint->is_frozen)
                                <span class="text-blue-500" title="Frozen">🔒</span>
                            @endif
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$sprint->status?->key] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $sprint->status?->name ?? 'Draft' }}
                            </span>
                        </div>
                    </div>
                    
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-300 line-clamp-2">
                        {{ Str::limit($sprint->goal, 100) }}
                    </p>

                    <div class="mt-4 flex items-center justify-between text-sm">
                        <div class="flex items-center gap-4 text-gray-500 dark:text-gray-400">
                            <span>📋 {{ $sprint->stories->count() }} stories</span>
                            <span>⏱️ {{ $sprint->total_points }} pts</span>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2">
                        <a href="{{ route('sprints.show', $sprint) }}" 
                           class="text-sm text-green-600 hover:text-green-800 dark:text-green-400">
                            View
                        </a>
                        @unless ($sprint->is_frozen)
                            <a href="{{ route('sprints.edit', $sprint) }}" 
                               class="text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400">
                                Edit
                            </a>
                        @endunless
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No sprints</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new sprint.</p>
                <div class="mt-6">
                    <a href="{{ route('sprints.create') }}" 
                       class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                        + New Sprint
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $sprints->withQueryString()->links() }}
    </div>
@endsection
