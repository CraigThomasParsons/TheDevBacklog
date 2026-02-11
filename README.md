# TheDevBacklog

The Backlog (Agent-Native, Agile-Compatible) : A Laravel code generator that automatically creates migrations, Eloquent models with relationships, REST controllers, seeders, and API routes.

## Features

- **Migration Generator**: Create Laravel migrations with fields, indexes, foreign keys, timestamps, and soft deletes
- **Model Generator**: Generate Eloquent models with fillable fields, relationships (hasMany, hasOne, belongsTo, belongsToMany), and soft deletes
- **Controller Generator**: Build REST API controllers with CRUD operations (index, store, show, update, destroy) and validation
- **Seeder Generator**: Create database seeders with custom data or factory support
- **Route Generator**: Generate API route definitions for resources

## Installation

```bash
composer install
```

For detailed usage instructions, see [USAGE.md](USAGE.md).

## Usage

### Using the CLI Tool (Recommended)

The easiest way to use the generator is with the CLI tool and a configuration file:

1. Copy the example config file:
```bash
cp config.example.php config.php
```

2. Edit `config.php` to define your resources

3. Run the generator:
```bash
php generate.php config.php
```

### Basic Example

```php
<?php

require_once 'vendor/autoload.php';

use TheDevBacklog\CodeGenerator;

$resource = [
    'model' => 'Product',
    'table' => 'products',
    'fields' => [
        ['name' => 'name', 'type' => 'string'],
        ['name' => 'price', 'type' => 'decimal:8,2'],
        ['name' => 'stock', 'type' => 'integer', 'default' => 0],
    ],
    'fillable' => ['name', 'price', 'stock'],
    'validationRules' => [
        'name' => 'required|string|max:255',
        'price' => 'required|numeric|min:0',
        'stock' => 'integer|min:0',
    ],
    'routeResource' => 'products',
];

$generator = new CodeGenerator('./output');
$result = $generator->generateMultipleResources([$resource]);
$writtenFiles = $generator->writeFiles($result);
```

### Advanced Example with Relationships

```php
$postResource = [
    'model' => 'Post',
    'table' => 'posts',
    'fields' => [
        ['name' => 'title', 'type' => 'string'],
        ['name' => 'content', 'type' => 'text'],
        ['name' => 'user_id', 'type' => 'foreignId', 'index' => true],
    ],
    'fillable' => ['title', 'content', 'user_id'],
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
    'validationRules' => [
        'title' => 'required|string|max:255',
        'content' => 'required|string',
        'user_id' => 'required|exists:users,id',
    ],
    'seederData' => [
        ['title' => 'First Post', 'content' => 'Content here', 'user_id' => 1],
    ],
];

$generator = new CodeGenerator('./output');
$result = $generator->generateMultipleResources([$postResource]);
$writtenFiles = $generator->writeFiles($result);
```

## Configuration Options

### Resource Configuration

| Option | Type | Description |
|--------|------|-------------|
| `model` | string | Model name (e.g., 'Post') |
| `table` | string | Database table name (e.g., 'posts') |
| `fields` | array | Array of field definitions |
| `fillable` | array | Fillable fields for mass assignment |
| `relationships` | array | Model relationships |
| `timestamps` | boolean | Include timestamps (default: true) |
| `softDeletes` | boolean | Include soft deletes (default: false) |
| `controller` | string | Controller name (default: ModelController) |
| `validationRules` | array | Validation rules for controller |
| `seeder` | string | Seeder name (default: ModelSeeder) |
| `seederData` | array | Seed data |
| `useFactory` | boolean | Use factory for seeding (default: false) |
| `factoryCount` | integer | Number of factory records (default: 10) |
| `routeResource` | string | API route resource name |

### Field Definition

| Option | Type | Description |
|--------|------|-------------|
| `name` | string | Field name |
| `type` | string | Laravel column type (string, text, integer, etc.) |
| `nullable` | boolean | Allow null values (default: false) |
| `default` | mixed | Default value |
| `unique` | boolean | Unique constraint (default: false) |
| `index` | boolean | Add index (default: false) |
| `foreign` | array | Foreign key definition |

### Relationship Types

- `hasOne`: One-to-one relationship
- `hasMany`: One-to-many relationship
- `belongsTo`: Inverse of hasOne/hasMany
- `belongsToMany`: Many-to-many relationship

## Examples

See the `examples/` directory for complete examples:

- `examples/generate_simple.php` - Basic product resource
- `examples/generate_blog.php` - Blog with posts and comments

Run examples:

```bash
php examples/generate_simple.php
php examples/generate_blog.php
```

Or use the CLI with the comprehensive config:

```bash
php generate.php config.example.php
```

## Output Structure

Generated files are organized in Laravel's standard structure:

```
output/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── PostController.php
│   │       └── CommentController.php
│   └── Models/
│       ├── Post.php
│       └── Comment.php
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_000000_create_posts_table.php
│   │   └── 2024_01_01_000001_create_comments_table.php
│   └── seeders/
│       ├── PostSeeder.php
│       └── CommentSeeder.php
└── routes/
    └── api.php
```

## License

MIT
