<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StoryTask;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Str;

class DeckardCommitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deckard:commit {task_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically branch, commit, and push completed task code using Symfony Process.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $taskId = $this->argument('task_id');
        $this->info("[*] Deckard is processing task {$taskId}");

        $task = StoryTask::with(['story.codeFolders', 'story.epic.project'])->find($taskId);

        if (!$task) {
            $this->error("[-] Task {$taskId} not found.");
            return Command::FAILURE;
        }

        $repoPaths = [];

        // Check for specific code folders first
        if ($task->story && $task->story->codeFolders->isNotEmpty()) {
            foreach ($task->story->codeFolders as $folder) {
                if (!empty($folder->folder_path)) {
                    $repoPaths[] = rtrim($folder->folder_path, '/');
                }
            }
        }

        // Fallback to project local location
        if (empty($repoPaths) && $task->story && $task->story->epic && $task->story->epic->project) {
            $projectPath = $task->story->epic->project->local_location;
            if (!empty($projectPath)) {
                $repoPaths[] = rtrim($projectPath, '/');
            }
        }

        if (empty($repoPaths)) {
            $this->error("[-] Missing repository path for task {$taskId}");
            return Command::FAILURE;
        }

        // De-duplicate paths
        $repoPaths = array_unique($repoPaths);

        $success = true;

        foreach ($repoPaths as $repoPath) {
            if (str_starts_with($repoPath, '~/')) {
                $repoPath = str_replace('~/', getenv('HOME') . '/', $repoPath);
            }

            if (!is_dir($repoPath)) {
                $this->error("[-] Invalid or missing repository path: {$repoPath}");
                $success = false;
                continue;
            }

            $this->info("[*] Processing repository: {$repoPath}");

            $taskTitle = $task->title ?? 'chore';
            $branchSuffix = Str::slug(substr($taskTitle, 0, 30));
            $featureBranch = "feature/{$taskId}-{$branchSuffix}";

            try {
                $baseBranch = $this->determineTargetBranch($repoPath);
                $this->info("[*] Base branch chosen: {$baseBranch}");

                try {
                    $this->runGitCommand($repoPath, ['git', 'fetch', 'origin']);
                    $this->runGitCommand($repoPath, ['git', 'checkout', $baseBranch], false);
                    $this->runGitCommand($repoPath, ['git', 'pull', 'origin', $baseBranch], false);
                } catch (\Exception $e) {
                    $this->warn("[*] Warning: Could not fetch or pull from remote. Proceeding with local base branch checkout.");
                    $this->runGitCommand($repoPath, ['git', 'checkout', $baseBranch], false);
                }

                $this->info("[*] Creating branch: {$featureBranch}");
                try {
                    $this->runGitCommand($repoPath, ['git', 'checkout', '-b', $featureBranch]);
                } catch (\Exception $e) {
                    $this->info("[*] Branch exists, checking it out.");
                    $this->runGitCommand($repoPath, ['git', 'checkout', $featureBranch]);
                }

                $this->info("[*] Staging changes...");
                $this->runGitCommand($repoPath, ['git', 'add', '.']);

                $status = $this->runGitCommand($repoPath, ['git', 'status', '--porcelain']);
                if (empty(trim($status))) {
                    $this->info("[*] No changes detected. Deckard has nothing to commit for {$repoPath}.");
                    continue;
                }

                // Default commit prefix. Should match python deckard configuration
                $commitPrefix = 'feat(deckard): completed task';
                $commitMsg = "{$commitPrefix} {$taskId} - {$taskTitle}";
                
                $this->info("[*] Committing: {$commitMsg}");
                $this->runGitCommand($repoPath, ['git', 'commit', '-m', $commitMsg]);

                $this->info("[*] Pushing to origin {$featureBranch}...");
                try {
                    $this->runGitCommand($repoPath, ['git', 'push', '-u', 'origin', $featureBranch]);
                } catch (\Exception $e) {
                    $this->warn("[*] Warning: Could not push to remote. Branch left committed locally.");
                }

                $this->info("[+] Deckard completed Gitflow for {$repoPath}.");

            } catch (\Exception $e) {
                $this->error("[-] Deckard failed to process Git workflow in {$repoPath}: " . $e->getMessage());
                $success = false;
            }
        }

        return $success ? Command::SUCCESS : Command::FAILURE;
    }

    private function determineTargetBranch(string $repoPath): string
    {
        try {
            $branches = $this->runGitCommand($repoPath, ['git', 'branch', '-r']);
            if (str_contains($branches, 'origin/develop')) {
                return 'develop';
            }
        } catch (\Exception $e) {
            // ignore
        }

        try {
            $localBranches = $this->runGitCommand($repoPath, ['git', 'branch']);
            if (str_contains($localBranches, '* main') || str_contains($localBranches, ' main')) {
                return 'main';
            }
            if (str_contains($localBranches, '* master') || str_contains($localBranches, ' master')) {
                return 'master';
            }

            foreach (explode("\n", $localBranches) as $line) {
                if (str_starts_with($line, '* ')) {
                    return trim(substr($line, 2));
                }
            }
        } catch (\Exception $e) {
            // ignore
        }

        return 'main';
    }

    private function runGitCommand(string $repoPath, array $command, bool $throwOnError = true): string
    {
        $process = new Process($command);
        $process->setWorkingDirectory($repoPath);
        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful() && $throwOnError) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }
}
