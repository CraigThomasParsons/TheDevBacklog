<?php

namespace Tests\Feature;

use App\Models\Story;
use App\Models\StoryCodeFolder;
use Database\Seeders\StatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoryCodeFolderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(StatusSeeder::class);
    }

    public function test_filesystem_browser_api()
    {
        // The system temporary directory is available on both developer machines and CI runners.
        $response = $this->getJson(route('filesystem.browse', ['path' => sys_get_temp_dir()]));

        $response->assertStatus(200);
        $response->assertJsonStructure(['current_path', 'directories']);
    }

    public function test_can_attach_code_folder_to_story()
    {
        $story = Story::factory()->create();
        $folderPath = '/tmp/some/fake/path';

        // We bypass the is_dir check in controller for the test or create it?
        // Controller has: if (!is_dir($validated['folder_path']))
        // We must ensure the path exists.
        if (! is_dir($folderPath)) {
            mkdir($folderPath, 0777, true);
        }

        $response = $this->post(route('stories.code-folders.store', $story), [
            'folder_path' => $folderPath,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('story_code_folders', [
            'story_id' => $story->id,
            'folder_path' => $folderPath,
        ]);

        rmdir($folderPath); // Cleanup
    }

    public function test_cannot_attach_duplicate_folder()
    {
        $story = Story::factory()->create();
        $folderPath = '/tmp/some/fake/path/dup';
        if (! is_dir($folderPath)) {
            mkdir($folderPath, 0777, true);
        }

        StoryCodeFolder::create([
            'story_id' => $story->id,
            'folder_path' => $folderPath,
        ]);

        $response = $this->post(route('stories.code-folders.store', $story), [
            'folder_path' => $folderPath,
        ]);

        $response->assertSessionHas('warning');
        $this->assertCount(1, $story->codeFolders);

        rmdir($folderPath);
    }

    public function test_can_detach_code_folder()
    {
        $story = Story::factory()->create();
        $codeFolder = StoryCodeFolder::create([
            'story_id' => $story->id,
            'folder_path' => '/some/path',
        ]);

        $response = $this->delete(route('code-folders.destroy', $codeFolder));

        $response->assertRedirect();
        $this->assertDatabaseMissing('story_code_folders', ['id' => $codeFolder->id]);
    }
}
