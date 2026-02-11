<?php

namespace TheDevBacklog\Generators;

class SeederGenerator implements GeneratorInterface
{
    /**
     * Generate a Laravel seeder
     *
     * @param array $config Expected keys: name, model, data
     * @return string
     */
    public function generate(array $config): string
    {
        $name = $config['name'] ?? 'ExampleSeeder';
        $model = $config['model'] ?? 'Example';
        $data = $config['data'] ?? [];
        $useFactory = $config['useFactory'] ?? false;
        $factoryCount = $config['factoryCount'] ?? 10;

        if ($useFactory) {
            $seedCode = "        {$model}::factory({$factoryCount})->create();";
        } else {
            $seedCode = $this->generateDataSeeding($model, $data);
        }

        return <<<PHP
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\\{$model};

class {$name} extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
{$seedCode}
    }
}

PHP;
    }

    /**
     * Generate data seeding code
     *
     * @param string $model
     * @param array $data
     * @return string
     */
    private function generateDataSeeding(string $model, array $data): string
    {
        if (empty($data)) {
            return "        // Add your seeding logic here\n        // {$model}::create([...]);";
        }

        $code = '';
        foreach ($data as $item) {
            $code .= "        {$model}::create([\n";
            foreach ($item as $key => $value) {
                $valueStr = is_string($value) ? "'{$value}'" : (is_bool($value) ? ($value ? 'true' : 'false') : $value);
                $code .= "            '{$key}' => {$valueStr},\n";
            }
            $code .= "        ]);\n\n";
        }

        return rtrim($code);
    }

    /**
     * Get the seeder filename
     *
     * @param array $config
     * @return string
     */
    public function getFilename(array $config): string
    {
        $name = $config['name'] ?? 'ExampleSeeder';
        return "{$name}.php";
    }
}
