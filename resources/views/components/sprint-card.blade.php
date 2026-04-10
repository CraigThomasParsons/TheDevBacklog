@props(['sprint', 'opacity' => ''])

<div draggable="true"
     @dragstart="onDragStart($event, {{ $sprint->id }})"
     class="bg-white dark:bg-gray-700 p-4 rounded shadow-sm border border-gray-200 dark:border-gray-600 cursor-move hover:shadow-md transition-shadow active:cursor-grabbing {{ $opacity }}">
    
    <div class="flex justify-between items-start mb-2">
        <h3 class="font-medium text-gray-900 dark:text-gray-100">
            <a href="{{ route('sprints.show', $sprint) }}" class="hover:underline">{{ $sprint->title }}</a>
        </h3>
        <span class="text-xs font-mono text-gray-500 dark:text-gray-400">#{{ $sprint->id }}</span>
    </div>

    <p class="text-xs text-gray-600 dark:text-gray-300 line-clamp-2 mb-3" title="{{ $sprint->goal }}">
        {{ $sprint->goal }}
    </p>

    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
        <span class="flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            {{ $sprint->stories_count ?? $sprint->stories->count() }}
        </span>
        <span class="flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ $sprint->created_at->format('M d') }}
        </span>
    </div>
</div>
