<?php

namespace TheDevBacklog\Generators;

class ControllerGenerator implements GeneratorInterface
{
    /**
     * Generate a Laravel REST controller
     *
     * @param array $config Expected keys: name, model, modelVariable, routes
     * @return string
     */
    public function generate(array $config): string
    {
        $name = $config['name'] ?? 'ExampleController';
        $model = $config['model'] ?? 'Example';
        $modelVariable = $config['modelVariable'] ?? strtolower($model);
        $validationRules = $config['validationRules'] ?? [];

        $validationCode = $this->generateValidation($validationRules);

        return <<<PHP
<?php

namespace App\Http\Controllers;

use App\Models\\{$model};
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class {$name} extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        \${$modelVariable}s = {$model}::all();
        return response()->json(\${$modelVariable}s);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request \$request): JsonResponse
    {
        \$validated = \$request->validate({$validationCode});

        \${$modelVariable} = {$model}::create(\$validated);

        return response()->json(\${$modelVariable}, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string \$id): JsonResponse
    {
        \${$modelVariable} = {$model}::findOrFail(\$id);
        return response()->json(\${$modelVariable});
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request \$request, string \$id): JsonResponse
    {
        \$validated = \$request->validate({$validationCode});

        \${$modelVariable} = {$model}::findOrFail(\$id);
        \${$modelVariable}->update(\$validated);

        return response()->json(\${$modelVariable});
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string \$id): JsonResponse
    {
        \${$modelVariable} = {$model}::findOrFail(\$id);
        \${$modelVariable}->delete();

        return response()->json(null, 204);
    }
}

PHP;
    }

    /**
     * Generate validation rules array code
     *
     * @param array $rules
     * @return string
     */
    private function generateValidation(array $rules): string
    {
        if (empty($rules)) {
            return '[]';
        }

        $code = "[\n";
        foreach ($rules as $field => $rule) {
            $ruleStr = is_array($rule) ? "'" . implode('|', $rule) . "'" : "'{$rule}'";
            $code .= "            '{$field}' => {$ruleStr},\n";
        }
        $code .= "        ]";

        return $code;
    }

    /**
     * Get the controller filename
     *
     * @param array $config
     * @return string
     */
    public function getFilename(array $config): string
    {
        $name = $config['name'] ?? 'ExampleController';
        return "{$name}.php";
    }
}
