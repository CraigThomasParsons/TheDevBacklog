<?php

/**
 * Advanced example: Custom workflow for generating resources programmatically
 * 
 * This example shows how to use the generator in a more flexible way,
 * with custom logic and conditional generation.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use TheDevBacklog\CodeGenerator;

// Initialize the generator
$generator = new CodeGenerator('./output');

// Example: Generate resources based on user input or external data
// WARNING: Always validate and sanitize external input before using in code generation
// to prevent code injection vulnerabilities. Never trust user input directly.
$resourceName = 'Order'; // This could come from user input, database, API, etc.
$fields = [
    ['name' => 'order_number', 'type' => 'string', 'unique' => true],
    ['name' => 'customer_id', 'type' => 'foreignId', 'index' => true],
    ['name' => 'total_amount', 'type' => 'decimal:10,2'],
    ['name' => 'status', 'type' => 'string', 'default' => 'pending'],
    ['name' => 'notes', 'type' => 'text', 'nullable' => true],
];

$resource = [
    'model' => $resourceName,
    'table' => strtolower($resourceName) . 's',
    'fields' => $fields,
    'fillable' => array_column($fields, 'name'),
    'relationships' => [
        [
            'type' => 'belongsTo',
            'name' => 'customer',
            'model' => 'Customer',
            'foreignKey' => 'customer_id',
        ],
        [
            'type' => 'hasMany',
            'name' => 'items',
            'model' => 'OrderItem',
        ],
    ],
    'timestamps' => true,
    'validationRules' => [
        'order_number' => 'required|string|unique:orders',
        'customer_id' => 'required|exists:customers,id',
        'total_amount' => 'required|numeric|min:0',
        'status' => 'required|in:pending,processing,completed,cancelled',
        'notes' => 'nullable|string',
    ],
    'routeResource' => strtolower($resourceName) . 's',
];

echo "Generating {$resourceName} resource...\n\n";

// Generate just this resource
$generated = $generator->generateResource($resource);

// You can inspect or modify the generated content before writing
echo "Generated components:\n";
foreach ($generated as $type => $data) {
    if (isset($data['filename'])) {
        echo "  - {$type}: {$data['path']}/{$data['filename']}\n";
        
        // Example: You could modify the content here if needed
        // $data['content'] = str_replace('something', 'something_else', $data['content']);
    }
}

// Write files to disk
$writtenFiles = [];
foreach ($generated as $type => $data) {
    if ($type !== 'route' && isset($data['content'])) {
        $directory = './output/' . $data['path'];
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $filepath = $directory . '/' . $data['filename'];
        file_put_contents($filepath, $data['content']);
        $writtenFiles[] = $data['path'] . '/' . $data['filename'];
    }
}

// Handle route separately if needed
if (isset($generated['route'])) {
    echo "\nRoute definition (add to routes/api.php):\n";
    echo $generated['route']['content'] . "\n";
}

echo "\n✓ Successfully generated " . count($writtenFiles) . " files\n";

// Example: Generate OrderItem as a related resource
$orderItemResource = [
    'model' => 'OrderItem',
    'table' => 'order_items',
    'fields' => [
        ['name' => 'order_id', 'type' => 'foreignId', 'index' => true],
        ['name' => 'product_id', 'type' => 'foreignId', 'index' => true],
        ['name' => 'quantity', 'type' => 'integer'],
        ['name' => 'unit_price', 'type' => 'decimal:10,2'],
        ['name' => 'subtotal', 'type' => 'decimal:10,2'],
    ],
    'fillable' => ['order_id', 'product_id', 'quantity', 'unit_price', 'subtotal'],
    'relationships' => [
        [
            'type' => 'belongsTo',
            'name' => 'order',
            'model' => 'Order',
            'foreignKey' => 'order_id',
        ],
        [
            'type' => 'belongsTo',
            'name' => 'product',
            'model' => 'Product',
            'foreignKey' => 'product_id',
        ],
    ],
    'validationRules' => [
        'order_id' => 'required|exists:orders,id',
        'product_id' => 'required|exists:products,id',
        'quantity' => 'required|integer|min:1',
        'unit_price' => 'required|numeric|min:0',
        'subtotal' => 'required|numeric|min:0',
    ],
    'routeResource' => 'order-items',
];

echo "\nGenerating OrderItem resource...\n";
$result = $generator->generateMultipleResources([$orderItemResource]);
$generator->writeFiles($result);

echo "✓ OrderItem resource generated\n";

echo "\nAll resources generated successfully!\n";
