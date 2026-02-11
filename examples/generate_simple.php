<?php

require_once __DIR__ . '/../vendor/autoload.php';

use TheDevBacklog\CodeGenerator;

// Simple example: Generate a basic Product resource
$productResource = [
    'model' => 'Product',
    'table' => 'products',
    'fields' => [
        ['name' => 'name', 'type' => 'string'],
        ['name' => 'description', 'type' => 'text', 'nullable' => true],
        ['name' => 'price', 'type' => 'decimal:8,2'],
        ['name' => 'stock', 'type' => 'integer', 'default' => 0],
    ],
    'fillable' => ['name', 'description', 'price', 'stock'],
    'timestamps' => true,
    'validationRules' => [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'stock' => 'integer|min:0',
    ],
    'routeResource' => 'products',
];

$generator = new CodeGenerator('./output');

echo "Generating Product resource...\n\n";

$result = $generator->generateMultipleResources([$productResource]);
$writtenFiles = $generator->writeFiles($result);

echo "Generated files:\n";
foreach ($writtenFiles as $file) {
    echo "  - $file\n";
}

echo "\nGeneration complete!\n";
