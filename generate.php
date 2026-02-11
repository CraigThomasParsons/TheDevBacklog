#!/usr/bin/env php
<?php

/**
 * CLI script to generate Laravel resources from a configuration file
 * 
 * Usage: php generate.php [config_file]
 * Example: php generate.php config.example.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use TheDevBacklog\CodeGenerator;

// Get config file from command line argument or use default
$configFile = $argv[1] ?? 'config.php';

// Validate config file path for security
$configFile = basename($configFile); // Prevent path traversal
if (!preg_match('/^[a-zA-Z0-9_.-]+\.php$/', $configFile)) {
    echo "Error: Invalid configuration file name. Only alphanumeric, dash, dot, and underscore are allowed.\n";
    exit(1);
}

if (!file_exists($configFile)) {
    echo "Error: Configuration file '{$configFile}' not found.\n";
    echo "Usage: php generate.php [config_file]\n";
    echo "Example: php generate.php config.example.php\n";
    exit(1);
}

// Load configuration
$config = require $configFile;

if (!isset($config['resources']) || !is_array($config['resources'])) {
    echo "Error: Configuration file must contain a 'resources' array.\n";
    exit(1);
}

// Get output directory from config or use default
$outputDir = $config['output_dir'] ?? './output';

// Initialize the code generator
$generator = new CodeGenerator($outputDir);

echo "Laravel Code Generator\n";
echo "======================\n\n";
echo "Config file: {$configFile}\n";
echo "Output directory: {$outputDir}\n";
echo "Resources to generate: " . count($config['resources']) . "\n\n";

// Generate all resources
$result = $generator->generateMultipleResources($config['resources']);

// Write files to disk
$writtenFiles = $generator->writeFiles($result);

echo "✓ Successfully generated " . count($writtenFiles) . " files:\n\n";
foreach ($writtenFiles as $file) {
    echo "  - {$file}\n";
}

echo "\nGeneration complete!\n";
