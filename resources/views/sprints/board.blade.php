@extends('layouts.app')

@section('title', 'Sprint Board')

@section('header')
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Sprint Board</h1>
        <div class="flex items-center gap-3">
            <a href="{{ route('sprints.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900">List View</a>
            <a href="{{ route('sprints.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                + New Sprint
            </a>
            @if(request('show_closed'))
                <a href="{{ route('sprints.board.index') }}" class="text-sm text-indigo-600">Hide Closed</a>
            @else
                <a href="{{ route('sprints.board.index', ['show_closed' => 1]) }}" class="text-sm text-gray-500">Show Closed</a>
            @endif
        </div>
    </div>
@endsection

@section('content')
    <div x-data="sprintBoard()" class="h-[calc(100vh-12rem)] min-h-[500px]">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 h-full">
            
            <!-- Backlog Column -->
            <div class="flex flex-col bg-gray-100 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 h-full"
                 @dragover.prevent="onDragOver($event)"
                 @drop.prevent="onDrop($event, 'backlog')">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 rounded-t-lg">
                    <h2 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center justify-between">
                        Backlog (Draft/Ready)
                        <span class="bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 py-0.5 px-2 rounded-full text-xs">
                            {{ $backlogSprints->count() }}
                        </span>
                    </h2>
                </div>
                <div class="flex-1 p-4 overflow-y-auto space-y-3">
                    @foreach($backlogSprints as $sprint)
                        <x-sprint-card :sprint="$sprint" />
                    @endforeach
                    @if($backlogSprints->isEmpty())
                        <p class="text-sm text-gray-400 text-center py-4">No sprints in backlog.</p>
                    @endif
                </div>
            </div>

            <!-- Active Column -->
            <div class="flex flex-col bg-green-50 dark:bg-green-900/10 rounded-lg shadow-sm border-2 border-green-200 dark:border-green-800 border-dashed h-full"
                 @dragover.prevent="onDragOver($event)"
                 @drop.prevent="onDrop($event, 'active')">
                <div class="p-4 border-b border-green-200 dark:border-green-800 bg-green-100 dark:bg-green-900/20 rounded-t-lg">
                    <h2 class="font-semibold text-green-800 dark:text-green-300 flex items-center justify-between">
                        Current Sprint
                        <span class="bg-green-200 dark:bg-green-800 text-green-800 dark:text-green-200 py-0.5 px-2 rounded-full text-xs">
                            {{ $activeSprint ? 1 : 0 }}
                        </span>
                    </h2>
                </div>
                <div class="flex-1 p-4 overflow-y-auto space-y-3 relative">
                    @if($activeSprint)
                        <x-sprint-card :sprint="$activeSprint" />
                    @else
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <p class="text-sm text-green-600/50 dark:text-green-400/50 font-medium">Drag a sprint here to start it</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- History Column -->
            <div class="flex flex-col bg-gray-100 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 h-full"
                 @dragover.prevent="onDragOver($event)"
                 @drop.prevent="onDrop($event, 'history')">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 rounded-t-lg">
                    <h2 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center justify-between">
                        History (Closed)
                        <span class="bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 py-0.5 px-2 rounded-full text-xs">
                            {{ $historySprints->count() }}
                        </span>
                    </h2>
                </div>
                <div class="flex-1 p-4 overflow-y-auto space-y-3">
                    @forelse($historySprints as $sprint)
                        <x-sprint-card :sprint="$sprint" opacity="opacity-75" />
                    @empty
                        <p class="text-sm text-gray-400 text-center py-4">
                            {{ request('show_closed') ? 'No closed sprints found.' : 'Closed sprints hidden.' }}
                        </p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('sprintBoard', () => ({
                draggingSprintId: null,

                onDragStart(event, sprintId) {
                    this.draggingSprintId = sprintId;
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', sprintId);
                    // Add a generic drag image if needed, for now browser default is okay
                },

                onDragOver(event) {
                    event.preventDefault();
                    return false;
                },

                async onDrop(event, targetColumn) {
                    const sprintId = this.draggingSprintId;
                    if (!sprintId) return;

                    // Optimistic UI update could happen here, but reloading is safer for state consistency 
                    // given singleton rules. For now, simple reload after fetch.
                    
                    try {
                        const response = await fetch(`/api/sprints/${sprintId}/move`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ column: targetColumn })
                        });

                        if (response.ok) {
                            window.location.reload();
                        } else {
                            const data = await response.json();
                            alert(data.message || 'Failed to move sprint.');
                        }
                    } catch (error) {
                        console.error('Error moving sprint:', error);
                        alert('Network error.');
                    } finally {
                        this.draggingSprintId = null;
                    }
                }
            }));
        });
    </script>
@endsection
