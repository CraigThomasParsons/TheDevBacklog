<?php
use App\Models\StoryTask;
use App\Models\Feature;

$tasks = StoryTask::whereNull('feature_id')->with('story.epic')->get();
$count = 0;
$skipped = 0;

foreach($tasks as $task) {
    if ($task->story && $task->story->epic && $task->story->epic->chat_project_id) {
        // Create a 1:1 Feature mapped to the old Story to ensure the graph stays unbroken
        $feature = Feature::firstOrCreate(
            [
                'story_id' => $task->story_id,
                'project_id' => $task->story->epic->chat_project_id
            ],
            [
                'title' => substr($task->story->title, 0, 200),
                'description' => $task->story->narrative ?? 'Auto-generated during architecture migration.',
                'status' => 'ready',
                'priority' => $task->story->priority ?? 0
            ]
        );
        
        $task->update(['feature_id' => $feature->id]);
        $count++;
    } else {
        $skipped++;
    }
}

echo "Successfully backfilled $count legacy tasks to the new Feature architecture paradigm! Skipped $skipped un-mappable tasks.\n";
