<?php

require_once __DIR__ . '/../vendor/autoload.php';

use TheDevBacklog\CodeGenerator;

// Example: Generate a Blog Post resource with all components
$postResource = [
    'model' => 'Post',
    'table' => 'posts',
    'fields' => [
        ['name' => 'title', 'type' => 'string', 'nullable' => false],
        ['name' => 'slug', 'type' => 'string', 'unique' => true],
        ['name' => 'content', 'type' => 'text', 'nullable' => true],
        ['name' => 'published_at', 'type' => 'timestamp', 'nullable' => true],
        ['name' => 'user_id', 'type' => 'foreignId', 'nullable' => false, 'index' => true],
    ],
    'fillable' => ['title', 'slug', 'content', 'published_at', 'user_id'],
    'relationships' => [
        [
            'type' => 'belongsTo',
            'name' => 'user',
            'model' => 'User',
            'foreignKey' => 'user_id',
        ],
        [
            'type' => 'hasMany',
            'name' => 'comments',
            'model' => 'Comment',
        ],
    ],
    'timestamps' => true,
    'softDeletes' => true,
    'controller' => 'PostController',
    'validationRules' => [
        'title' => 'required|string|max:255',
        'slug' => 'required|string|unique:posts',
        'content' => 'nullable|string',
        'published_at' => 'nullable|date',
        'user_id' => 'required|exists:users,id',
    ],
    'seeder' => 'PostSeeder',
    'useFactory' => false,
    'seederData' => [
        [
            'title' => 'First Post',
            'slug' => 'first-post',
            'content' => 'This is the content of the first post.',
            'user_id' => 1,
        ],
        [
            'title' => 'Second Post',
            'slug' => 'second-post',
            'content' => 'This is the content of the second post.',
            'user_id' => 1,
        ],
    ],
    'routeResource' => 'posts',
];

// Example: Generate a Comment resource
$commentResource = [
    'model' => 'Comment',
    'table' => 'comments',
    'fields' => [
        ['name' => 'content', 'type' => 'text', 'nullable' => false],
        ['name' => 'post_id', 'type' => 'foreignId', 'nullable' => false, 'index' => true],
        ['name' => 'user_id', 'type' => 'foreignId', 'nullable' => false, 'index' => true],
    ],
    'fillable' => ['content', 'post_id', 'user_id'],
    'relationships' => [
        [
            'type' => 'belongsTo',
            'name' => 'post',
            'model' => 'Post',
            'foreignKey' => 'post_id',
        ],
        [
            'type' => 'belongsTo',
            'name' => 'user',
            'model' => 'User',
            'foreignKey' => 'user_id',
        ],
    ],
    'timestamps' => true,
    'softDeletes' => false,
    'controller' => 'CommentController',
    'validationRules' => [
        'content' => 'required|string',
        'post_id' => 'required|exists:posts,id',
        'user_id' => 'required|exists:users,id',
    ],
    'seeder' => 'CommentSeeder',
    'useFactory' => true,
    'factoryCount' => 20,
    'routeResource' => 'comments',
];

// Initialize the code generator
$generator = new CodeGenerator('./output');

// Generate all resources
echo "Generating Laravel resources...\n\n";

$result = $generator->generateMultipleResources([$postResource, $commentResource]);

// Write files to disk
$writtenFiles = $generator->writeFiles($result);

echo "Generated files:\n";
foreach ($writtenFiles as $file) {
    echo "  - $file\n";
}

echo "\nGeneration complete!\n";
