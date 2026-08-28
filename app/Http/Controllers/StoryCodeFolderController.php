<?php

namespace App\Http\Controllers;

use App\Models\Story;
use App\Models\StoryCodeFolder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class StoryCodeFolderController extends Controller
{
    public function store(Request $request, Story $story): RedirectResponse
    {
        $validated = $request->validate([
            'folder_path' => 'required|string',
        ]);

        // Verify it actually exists on disk? Optional but good.
        if (!is_dir($validated['folder_path'])) {
            return back()->with('error', 'Directory does not exist on the server filesystem.');
        }

        // Prevent duplicates
        $exists = $story->codeFolders()->where('folder_path', $validated['folder_path'])->exists();
        if ($exists) {
            return back()->with('warning', 'This folder is already linked to the story.');
        }

        $story->codeFolders()->create([
            'folder_path' => $validated['folder_path'],
        ]);

        return back()->with('success', 'Code folder added successfully.');
    }

    public function destroy(StoryCodeFolder $codeFolder): RedirectResponse
    {
        $codeFolder->delete();
        return back()->with('success', 'Code folder removed.');
    }
}
