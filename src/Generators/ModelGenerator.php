<?php

namespace TheDevBacklog\Generators;

class ModelGenerator implements GeneratorInterface
{
    /**
     * Generate a Laravel Eloquent model
     *
     * @param array $config Expected keys: name, table, fillable, relationships, timestamps, softDeletes
     * @return string
     */
    public function generate(array $config): string
    {
        $name = $config['name'] ?? 'Example';
        $table = $config['table'] ?? strtolower($name) . 's';
        $fillable = $config['fillable'] ?? [];
        $relationships = $config['relationships'] ?? [];
        $timestamps = $config['timestamps'] ?? true;
        $softDeletes = $config['softDeletes'] ?? false;

        $fillableCode = $this->generateFillable($fillable);
        $relationshipsCode = $this->generateRelationships($relationships);
        $useStatements = $softDeletes ? "use Illuminate\Database\Eloquent\SoftDeletes;\n" : '';
        $traits = $softDeletes ? "\n    use SoftDeletes;" : '';
        $timestampsProperty = !$timestamps ? "\n    public \$timestamps = false;" : '';

        return <<<PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
{$useStatements}
class {$name} extends Model
{
    use HasFactory;{$traits}

    protected \$table = '{$table}';
{$fillableCode}{$timestampsProperty}
{$relationshipsCode}}

PHP;
    }

    /**
     * Generate fillable property
     *
     * @param array $fillable
     * @return string
     */
    private function generateFillable(array $fillable): string
    {
        if (empty($fillable)) {
            return '';
        }

        $fields = array_map(function($field) {
            return "        '{$field}'";
        }, $fillable);

        return "\n    protected \$fillable = [\n" . implode(",\n", $fields) . ",\n    ];";
    }

    /**
     * Generate relationship methods
     *
     * @param array $relationships
     * @return string
     */
    private function generateRelationships(array $relationships): string
    {
        if (empty($relationships)) {
            return '';
        }

        $code = '';
        foreach ($relationships as $relationship) {
            $type = $relationship['type'] ?? 'belongsTo';
            $name = $relationship['name'];
            $model = $relationship['model'];
            $foreignKey = $relationship['foreignKey'] ?? null;
            $localKey = $relationship['localKey'] ?? null;

            $code .= "\n    /**\n";
            $code .= "     * Get the {$name}\n";
            $code .= "     */\n";
            $code .= "    public function {$name}()\n";
            $code .= "    {\n";

            switch ($type) {
                case 'hasMany':
                    if ($foreignKey) {
                        $code .= "        return \$this->hasMany({$model}::class, '{$foreignKey}');\n";
                    } else {
                        $code .= "        return \$this->hasMany({$model}::class);\n";
                    }
                    break;

                case 'hasOne':
                    if ($foreignKey) {
                        $code .= "        return \$this->hasOne({$model}::class, '{$foreignKey}');\n";
                    } else {
                        $code .= "        return \$this->hasOne({$model}::class);\n";
                    }
                    break;

                case 'belongsTo':
                    if ($foreignKey) {
                        $code .= "        return \$this->belongsTo({$model}::class, '{$foreignKey}');\n";
                    } else {
                        $code .= "        return \$this->belongsTo({$model}::class);\n";
                    }
                    break;

                case 'belongsToMany':
                    $pivotTable = $relationship['pivotTable'] ?? null;
                    if ($pivotTable) {
                        $code .= "        return \$this->belongsToMany({$model}::class, '{$pivotTable}');\n";
                    } else {
                        $code .= "        return \$this->belongsToMany({$model}::class);\n";
                    }
                    break;
            }

            $code .= "    }\n";
        }

        return $code;
    }

    /**
     * Get the model filename
     *
     * @param array $config
     * @return string
     */
    public function getFilename(array $config): string
    {
        $name = $config['name'] ?? 'Example';
        return "{$name}.php";
    }
}
