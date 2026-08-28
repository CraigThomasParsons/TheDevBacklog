@extends('layouts.app')

@section('title', 'Edit Sprint')

@section('header')
    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Edit Sprint: {{ $sprint->title }}</h1>
@endsection

@section('content')
    <form id="sprint-edit-form" method="POST" action="{{ route('sprints.update', $sprint) }}" x-data="sprintBuilder()">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Panel: Sprint Details -->
            <div class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Sprint Details</h2>

                    <div class="space-y-5">
                        <!-- Title -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Title <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="title" id="title" value="{{ old('title', $sprint->title) }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                            @error('title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Goal -->
                        <div>
                            <label for="goal" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Sprint Goal <span class="text-red-500">*</span>
                            </label>
                            <textarea name="goal" id="goal" rows="3" required
                                      class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">{{ old('goal', $sprint->goal) }}</textarea>
                            @error('goal')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Success Criteria -->
                        <div>
                            <label for="success_criteria" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Success Criteria
                            </label>
                            <textarea name="success_criteria" id="success_criteria" rows="4"
                                      class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">{{ old('success_criteria', $sprint->success_criteria) }}</textarea>
                        </div>

                        <!-- Status -->
                        <div>
                            <label for="sprint_status_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Status <span class="text-red-500">*</span>
                            </label>
                            <select name="sprint_status_id" id="sprint_status_id" required
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->id }}" {{ old('sprint_status_id', $sprint->sprint_status_id) == $status->id ? 'selected' : '' }}>
                                        {{ $status->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Story Picker -->
            <div class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Sprint Stories</h2>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            <span x-text="selectedStories.length">0</span> selected
                            (<span x-text="totalPoints">0</span> pts)
                        </span>
                    </div>

                    <!-- Selected Stories -->
                    <div class="mb-4">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Current Stories</h3>
                        <ul id="selected-stories" class="space-y-2 min-h-[100px] border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-3">
                            <template x-for="story in selectedStories" :key="story.id">
                                <li class="flex items-center justify-between bg-green-50 dark:bg-green-900/20 p-3 rounded-lg cursor-move">
                                    <input type="hidden" name="stories[]" :value="story.id">
                                    <div>
                                        <span class="font-medium text-gray-900 dark:text-gray-100" x-text="story.title"></span>
                                        <span class="ml-2 text-xs text-gray-500" x-text="story.points + ' pts'"></span>
                                    </div>
                                    <button type="button" @click="removeStory(story)" class="text-red-500 hover:text-red-700">
                                        ✕
                                    </button>
                                </li>
                            </template>
                            <li x-show="selectedStories.length === 0" class="text-center text-gray-400 py-4">
                                No stories selected
                            </li>
                        </ul>
                    </div>

                    <!-- Available Stories -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Add More Stories</h3>
                        <ul class="space-y-2 max-h-[300px] overflow-y-auto">
                            @forelse ($availableStories as $story)
                                <li class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer"
                                    @click="addStory({ id: {{ $story->id }}, title: '{{ addslashes($story->title) }}', points: {{ $story->est_points ?? 0 }} })"
                                    x-show="!isSelected({{ $story->id }})">
                                    <div>
                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $story->title }}</span>
                                        @if ($story->est_points)
                                            <span class="ml-2 text-xs text-gray-500">{{ $story->est_points }} pts</span>
                                        @endif
                                    </div>
                                    <span class="text-green-500">+</span>
                                </li>
                            @empty
                                <li class="text-center text-gray-400 py-4">
                                    No additional ready stories available.
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-6 flex justify-between">
            <button type="submit"
                    form="sprint-delete-form"
                    class="text-red-600 hover:text-red-800 text-sm"
                    onclick="return confirm('Are you sure you want to delete this sprint?');">
                Delete Sprint
            </button>

            <div class="flex gap-3">
                <a href="{{ route('sprints.show', $sprint) }}" 
                   class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Update Sprint
                </button>
            </div>
        </div>
    </form>

    <script>
        function sprintBuilder() {
            // Initialize with existing sprint stories
            const existingStories = @json($sprint->stories->map(fn($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'points' => $s->est_points ?? 0
            ]));

            return {
                selectedStories: existingStories,
                
                get totalPoints() {
                    return this.selectedStories.reduce((sum, story) => sum + (story.points || 0), 0);
                },

                isSelected(storyId) {
                    return this.selectedStories.some(story => story.id === storyId);
                },

                addStory(story) {
                    if (!this.isSelected(story.id)) {
                        this.selectedStories.push(story);
                    }
                },

                removeStory(story) {
                    this.selectedStories = this.selectedStories.filter(s => s.id !== story.id);
                },

                init() {
                    // Initialize Sortable for drag and drop reordering
                    new Sortable(document.getElementById('selected-stories'), {
                        animation: 150,
                        ghostClass: 'sortable-ghost',
                    });
                }
            }
        }
    </script>

    <form id="sprint-delete-form" method="POST" action="{{ route('sprints.destroy', $sprint) }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>
@endsection
