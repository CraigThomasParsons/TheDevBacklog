<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FileSystemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $codeRoot = realpath((string) config('app.code_root'));

        if ($codeRoot === false) {
            return response()->json(['error' => 'Configured code root is invalid.'], 404);
        }

        $requestedPath = (string) $request->query('path', $codeRoot);
        $path = realpath($requestedPath);

        // Resolve symlinks before enforcing the boundary so traversal cannot escape the configured root.
        if ($path === false || ($path !== $codeRoot && ! str_starts_with($path, $codeRoot.DIRECTORY_SEPARATOR))) {
            return response()->json(['error' => 'Invalid directory: '.$requestedPath], 404);
        }

        $directories = [];
        $scanned = scandir($path);

        foreach ($scanned as $node) {
            if (in_array($node, ['.', '..'])) {
                continue;
            }

            $fullPath = rtrim($path, '/').'/'.$node;

            if (is_dir($fullPath)) {
                $directories[] = [
                    'name' => $node,
                    'path' => $fullPath,
                ];
            }
        }

        // Sort directories alphabetically
        usort($directories, fn ($firstDirectory, $secondDirectory) => strcasecmp($firstDirectory['name'], $secondDirectory['name']));

        // Add parent directory option
        $parentPath = dirname($path);
        if ($path !== $codeRoot) {
            array_unshift($directories, [
                'name' => '..',
                'path' => $parentPath,
            ]);
        }

        return response()->json([
            'current_path' => $path,
            'directories' => $directories,
        ]);
    }
}
