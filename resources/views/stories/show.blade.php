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

            @php
                $transitionMap = [
                    'draft'       => ['label' => 'Mark Ready',        'color' => 'bg-gray-500 hover:bg-gray-600'],
                    'ready'       => ['label' => 'Start Work',        'color' => 'bg-blue-600 hover:bg-blue-700'],
                    'in_progress' => ['label' => 'Send to Review',    'color' => 'bg-yellow-500 hover:bg-yellow-600'],
                    'in_testing'  => ['label' => 'Mark Done',         'color' => 'bg-green-600 hover:bg-green-700'],
                ];
                $currentKey = $story->status?->key;
                $transition = $transitionMap[$currentKey] ?? null;
            @endphp

            @if ($transition)
                <form method="POST" action="{{ route('stories.transition', $story) }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest {{ $transition['color'] }}">
                        {{ $transition['label'] }}
                    </button>
                </form>
            @endif

            <a href="{{ route('sprints.current') }}"
               class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                Current Sprint Board
            </a>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('fileBrowser', () => ({
            isOpen: false,
            currentPath: '/home/craigpar/Code',
            directories: [],
            isLoading: false,

            init() {
                this.$watch('isOpen', value => {
                    if (value && this.directories.length === 0) {
                        this.browse(this.currentPath);
                    }
                });
            },

            open() {
                this.isOpen = true;
            },

            close() {
                this.isOpen = false;
            },

            async browse(path) {
                this.isLoading = true;
                try {
                    const response = await fetch(`{{ route('filesystem.browse') }}?path=${encodeURIComponent(path)}`);
                    const data = await response.json();
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    this.currentPath = data.current_path;
                    this.directories = data.directories;
                } catch (error) {
                    console.error('Error browsing:', error);
                    alert('Failed to load directory listing.');
                } finally {
                    this.isLoading = false;
                }
            },

            selectFolder(path) {
                // Submit form to attach folder
                this.$refs.folderPathInput.value = path;
                this.$refs.addFolderForm.submit();
            }
        }));
    });
</script>
@endpush

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="{ showTaskModal: false, selectedTask: {} }">
        <!-- Main Content Column -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Narrative -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Narrative</h2>
                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $story->narrative ?: 'No narrative provided.' }}</p>
            </section>

            <!-- Acceptance Criteria -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Acceptance Criteria</h2>
                @if ($story->acceptance_criteria)
                    <pre class="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{{ $story->acceptance_criteria }}</pre>
                @else
                    <p class="text-gray-600 dark:text-gray-300">No acceptance criteria provided.</p>
                @endif
            </section>

            <!-- Mason Task Plan -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Mason Task Plan</h2>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $story->tasks->count() }} task(s)</span>
                </div>
                <!-- ... task list content ... -->
                 @if ($story->tasks->isEmpty())
                    <p class="text-sm text-gray-600 dark:text-gray-300">No Mason tasks recorded yet for this story.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($story->tasks as $task)
                            <article class="rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-3 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                     @click="selectedTask = {{ Js::from($task) }}; showTaskModal = true">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $task->sort_order + 1 }}. {{ $task->title }}</h3>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                        {{ $task->state }}
                                    </span>
                                </div>
                                <p class="mt-2 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $task->description }}</p>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <!-- Comments -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Comments</h2>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $story->comments->count() }} comment(s)</span>
                </div>
                <!-- ... comments content ... -->
                 <form method="POST" action="{{ route('stories.comments.store', $story) }}" class="mb-4 space-y-3">
                    @csrf
                    <div>
                        <label for="author_name" class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Name (optional)</label>
                        <input id="author_name" name="author_name" type="text" maxlength="120"
                               placeholder="Anonymous"
                               class="w-full rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <label for="body" class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Comment</label>
                        <textarea id="body" name="body" rows="3" required maxlength="5000"
                                  placeholder="Leave a status note, handoff note, or blocker..."
                                  class="w-full rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-gray-100"></textarea>
                    </div>
                    <button type="submit"
                            class="inline-flex items-center px-3 py-2 rounded-md text-sm font-semibold text-white bg-green-600 hover:bg-green-700">
                        Add Comment
                    </button>
                </form>
                @if ($story->comments->isEmpty())
                    <p class="text-sm text-gray-600 dark:text-gray-300">No comments yet.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($story->comments as $comment)
                            <article class="rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-3">
                                <div class="flex items-center justify-between gap-3 mb-1">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $comment->author_name }}
                                        <span class="ml-2 text-xs font-medium text-gray-500 dark:text-gray-400">({{ $comment->source }})</span>
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $comment->created_at?->diffForHumans() }}</span>
                                </div>
                                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $comment->body }}</p>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <!-- Sidebar -->
        <aside class="space-y-6">
            <!-- Details -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <!-- ... details content ... -->
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Details</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $story->status?->name ?? 'Unknown' }}</dd>
                    </div>
                     <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Points</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $story->est_points ?? '—' }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Sprint Assignments -->
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

            <!-- Code Context with File Browser Integration -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow p-6" x-data="fileBrowser()">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Code Context</h2>
                    <button @click="open()" type="button" class="text-xs text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 font-medium">
                        + Add Folder
                    </button>
                </div>

                @php($project = $story->epic?->project)
                @if ($project)
                    <div class="mb-4 space-y-2 pb-4 border-b border-gray-100 dark:border-gray-700">
                        <p class="text-xs font-medium text-gray-500 uppercase">Project Defaults</p>
                        <div class="text-sm">
                            <span class="block text-gray-500 text-xs">Project</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $project->name }}</span>
                        </div>
                    </div>
                @endif

                <div class="space-y-2">
                    <p class="text-xs font-medium text-gray-500 uppercase">Linked Folders</p>
                    @if($story->codeFolders->isEmpty())
                        <p class="text-sm text-gray-500 italic">No specific folders linked.</p>
                    @else
                        <ul class="space-y-2">
                            @foreach($story->codeFolders as $folder)
                                <li class="flex items-center justify-between text-xs bg-gray-50 dark:bg-gray-700 p-2 rounded group">
                                    <span class="font-mono text-gray-700 dark:text-gray-300 break-all truncate mr-2" title="{{ $folder->folder_path }}">
                                        {{ Str::limit($folder->folder_path, 30, '...') }}
                                    </span>
                                    <form method="POST" action="{{ route('code-folders.destroy', $folder) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-gray-400 hover:text-red-600 dark:hover:text-red-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <!-- Hidden Form for Adding Folder -->
                <form x-ref="addFolderForm" method="POST" action="{{ route('stories.code-folders.store', $story) }}" class="hidden">
                    @csrf
                    <input type="hidden" name="folder_path" x-ref="folderPathInput">
                </form>

                <!-- File Browser Modal -->
                <div x-show="isOpen" 
                     style="display: none;"
                     class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="close()"></div>

                        <div class="inline-block w-full max-w-2xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 rounded-lg shadow-xl">
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Select Code Folder</h3>
                                <button @click="close()" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            
                            <div class="px-4 py-2 bg-gray-100 dark:bg-gray-700 flex items-center gap-2">
                                <span class="text-gray-500 dark:text-gray-400 font-mono text-sm">Path:</span>
                                <input type="text" x-model="currentPath" @keydown.enter="browse(currentPath)" 
                                       class="flex-1 text-sm font-mono border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-800 dark:text-gray-200">
                                <button @click="browse(currentPath)" class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Go</button>
                            </div>

                            <div class="h-96 overflow-y-auto p-2" :class="{ 'opacity-50': isLoading }">
                                <div x-show="isLoading" class="flex justify-center py-4">
                                    <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                <ul class="space-y-1">
                                    <template x-for="dir in directories" :key="dir.path">
                                        <li class="flex items-center justify-between p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded cursor-pointer group"
                                            @dblclick="browse(dir.path)">
                                            <div class="flex items-center gap-2 flex-1" @click="browse(dir.path)">
                                                <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path></svg>
                                                <span x-text="dir.name" class="text-sm font-medium text-gray-700 dark:text-gray-200"></span>
                                            </div>
                                            <button @click.stop="selectFolder(dir.path)" 
                                                    class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded opacity-0 group-hover:opacity-100 hover:bg-green-200">
                                                Select
                                            </button>
                                        </li>
                                    </template>
                                </ul>
                                <div x-show="directories.length === 0 && !isLoading" class="text-center py-8 text-gray-500">
                                    No directories found.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </aside>
    </div>

    <!-- Task Modal (Existing) -->
    <div x-show="showTaskModal" 
         style="display: none;"
    ...             class="fixed inset-0 z-50 overflow-y-auto" 
             aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showTaskModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                     @click="showTaskModal = false"
                     aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showTaskModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                    <span x-text="selectedTask.title"></span>
                                </h3>
                                
                                <div class="mt-4 space-y-4">
                                    <!-- Status Badge -->
                                    <div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                              :class="{
                                                  'bg-green-100 text-green-800': selectedTask.state === 'completed',
                                                  'bg-red-100 text-red-800': selectedTask.state === 'failed',
                                                  'bg-yellow-100 text-yellow-800': selectedTask.state === 'in_progress',
                                                  'bg-gray-100 text-gray-800': selectedTask.state === 'queued'
                                              }"
                                              x-text="selectedTask.state">
                                        </span>
                                        <span class="ml-2 text-xs text-gray-500" x-text="'Provider: ' + (selectedTask.last_provider || 'Pending')"></span>
                                    </div>

                                    <!-- Error Message -->
                                    <template x-if="selectedTask.error_message">
                                        <div class="rounded-md bg-red-50 p-4 border border-red-200">
                                            <div class="flex">
                                                <div class="flex-shrink-0">
                                                    <!-- Heroicon x-circle -->
                                                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                                <div class="ml-3">
                                                    <h3 class="text-sm font-medium text-red-800">Task Failure</h3>
                                                    <div class="mt-2 text-sm text-red-700 whitespace-pre-wrap font-mono" x-text="selectedTask.error_message"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Description -->
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">Description</h4>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300 whitespace-pre-wrap" x-text="selectedTask.description"></p>
                                    </div>

                                    <!-- Success Criteria -->
                                    <template x-if="selectedTask.success_criteria && selectedTask.success_criteria.length">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">Success Criteria</h4>
                                            <ul class="mt-1 list-disc pl-5 text-sm text-gray-600 dark:text-gray-300">
                                                <template x-for="criteria in selectedTask.success_criteria">
                                                    <li x-text="criteria"></li>
                                                </template>
                                            </ul>
                                        </div>
                                    </template>

                                    <!-- Metadata -->
                                    <div class="grid grid-cols-2 gap-4 text-xs text-gray-500 border-t border-gray-200 dark:border-gray-700 pt-4">
                                        <div>
                                            <span class="block font-medium">External ID</span>
                                            <span class="font-mono" x-text="selectedTask.external_task_id || '—'"></span>
                                        </div>
                                        <div>
                                            <span class="block font-medium">Duration</span>
                                            <span x-text="(selectedTask.last_duration_ms ? (selectedTask.last_duration_ms + 'ms') : '—')"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" 
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                @click="showTaskModal = false">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
@endsection
