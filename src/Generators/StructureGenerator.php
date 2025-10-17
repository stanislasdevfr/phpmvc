<?php

namespace PhpMvc\Generators;

class StructureGenerator
{
    private string $projectPath;
    private string $projectName;

    public function __construct(string $projectName)
    {
        $this->projectName = $projectName;
        $this->projectPath = getcwd() . '/' . $projectName;
    }

    public function generate(): void
    {
        $this->createMainDirectory();
        $this->createDirectories();
        $this->createBaseFiles();
    }

    private function createMainDirectory(): void
    {
        if (file_exists($this->projectPath)) {
            throw new \RuntimeException("The folder '{$this->projectName}' already exists!");
        }

        mkdir($this->projectPath, 0755, true);
    }

    private function createDirectories(): void
    {
        $directories = [
            'public',
            'src/Entity',
            'src/Controller',
            'src/Repository',
            'src/View',
            'src/Core',
            'config',
        ];

        foreach ($directories as $directory) {
            $fullPath = $this->projectPath . '/' . $directory;
            mkdir($fullPath, 0755, true);
        }
    }

    private function createBaseFiles(): void
    {
        $this->createComposerJson();
        $this->createGitignore();
        $this->createDatabaseConfig();
        $this->createIndexFile();
        $this->createReadme();
        $this->createRoutesFile();
        $this->copyCoreClasses();
    }

    private function createComposerJson(): void
    {
        $composerContent = [
            'name' => strtolower($this->projectName) . '/app',
            'description' => 'MVC project generated with PhpMvc',
            'type' => 'project',
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'src/'
                ]
            ],
            'require' => [
                'php' => '>=8.0'
            ]
        ];

        $jsonContent = json_encode($composerContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents(
            $this->projectPath . '/composer.json',
            $jsonContent
        );
    }

    private function createGitignore(): void
    {
        $gitignoreContent = <<<EOT
/vendor/
.DS_Store
.idea/
*.log
config/database.php
EOT;

        file_put_contents(
            $this->projectPath . '/.gitignore',
            $gitignoreContent
        );
    }

    private function createDatabaseConfig(): void
    {
        $databaseContent = <<<'EOT'
<?php

return [
    'host' => 'localhost',
    'database' => 'your_database_name',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
EOT;

        file_put_contents(
            $this->projectPath . '/config/database.php',
            $databaseContent
        );
    }

   private function createIndexFile(): void
{
    $indexContent = <<<'EOT'
<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load router configuration
$router = require __DIR__ . '/../config/routes.php';

// Dispatch the request
$router->dispatch();
EOT;

    file_put_contents(
        $this->projectPath . '/public/index.php',
        $indexContent
    );
}

    private function createReadme(): void
    {
        $readmeContent = <<<EOT
# {$this->projectName}
MVC project generated with PhpMvc
EOT;
        
        file_put_contents(
            $this->projectPath . '/README.md',
            $readmeContent
        );
    }

    private function createRoutesFile(): void
{
    $routesContent = <<<'EOT'
<?php

use App\Core\Router;

$router = new Router();

// Add your routes here
// Example:
// $router->get('/users', 'UserController', 'index');
// $router->get('/users/{id}', 'UserController', 'show');

// Or use resource for automatic CRUD routes:
// $router->resource('users', 'UserController');

return $router;
EOT;

    file_put_contents(
        $this->projectPath . '/config/routes.php',
        $routesContent
    );
}

 private function copyCoreClasses(): void
{
    $templatePath = __DIR__ . '/../Templates/Core';
    $destinationPath = $this->projectPath . '/src/Core';

    $coreFiles = [
        'Database.php',
        'Hydrator.php',
        'Router.php',
        'Session.php',        // ← NOUVEAU
        'AuthMiddleware.php'  // ← NOUVEAU
    ];

    foreach ($coreFiles as $file) {
        $source = $templatePath . '/' . $file;
        $destination = $destinationPath . '/' . $file;

        if (file_exists($source)) {
            copy($source, $destination);
        }
    }
}

    public function getProjectPath(): string
    {
        return $this->projectPath;
    }
}