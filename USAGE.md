# Usage Guide

This guide shows you how to use the Laravel Code Generator in different scenarios.

## Quick Start

### 1. Install Dependencies

```bash
composer install
```

### 2. Choose Your Approach

You can use the generator in three ways:

#### Option A: CLI with Configuration File (Recommended)

1. Copy the example config:
```bash
cp config.example.php config.php
```

2. Edit `config.php` to define your resources

3. Generate:
```bash
php generate.php config.php
```

#### Option B: Run Example Scripts

```bash
php examples/generate_simple.php    # Simple product example
php examples/generate_blog.php      # Blog with relationships
php examples/generate_custom.php    # Custom programmatic usage
```

#### Option C: Use Programmatically

```php
<?php
require_once 'vendor/autoload.php';

use TheDevBacklog\CodeGenerator;

$generator = new CodeGenerator('./output');
$result = $generator->generateMultipleResources([...]);
$generator->writeFiles($result);
```

## Configuration Format

### Basic Resource

```php
[
    'model' => 'Product',
    'table' => 'products',
    'fields' => [
        ['name' => 'name', 'type' => 'string'],
        ['name' => 'price', 'type' => 'decimal:10,2'],
    ],
    'fillable' => ['name', 'price'],
    'routeResource' => 'products',
]
```

### With Relationships

```php
[
    'model' => 'Post',
    'table' => 'posts',
    'fields' => [
        ['name' => 'title', 'type' => 'string'],
        ['name' => 'user_id', 'type' => 'foreignId', 'index' => true],
    ],
    'fillable' => ['title', 'user_id'],
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
    'validationRules' => [
        'title' => 'required|string|max:255',
        'user_id' => 'required|exists:users,id',
    ],
]
```

## Field Types

All Laravel column types are supported:

| Type | Example | Description |
|------|---------|-------------|
| string | `'type' => 'string'` | VARCHAR column |
| text | `'type' => 'text'` | TEXT column |
| integer | `'type' => 'integer'` | INT column |
| bigInteger | `'type' => 'bigInteger'` | BIGINT column |
| decimal | `'type' => 'decimal:10,2'` | DECIMAL column with precision |
| boolean | `'type' => 'boolean'` | BOOLEAN/TINYINT column |
| date | `'type' => 'date'` | DATE column |
| datetime | `'type' => 'datetime'` | DATETIME column |
| timestamp | `'type' => 'timestamp'` | TIMESTAMP column |
| foreignId | `'type' => 'foreignId'` | Unsigned BIGINT for foreign keys |

## Field Options

```php
[
    'name' => 'email',
    'type' => 'string',
    'nullable' => true,      // Allow NULL values
    'unique' => true,        // Add unique constraint
    'index' => true,         // Add index
    'default' => 'value',    // Set default value
]
```

## Relationship Types

### belongsTo (Many-to-One)

```php
[
    'type' => 'belongsTo',
    'name' => 'user',
    'model' => 'User',
    'foreignKey' => 'user_id',  // Optional
]
```

### hasMany (One-to-Many)

```php
[
    'type' => 'hasMany',
    'name' => 'posts',
    'model' => 'Post',
    'foreignKey' => 'user_id',  // Optional
]
```

### hasOne (One-to-One)

```php
[
    'type' => 'hasOne',
    'name' => 'profile',
    'model' => 'Profile',
    'foreignKey' => 'user_id',  // Optional
]
```

### belongsToMany (Many-to-Many)

```php
[
    'type' => 'belongsToMany',
    'name' => 'roles',
    'model' => 'Role',
    'pivotTable' => 'role_user',  // Optional
]
```

## Validation Rules

Add validation rules for the controller:

```php
'validationRules' => [
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
    'age' => 'integer|min:18|max:100',
    'status' => 'required|in:active,inactive',
]
```

## Seeders

### With Data

```php
'seederData' => [
    ['name' => 'Product 1', 'price' => 99.99],
    ['name' => 'Product 2', 'price' => 149.99],
]
```

### With Factory

```php
'useFactory' => true,
'factoryCount' => 50,  // Number of records to create
```

## Advanced Features

### Skip Generation

Skip specific components:

```php
[
    'model' => 'User',
    'skipMigration' => true,   // Don't generate migration
    'skipController' => true,  // Don't generate controller
    'skipSeeder' => true,      // Don't generate seeder
    'skipRoute' => true,       // Don't add to routes
]
```

### Soft Deletes

Enable soft deletes:

```php
[
    'softDeletes' => true,
]
```

This adds:
- `deleted_at` column to migration
- `SoftDeletes` trait to model

### Timestamps

Control timestamp columns:

```php
[
    'timestamps' => true,  // Adds created_at and updated_at
]
```

### Route Options

Restrict route methods:

```php
// Only specific methods
'routeOnly' => ['index', 'show'],

// Exclude specific methods
'routeExcept' => ['destroy'],
```

## Output Structure

Generated files follow Laravel's structure:

```
output/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── ProductController.php
│   │       └── PostController.php
│   └── Models/
│       ├── Product.php
│       └── Post.php
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_000000_create_products_table.php
│   │   └── 2024_01_01_000001_create_posts_table.php
│   └── seeders/
│       ├── ProductSeeder.php
│       └── PostSeeder.php
└── routes/
    └── api.php
```

## Using Generated Files

1. Copy generated files to your Laravel project
2. Run migrations:
```bash
php artisan migrate
```

3. Run seeders (optional):
```bash
php artisan db:seed --class=ProductSeeder
```

4. Test your API endpoints:
```bash
# List all
GET /api/products

# Show one
GET /api/products/1

# Create
POST /api/products

# Update
PUT /api/products/1

# Delete
DELETE /api/products/1
```

## Security Notes

1. **Always validate external input** - Never use unsanitized user input in code generation
2. **Review generated code** - Always review generated code before using in production
3. **Use pagination** - The generated `index()` method uses `all()`. For large datasets, implement pagination
4. **Add authentication** - The generated controllers don't include authentication. Add middleware as needed

## Tips and Best Practices

1. **Start Simple** - Generate one resource, test it, then add complexity
2. **Use Relationships Wisely** - Define relationships from both sides for bidirectional access
3. **Keep Migrations Ordered** - Ensure parent tables are created before child tables
4. **Add Indexes** - Add indexes to foreign keys and frequently queried columns
5. **Customize After Generation** - Generated code is a starting point. Customize as needed
6. **Version Control** - Commit generated files to track changes

## Troubleshooting

### Generated files not found
- Check the output directory path
- Verify write permissions

### Migration syntax errors
- Review field types and parameters
- Check for duplicate field names

### Relationship errors
- Ensure related models exist
- Verify foreign key column names match

### Validation fails
- Check validation rule syntax
- Ensure rules match field types

## Examples

See the `examples/` directory for complete working examples:

- `generate_simple.php` - Basic product resource
- `generate_blog.php` - Blog with relationships
- `generate_custom.php` - Programmatic usage

## Need Help?

Check the main README.md for additional documentation and configuration options.
