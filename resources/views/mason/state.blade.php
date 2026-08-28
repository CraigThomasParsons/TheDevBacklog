@extends('layouts.app')

@section('title', 'Mason Run State')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Mason Run State</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Live execution snapshot for current sprint work.</p>
        </div>
        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('mason.state.provider') }}" class="flex items-center gap-2">
                @csrf
                <select name="provider_override"
                        title="Select which provider Mason should use."
                        class="bg-gray-800 border border-gray-600 text-white px-3 py-2 rounded-lg text-sm">
                    @foreach ($providerOptions as $providerKey => $providerLabel)
                        <option value="{{ $providerKey }}" @selected(data_get($state, 'run_control.provider_override', 'auto') === $providerKey)>{{ $providerLabel }}</option>
                    @endforeach
                </select>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded-lg text-sm font-semibold">
                    Set Provider
                </button>
            </form>
            <form method="POST" action="{{ route('mason.state.start') }}">
                @csrf
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold">
                    Start Sprint
                </button>
            </form>
            <button id="refreshStateButton" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                Refresh
            </button>
        </div>
    </div>

    <div id="mason-state-root"
         data-endpoint="{{ url('/api/mason/run-state') }}"
         data-initial='@json($state)'>
    </div>

    <script>
        (function () {
            const root = document.getElementById('mason-state-root');
            const refreshButton = document.getElementById('refreshStateButton');
            const endpoint = root.dataset.endpoint;
            let state = JSON.parse(root.dataset.initial || '{}');

            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;');

            const statusCount = (key) => Number(state.counts?.[key] ?? 0);

            function render() {
                if (!state.has_sprint) {
                    root.innerHTML = `
                        <div class="bg-yellow-100 border border-yellow-300 text-yellow-900 rounded-lg p-4">
                            No active/ready sprint found for Mason.
                        </div>
                    `;
                    return;
                }

                const currentStory = state.current_story
                    ? `<a class="text-green-600 hover:underline" href="${state.current_story.url}">
                            #${state.current_story.id} ${escapeHtml(state.current_story.title)}
                       </a>`
                    : '<span class="text-gray-500">No story in progress</span>';

                const nextReadyRows = (state.next_ready || []).map((story) => `
                    <tr class="border-b border-gray-700">
                        <td class="py-2 pr-2 text-gray-200">
                            <a class="text-green-600 hover:underline" href="${story.url}">
                                #${story.id} ${escapeHtml(story.title)}
                            </a>
                        </td>
                        <td class="py-2 pr-2 text-gray-400">${escapeHtml(story.story_type)}</td>
                        <td class="py-2 pr-2 text-gray-400">${story.priority}</td>
                    </tr>
                `).join('');

                root.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gray-800 rounded-lg p-4">
                            <div class="text-xs text-gray-400 uppercase">Current Sprint</div>
                            <div class="text-lg text-white font-semibold">${escapeHtml(state.sprint.title)}</div>
                            <div class="text-sm text-gray-400">Status: ${escapeHtml(state.sprint.status)}</div>
                            <div class="text-sm text-gray-400 mt-1">Provider: ${escapeHtml((state.run_control?.provider_options || {})[state.run_control?.provider_override] ?? (state.run_control?.provider_override ?? 'auto'))}</div>
                        </div>
                        <div class="bg-gray-800 rounded-lg p-4">
                            <div class="text-xs text-gray-400 uppercase">WIP</div>
                            <div class="text-2xl text-white font-semibold">${state.wip.current}/${state.wip.limit}</div>
                            <div class="text-sm text-gray-400">In Progress Limit</div>
                        </div>
                        <div class="bg-gray-800 rounded-lg p-4">
                            <div class="text-xs text-gray-400 uppercase">Enablers</div>
                            <div class="text-2xl text-white font-semibold">${state.enablers.completed}/${state.enablers.total}</div>
                            <div class="text-sm text-gray-400">Completed</div>
                        </div>
                        <div class="bg-gray-800 rounded-lg p-4">
                            <div class="text-xs text-gray-400 uppercase">Last Task Update</div>
                            <div class="text-sm text-white font-semibold">${escapeHtml(state.last_task_update_at ?? 'No task updates yet')}</div>
                        </div>
                    </div>

                    <div class="bg-gray-800 rounded-lg p-4 mb-6">
                        <div class="text-xs text-gray-400 uppercase mb-2">Runner Control</div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                            <div class="text-gray-300">Requested Run: <span class="font-semibold ${state.run_control?.is_running ? 'text-green-400' : 'text-yellow-400'}">${state.run_control?.is_running ? 'ON' : 'OFF'}</span></div>
                            <div class="text-gray-300">Heartbeat: <span class="font-semibold ${state.run_control?.heartbeat_fresh ? 'text-green-400' : 'text-red-400'}">${state.run_control?.heartbeat_fresh ? 'fresh' : 'stale'}</span></div>
                            <div class="text-gray-300">Message: <span class="text-white">${escapeHtml(state.run_control?.last_status_message ?? '(none)')}</span></div>
                        </div>
                    </div>

                    <div class="bg-gray-800 rounded-lg p-4 mb-6">
                        <div class="text-xs text-gray-400 uppercase mb-2">Story Counts</div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                            <div class="text-gray-300">Ready: <span class="text-white font-semibold">${statusCount('ready')}</span></div>
                            <div class="text-gray-300">In Progress: <span class="text-white font-semibold">${statusCount('in_progress')}</span></div>
                            <div class="text-gray-300">Completed: <span class="text-white font-semibold">${statusCount('completed')}</span></div>
                            <div class="text-gray-300">Done: <span class="text-white font-semibold">${statusCount('done')}</span></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-gray-800 rounded-lg p-4">
                            <div class="text-xs text-gray-400 uppercase mb-2">Current Story</div>
                            <div class="text-sm">${currentStory}</div>
                        </div>
                        <div class="bg-gray-800 rounded-lg p-4">
                            <div class="text-xs text-gray-400 uppercase mb-2">Next Ready Stories</div>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-400">
                                        <th class="py-2 pr-2">Story</th>
                                        <th class="py-2 pr-2">Type</th>
                                        <th class="py-2 pr-2">Priority</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${nextReadyRows || '<tr><td colspan="3" class="py-2 text-gray-500">No ready stories</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            }

            async function refreshState() {
                try {
                    const response = await fetch(endpoint, { headers: { 'Accept': 'application/json' } });
                    const payload = await response.json();
                    if (payload.success) {
                        state = payload.state;
                        render();
                    }
                } catch (error) {
                    console.error('Failed to refresh Mason state', error);
                }
            }

            refreshButton.addEventListener('click', refreshState);
            render();
            setInterval(refreshState, 15000);
        })();
    </script>
@endsection
