<?php

namespace TheDevBacklog\Generators;

class RouteGenerator implements GeneratorInterface
{
    /**
     * Generate Laravel API routes
     *
     * @param array $config Expected keys: resource, controller, only, except
     * @return string
     */
    public function generate(array $config): string
    {
        $resource = $config['resource'] ?? 'examples';
        $controller = $config['controller'] ?? 'ExampleController';
        $only = $config['only'] ?? null;
        $except = $config['except'] ?? null;

        $routeLine = "Route::apiResource('{$resource}', {$controller}::class)";

        if ($only) {
            $onlyMethods = is_array($only) ? implode("', '", $only) : $only;
            $routeLine .= "->only(['{$onlyMethods}'])";
        }

        if ($except) {
            $exceptMethods = is_array($except) ? implode("', '", $except) : $except;
            $routeLine .= "->except(['{$exceptMethods}'])";
        }

        $routeLine .= ";";

        return $routeLine;
    }

    /**
     * Get the route filename (typically not used as routes are appended)
     *
     * @param array $config
     * @return string
     */
    public function getFilename(array $config): string
    {
        return 'api.php';
    }

    /**
     * Generate full api.php content with multiple routes
     *
     * @param array $routes Array of route configurations
     * @return string
     */
    public function generateApiFile(array $routes): string
    {
        $routeLines = '';
        $controllerUses = [];

        foreach ($routes as $routeConfig) {
            $controller = $routeConfig['controller'] ?? 'ExampleController';
            $controllerUses[$controller] = "use App\Http\Controllers\\{$controller};";
            $routeLines .= $this->generate($routeConfig) . "\n";
        }

        $useStatements = implode("\n", array_values($controllerUses));

        return <<<PHP
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
{$useStatements}

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request \$request) {
    return \$request->user();
});

{$routeLines}

PHP;
    }
}
