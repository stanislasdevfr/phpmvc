<?php

namespace PhpMvc\Generators;

class RoutesGenerator
{
    private string $projectPath;
    private array $entities;
    private bool $withAuth;

    public function __construct(string $projectPath, array $entities, bool $withAuth = false)
    {
        $this->projectPath = $projectPath;
        $this->entities = $entities;
        $this->withAuth = $withAuth;
    }

    public function generate(): void
    {
        $routesContent = $this->buildRoutesFile();

        $filePath = $this->projectPath . '/config/routes.php';
        file_put_contents($filePath, $routesContent);
    }

    private function buildRoutesFile(): string
    {
        $routes = '';
        
        // Home route - redirect to first entity
        if (!empty($this->entities)) {
            $firstEntity = $this->entities[0];
            $firstEntityName = $firstEntity['name'];
            $firstEntityLower = strtolower($firstEntityName);
            
            $routes .= "// Home route - redirect to first entity\n";
            $routes .= "\$router->get('/', '{$firstEntityName}Controller', 'index');\n";
            $routes .= "\n";
        }

        // Auth routes if enabled
        if ($this->withAuth) {
            $routes .= "// Authentication routes\n";
            $routes .= "\$router->get('/login', 'AuthController', 'login');\n";
            $routes .= "\$router->post('/login', 'AuthController', 'login');\n";
            $routes .= "\$router->get('/register', 'AuthController', 'register');\n";
            $routes .= "\$router->post('/register', 'AuthController', 'register');\n";
            $routes .= "\$router->get('/logout', 'AuthController', 'logout');\n";
            $routes .= "\$router->get('/auth/check', 'AuthController', 'check');\n";
            $routes .= "\n";
        }

        // Entity routes
        foreach ($this->entities as $entity) {
            $entityName = $entity['name'];
            $entityLower = strtolower($entityName);
            
            $routes .= "// {$entityName} routes\n";
            $routes .= "\$router->resource('{$entityLower}s', '{$entityName}Controller');\n";
            $routes .= "\n";
        }

        return <<<EOT
<?php

use App\Core\Router;

\$router = new Router();

// Auto-generated routes
{$routes}
// You can add custom routes here
// Example:
// \$router->get('/custom-path', 'CustomController', 'method');

return \$router;
EOT;
    }
}