<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FileSystemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $path = $request->query('path', '/home/craigpar/Code');

        // Security/Sanity check: Ensure we are within allowed areas if needed.
        // For local dev tool, we might be lenient, but preventing traversal out of /home/craigpar is good practice.
        if (!str_starts_with($path, '/home/craigpar')) {
             $path = '/home/craigpar/Code';
        }

        if (!is_dir($path)) {
            return response()->json(['error' => 'Invalid directory: ' . $path], 404);
        }

        $directories = [];
        $scanned = scandir($path);

        foreach ($scanned as $node) {
            if (in_array($node, ['.', '..'])) {
                continue;
            }

            $fullPath = rtrim($path, '/') . '/' . $node;

            if (is_dir($fullPath)) {
                $directories[] = [
                    'name' => $node,
                    'path' => $fullPath,
                ];
            }
        }

        // Sort directories alphabetically
        usort($directories, fn($a, $b) => strcasecmp($a['name'], $b['name']));

        // Add parent directory option
        $parentPath = dirname($path);
        if (str_starts_with($parentPath, '/home/craigpar')) {
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
