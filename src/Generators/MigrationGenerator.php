<?php

namespace TheDevBacklog\Generators;

class MigrationGenerator implements GeneratorInterface
{
    /**
     * Generate a Laravel migration file
     *
     * @param array $config Expected keys: table, fields, timestamps, softDeletes
     * @return string
     */
    public function generate(array $config): string
    {
        $table = $config['table'] ?? 'example';
        $fields = $config['fields'] ?? [];
        $timestamps = $config['timestamps'] ?? true;
        $softDeletes = $config['softDeletes'] ?? false;
        $className = $this->getClassName($table);

        $fieldsCode = $this->generateFields($fields);
        $timestampsCode = $timestamps ? "\n            \$table->timestamps();" : '';
        $softDeletesCode = $softDeletes ? "\n            \$table->softDeletes();" : '';

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{$table}', function (Blueprint \$table) {
            \$table->id();{$fieldsCode}{$timestampsCode}{$softDeletesCode}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
};

PHP;
    }

    /**
     * Generate fields code for migration
     *
     * @param array $fields
     * @return string
     */
    private function generateFields(array $fields): string
    {
        $code = '';
        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'] ?? 'string';
            $nullable = $field['nullable'] ?? false;
            $default = $field['default'] ?? null;
            $unique = $field['unique'] ?? false;
            $index = $field['index'] ?? false;

            // Handle types with parameters (e.g., decimal:8,2)
            if (strpos($type, ':') !== false) {
                list($baseType, $params) = explode(':', $type, 2);
                $paramsList = explode(',', $params);
                $paramsStr = implode(', ', $paramsList);
                $line = "\n            \$table->{$baseType}('{$name}', {$paramsStr})";
            } else {
                $line = "\n            \$table->{$type}('{$name}')";
            }

            if ($nullable) {
                $line .= "->nullable()";
            }

            if ($default !== null) {
                $defaultValue = is_string($default) ? "'{$default}'" : $default;
                $line .= "->default({$defaultValue})";
            }

            if ($unique) {
                $line .= "->unique()";
            }

            if ($index) {
                $line .= "->index()";
            }

            $line .= ";";
            $code .= $line;
        }

        // Add foreign keys if specified
        foreach ($fields as $field) {
            if (isset($field['foreign'])) {
                $foreign = $field['foreign'];
                $code .= "\n            \$table->foreign('{$field['name']}')->references('{$foreign['references']}')->on('{$foreign['on']}')";
                if (isset($foreign['onDelete'])) {
                    $code .= "->onDelete('{$foreign['onDelete']}')";
                }
                if (isset($foreign['onUpdate'])) {
                    $code .= "->onUpdate('{$foreign['onUpdate']}')";
                }
                $code .= ";";
            }
        }

        return $code;
    }

    /**
     * Get the migration filename
     *
     * @param array $config
     * @return string
     */
    public function getFilename(array $config): string
    {
        $table = $config['table'] ?? 'example';
        $timestamp = date('Y_m_d_His');
        return "{$timestamp}_create_{$table}_table.php";
    }

    /**
     * Get the class name from table name
     *
     * @param string $table
     * @return string
     */
    private function getClassName(string $table): string
    {
        return 'Create' . str_replace('_', '', ucwords($table, '_')) . 'Table';
    }
}
