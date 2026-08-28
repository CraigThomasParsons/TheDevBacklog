<?php

namespace App\Observers;

use App\Models\StoryTask;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;

class StoryTaskObserver
{
    /**
     * Handle the StoryTask "saved" event.
     */
    public function saved(StoryTask $storyTask): void
    {
        // Prevent infinite loop if we are just saving the external ID
        if ($storyTask->isDirty('external_task_id') && count($storyTask->getDirty()) === 1) {
            return;
        }

        $this->syncToBeads($storyTask);
    }

    private function syncToBeads(StoryTask $storyTask): void
    {
        $storyTask->loadMissing(['story.codeFolders', 'story.epic.project']);
        
        $repoPaths = [];
        if ($storyTask->story && $storyTask->story->codeFolders->isNotEmpty()) {
            foreach ($storyTask->story->codeFolders as $folder) {
                if (!empty($folder->folder_path)) {
                    $repoPaths[] = rtrim($folder->folder_path, '/');
                }
            }
        }

        if (empty($repoPaths) && $storyTask->story && $storyTask->story->epic && $storyTask->story->epic->project) {
            $projectPath = $storyTask->story->epic->project->local_location;
            if (!empty($projectPath)) {
                $repoPaths[] = rtrim($projectPath, '/');
            }
        }

        if (empty($repoPaths)) {
            Log::warning("StoryTaskObserver: Missing repository path for task {$storyTask->id}");
            return;
        }

        $repoPaths = array_unique($repoPaths);
        $title = $storyTask->title ?? 'Untitled Task';
        
        $stateMap = [
            'pending' => 'open',
            'in_progress' => 'in_progress',
            'completed' => 'done',
            'failed' => 'open'
        ];
        $bdState = $stateMap[$storyTask->state] ?? 'open';
        
        foreach ($repoPaths as $repoPath) {
            if (str_starts_with($repoPath, '~/')) {
                $repoPath = str_replace('~/', getenv('HOME') . '/', $repoPath);
            }

            if (!is_dir($repoPath)) {
                continue;
            }
            
            // Wait, bd config might not be initialized there.
            if (!is_dir($repoPath . '/.beads')) {
                continue; 
            }
            
            if (empty($storyTask->external_task_id)) {
                $process = new Process(['bd', 'create', $title, '--json']);
                $process->setWorkingDirectory($repoPath);
                $process->setEnv($this->getEnvironment());
                $process->run();
                
                if ($process->isSuccessful()) {
                    $jsonOut = trim($process->getOutput());
                    // Sometimes bd outputs raw text if json flag isn't perfectly supported in this version.
                    $output = json_decode($jsonOut, true);
                    
                    if (is_array($output) && isset($output['id'])) {
                        $storyTask->external_task_id = $output['id'];
                        $storyTask->saveQuietly();
                        
                        $updateProcess = new Process(['bd', 'update', $output['id'], '--status', $bdState]);
                        $updateProcess->setWorkingDirectory($repoPath);
                        $updateProcess->setEnv($this->getEnvironment());
                        $updateProcess->run();
                    }
                } else {
                    Log::error("Failed to create bd task: " . $process->getErrorOutput());
                }
            } else {
                $process = new Process(['bd', 'update', $storyTask->external_task_id, '--status', $bdState]);
                $process->setWorkingDirectory($repoPath);
                $process->setEnv($this->getEnvironment());
                $process->run();
                
                if (!$process->isSuccessful()) {
                    Log::error("Failed to update bd task: " . $process->getErrorOutput());
                }
            }
        }
    }
    
    private function getEnvironment(): array
    {
        return [
            'PATH' => getenv('PATH') . ':/home/craigpar/.local/bin:/usr/local/bin:/usr/bin:/bin',
            'HOME' => getenv('HOME') ?: '/home/craigpar',
        ];
    }
}
