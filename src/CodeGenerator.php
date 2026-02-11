<?php

namespace TheDevBacklog;

use TheDevBacklog\Generators\MigrationGenerator;
use TheDevBacklog\Generators\ModelGenerator;
use TheDevBacklog\Generators\ControllerGenerator;
use TheDevBacklog\Generators\SeederGenerator;
use TheDevBacklog\Generators\RouteGenerator;

class CodeGenerator
{
    private MigrationGenerator $migrationGenerator;
    private ModelGenerator $modelGenerator;
    private ControllerGenerator $controllerGenerator;
    private SeederGenerator $seederGenerator;
    private RouteGenerator $routeGenerator;

    private string $outputDir;

    /**
     * @param string $outputDir Output directory path (relative to script execution or absolute)
     */
    public function __construct(string $outputDir = './output')
    {
        $this->migrationGenerator = new MigrationGenerator();
        $this->modelGenerator = new ModelGenerator();
        $this->controllerGenerator = new ControllerGenerator();
        $this->seederGenerator = new SeederGenerator();
        $this->routeGenerator = new RouteGenerator();
        $this->outputDir = $outputDir;
    }

    /**
     * Generate all Laravel files from a resource configuration
     *
     * @param array $resource Resource configuration
     * @return array Generated files
     */
    public function generateResource(array $resource): array
    {
        $generated = [];

        // Generate migration
        if (!isset($resource['skipMigration']) || !$resource['skipMigration']) {
            $migrationConfig = [
                'table' => $resource['table'],
                'fields' => $resource['fields'] ?? [],
                'timestamps' => $resource['timestamps'] ?? true,
                'softDeletes' => $resource['softDeletes'] ?? false,
            ];
            $generated['migration'] = [
                'filename' => $this->migrationGenerator->getFilename($migrationConfig),
                'content' => $this->migrationGenerator->generate($migrationConfig),
                'path' => 'database/migrations',
            ];
        }

        // Generate model
        if (!isset($resource['skipModel']) || !$resource['skipModel']) {
            $modelConfig = [
                'name' => $resource['model'],
                'table' => $resource['table'],
                'fillable' => $resource['fillable'] ?? [],
                'relationships' => $resource['relationships'] ?? [],
                'timestamps' => $resource['timestamps'] ?? true,
                'softDeletes' => $resource['softDeletes'] ?? false,
            ];
            $generated['model'] = [
                'filename' => $this->modelGenerator->getFilename($modelConfig),
                'content' => $this->modelGenerator->generate($modelConfig),
                'path' => 'app/Models',
            ];
        }

        // Generate controller
        if (!isset($resource['skipController']) || !$resource['skipController']) {
            $controllerConfig = [
                'name' => $resource['controller'] ?? $resource['model'] . 'Controller',
                'model' => $resource['model'],
                'modelVariable' => $resource['modelVariable'] ?? strtolower($resource['model']),
                'validationRules' => $resource['validationRules'] ?? [],
            ];
            $generated['controller'] = [
                'filename' => $this->controllerGenerator->getFilename($controllerConfig),
                'content' => $this->controllerGenerator->generate($controllerConfig),
                'path' => 'app/Http/Controllers',
            ];
        }

        // Generate seeder
        if (!isset($resource['skipSeeder']) || !$resource['skipSeeder']) {
            $seederConfig = [
                'name' => $resource['seeder'] ?? $resource['model'] . 'Seeder',
                'model' => $resource['model'],
                'data' => $resource['seederData'] ?? [],
                'useFactory' => $resource['useFactory'] ?? false,
                'factoryCount' => $resource['factoryCount'] ?? 10,
            ];
            $generated['seeder'] = [
                'filename' => $this->seederGenerator->getFilename($seederConfig),
                'content' => $this->seederGenerator->generate($seederConfig),
                'path' => 'database/seeders',
            ];
        }

        // Generate route (stored separately for aggregation)
        if (!isset($resource['skipRoute']) || !$resource['skipRoute']) {
            $routeConfig = [
                'resource' => $resource['routeResource'] ?? strtolower($resource['table']),
                'controller' => $resource['controller'] ?? $resource['model'] . 'Controller',
                'only' => $resource['routeOnly'] ?? null,
                'except' => $resource['routeExcept'] ?? null,
            ];
            $generated['route'] = [
                'content' => $this->routeGenerator->generate($routeConfig),
                'config' => $routeConfig,
            ];
        }

        return $generated;
    }

    /**
     * Generate multiple resources and aggregate routes
     *
     * @param array $resources Array of resource configurations
     * @return array All generated files
     */
    public function generateMultipleResources(array $resources): array
    {
        $allGenerated = [];
        $routeConfigs = [];

        foreach ($resources as $resource) {
            $generated = $this->generateResource($resource);
            $allGenerated[] = $generated;

            if (isset($generated['route'])) {
                $routeConfigs[] = $generated['route']['config'];
            }
        }

        // Generate complete api.php file
        if (!empty($routeConfigs)) {
            $allGenerated['api_routes'] = [
                'filename' => 'api.php',
                'content' => $this->routeGenerator->generateApiFile($routeConfigs),
                'path' => 'routes',
            ];
        }

        return $allGenerated;
    }

    /**
     * Write generated files to disk
     *
     * @param array $generated Generated files structure
     * @return array Written file paths
     */
    public function writeFiles(array $generated): array
    {
        $writtenFiles = [];

        foreach ($generated as $key => $data) {
            if ($key === 'api_routes') {
                $this->writeFile($data);
                $writtenFiles[] = $data['path'] . '/' . $data['filename'];
            } elseif (is_array($data) && isset($data[0])) {
                // Multiple resources
                foreach ($data as $resourceFiles) {
                    foreach ($resourceFiles as $type => $fileData) {
                        if ($type !== 'route' && isset($fileData['content'])) {
                            $this->writeFile($fileData);
                            $writtenFiles[] = $fileData['path'] . '/' . $fileData['filename'];
                        }
                    }
                }
            } else {
                // Single resource
                foreach ($data as $type => $fileData) {
                    if ($type !== 'route' && isset($fileData['content'])) {
                        $this->writeFile($fileData);
                        $writtenFiles[] = $fileData['path'] . '/' . $fileData['filename'];
                    }
                }
            }
        }

        return $writtenFiles;
    }

    /**
     * Write a single file to disk
     *
     * @param array $fileData File data with path, filename, and content
     */
    private function writeFile(array $fileData): void
    {
        $directory = $this->outputDir . '/' . $fileData['path'];
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filepath = $directory . '/' . $fileData['filename'];
        file_put_contents($filepath, $fileData['content']);
    }
}
