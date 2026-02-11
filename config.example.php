<?php

/**
 * Example configuration file for generating Laravel resources
 * 
 * This file shows various configuration options for generating
 * migrations, models, controllers, seeders, and routes.
 */

return [
    // Define your resources here
    'resources' => [
        // Example 1: Simple Product resource
        [
            'model' => 'Product',
            'table' => 'products',
            'fields' => [
                ['name' => 'name', 'type' => 'string'],
                ['name' => 'description', 'type' => 'text', 'nullable' => true],
                ['name' => 'price', 'type' => 'decimal:10,2'],
                ['name' => 'stock', 'type' => 'integer', 'default' => 0],
                ['name' => 'is_active', 'type' => 'boolean', 'default' => true],
            ],
            'fillable' => ['name', 'description', 'price', 'stock', 'is_active'],
            'timestamps' => true,
            'softDeletes' => false,
            'validationRules' => [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'stock' => 'integer|min:0',
                'is_active' => 'boolean',
            ],
            'routeResource' => 'products',
        ],

        // Example 2: User resource (skip generation if using Laravel's default)
        // Uncomment to generate custom user model
        /*
        [
            'model' => 'User',
            'table' => 'users',
            'skipMigration' => true, // Skip if migration already exists
            'fields' => [],
            'fillable' => ['name', 'email', 'password'],
            'relationships' => [
                [
                    'type' => 'hasMany',
                    'name' => 'posts',
                    'model' => 'Post',
                ],
            ],
            'skipController' => true, // Skip controller for User
            'skipRoute' => true,
        ],
        */

        // Example 3: Post resource with relationships
        [
            'model' => 'Post',
            'table' => 'posts',
            'fields' => [
                ['name' => 'title', 'type' => 'string'],
                ['name' => 'slug', 'type' => 'string', 'unique' => true, 'index' => true],
                ['name' => 'content', 'type' => 'text', 'nullable' => true],
                ['name' => 'excerpt', 'type' => 'text', 'nullable' => true],
                ['name' => 'published_at', 'type' => 'timestamp', 'nullable' => true],
                ['name' => 'user_id', 'type' => 'foreignId', 'index' => true],
                ['name' => 'category_id', 'type' => 'foreignId', 'nullable' => true, 'index' => true],
            ],
            'fillable' => ['title', 'slug', 'content', 'excerpt', 'published_at', 'user_id', 'category_id'],
            'relationships' => [
                [
                    'type' => 'belongsTo',
                    'name' => 'user',
                    'model' => 'User',
                    'foreignKey' => 'user_id',
                ],
                [
                    'type' => 'belongsTo',
                    'name' => 'category',
                    'model' => 'Category',
                    'foreignKey' => 'category_id',
                ],
                [
                    'type' => 'hasMany',
                    'name' => 'comments',
                    'model' => 'Comment',
                ],
                [
                    'type' => 'belongsToMany',
                    'name' => 'tags',
                    'model' => 'Tag',
                    'pivotTable' => 'post_tag',
                ],
            ],
            'timestamps' => true,
            'softDeletes' => true,
            'validationRules' => [
                'title' => 'required|string|max:255',
                'slug' => 'required|string|unique:posts,slug',
                'content' => 'nullable|string',
                'excerpt' => 'nullable|string|max:500',
                'published_at' => 'nullable|date',
                'user_id' => 'required|exists:users,id',
                'category_id' => 'nullable|exists:categories,id',
            ],
            'seederData' => [
                [
                    'title' => 'Getting Started with Laravel',
                    'slug' => 'getting-started-with-laravel',
                    'content' => 'Laravel is a powerful PHP framework...',
                    'excerpt' => 'Learn the basics of Laravel',
                    'user_id' => 1,
                    'category_id' => 1,
                ],
            ],
            'routeResource' => 'posts',
        ],

        // Example 4: Category resource
        [
            'model' => 'Category',
            'table' => 'categories',
            'fields' => [
                ['name' => 'name', 'type' => 'string', 'unique' => true],
                ['name' => 'slug', 'type' => 'string', 'unique' => true],
                ['name' => 'description', 'type' => 'text', 'nullable' => true],
                ['name' => 'parent_id', 'type' => 'foreignId', 'nullable' => true],
            ],
            'fillable' => ['name', 'slug', 'description', 'parent_id'],
            'relationships' => [
                [
                    'type' => 'hasMany',
                    'name' => 'posts',
                    'model' => 'Post',
                ],
                [
                    'type' => 'belongsTo',
                    'name' => 'parent',
                    'model' => 'Category',
                    'foreignKey' => 'parent_id',
                ],
                [
                    'type' => 'hasMany',
                    'name' => 'children',
                    'model' => 'Category',
                    'foreignKey' => 'parent_id',
                ],
            ],
            'useFactory' => true,
            'factoryCount' => 5,
            'routeResource' => 'categories',
        ],

        // Example 5: Tag resource
        [
            'model' => 'Tag',
            'table' => 'tags',
            'fields' => [
                ['name' => 'name', 'type' => 'string', 'unique' => true],
                ['name' => 'slug', 'type' => 'string', 'unique' => true],
            ],
            'fillable' => ['name', 'slug'],
            'relationships' => [
                [
                    'type' => 'belongsToMany',
                    'name' => 'posts',
                    'model' => 'Post',
                    'pivotTable' => 'post_tag',
                ],
            ],
            'useFactory' => true,
            'factoryCount' => 10,
            'routeResource' => 'tags',
        ],

        // Example 6: Comment resource
        [
            'model' => 'Comment',
            'table' => 'comments',
            'fields' => [
                ['name' => 'content', 'type' => 'text'],
                ['name' => 'post_id', 'type' => 'foreignId', 'index' => true],
                ['name' => 'user_id', 'type' => 'foreignId', 'index' => true],
                ['name' => 'parent_id', 'type' => 'foreignId', 'nullable' => true],
                ['name' => 'is_approved', 'type' => 'boolean', 'default' => false],
            ],
            'fillable' => ['content', 'post_id', 'user_id', 'parent_id', 'is_approved'],
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
                [
                    'type' => 'belongsTo',
                    'name' => 'parent',
                    'model' => 'Comment',
                    'foreignKey' => 'parent_id',
                ],
                [
                    'type' => 'hasMany',
                    'name' => 'replies',
                    'model' => 'Comment',
                    'foreignKey' => 'parent_id',
                ],
            ],
            'timestamps' => true,
            'softDeletes' => true,
            'validationRules' => [
                'content' => 'required|string',
                'post_id' => 'required|exists:posts,id',
                'user_id' => 'required|exists:users,id',
                'parent_id' => 'nullable|exists:comments,id',
            ],
            'useFactory' => true,
            'factoryCount' => 20,
            'routeResource' => 'comments',
        ],
    ],

    // Output directory (relative to the script location)
    'output_dir' => './output',
];
