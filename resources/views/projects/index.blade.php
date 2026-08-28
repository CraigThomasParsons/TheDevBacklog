@extends('layouts.app')

@section('title', 'Projects')

@section('header')
    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Projects</h1>
@endsection

@section('content')
    @if (($failedSyncEventsLastHour ?? 0) > 0)
        <div class="mb-4 bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded">
            Projection sync alert: {{ $failedSyncEventsLastHour }} failed sync event(s) in the last hour.
        </div>
    @endif

    <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <form method="GET" action="{{ route('projects.index') }}" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search projects..."
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition">Filter</button>
            <a href="{{ route('projects.index') }}" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800">Clear</a>
        </form>
    </div>

    @if ($connectionError)
        <div class="mb-4 bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded">
            {{ $connectionError }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Project</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Epics</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ready Stories</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sprints</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sync Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($projects as $project)
                    @php
                        $projectStats = $stats[$project->id] ?? ['epic_count' => 0, 'ready_story_count' => 0, 'sprint_count' => 0];
                        $minutesSinceLastSync = $project->last_synced_at?->diffInMinutes(now());
                        $isSyncHealthy = $minutesSinceLastSync !== null && $minutesSinceLastSync <= ($syncLagWarningMinutes ?? 20);
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $project->name }}</div>
                            @if (!empty($project->description))
                                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ \Illuminate\Support\Str::limit($project->description, 90) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $projectStats['epic_count'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $projectStats['ready_story_count'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $projectStats['sprint_count'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                            @if ($project->last_synced_at === null)
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">Not synced</span>
                            @elseif ($isSyncHealthy)
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Healthy</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-700">Lagging</span>
                            @endif
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $project->last_synced_at?->diffForHumans() ?? 'Never' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            <a href="{{ route('epic-drafts.index', ['project_id' => $project->id]) }}" class="text-green-600 hover:text-green-800">View Epic Drafts</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            No projects available.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
