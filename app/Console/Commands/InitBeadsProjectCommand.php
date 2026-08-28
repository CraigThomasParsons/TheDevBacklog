<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Project;
use Symfony\Component\Process\Process;

class InitBeadsProjectCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bd:init-project {project_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initializes the Beads (bd) distributed graph tracker in the local project directory.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $projectId = $this->argument('project_id');
        $project = Project::find($projectId);

        if (!$project) {
            $this->error("[-] Project {$projectId} not found in TheDevBacklog registry.");
            return Command::FAILURE;
        }

        if (empty($project->local_location)) {
            $this->error("[-] Project {$projectId} ({$project->name}) does not have a local_location defined.");
            return Command::FAILURE;
        }

        $projectDir = $project->local_location;
        if (str_starts_with($projectDir, '~/')) {
            $projectDir = str_replace('~/', getenv('HOME') . '/', $projectDir);
        }

        if (!is_dir($projectDir)) {
            $this->error("[-] Directory does not exist: {$projectDir}");
            return Command::FAILURE;
        }

        $this->info("[*] Initializing Beads in {$projectDir}...");

        $process = new Process(['bd', 'init']);
        $process->setWorkingDirectory($projectDir);
        $process->setEnv([
            // Include ~/.local/bin where bd was installed via the script
            'PATH' => getenv('PATH') . ':/home/craigpar/.local/bin:/usr/local/bin:/usr/bin:/bin',
            'HOME' => getenv('HOME') ?: '/home/craigpar',
        ]);
        
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error("[-] Failed to initialize Beads tracker:");
            $this->error($process->getErrorOutput());
            return Command::FAILURE;
        }

        $this->info($process->getOutput());
        $this->info("[+] Beads (bd) tracking graph initialized successfully for {$project->name}.");

        return Command::SUCCESS;
    }
}
