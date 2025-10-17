<?php

namespace PhpMvc\Generators;

class ControllerGenerator
{
    private string $projectPath;
    private string $entityName;
    private array $fields;
    private bool $withBootstrap;

    public function __construct(string $projectPath, string $entityName, array $fields, bool $withBootstrap = false)
    {
        $this->projectPath = $projectPath;
        $this->entityName = $entityName;
        $this->fields = $fields;
        $this->withBootstrap = $withBootstrap;
    }

    public function generate(): void
    {
        $controllerContent = $this->buildControllerClass();

        $filePath = $this->projectPath . '/src/Controller/' . $this->entityName . 'Controller.php';
        file_put_contents($filePath, $controllerContent);
    }

    private function buildControllerClass(): string
    {
        $entityLower = strtolower($this->entityName);
        $validation = $this->generateValidation();
        
        $indexMethod = $this->withBootstrap ? $this->generateHybridIndex() : $this->generateApiIndex();
        $showMethod = $this->withBootstrap ? $this->generateHybridShow() : $this->generateApiShow();
        
        // Protection partielle : seulement pour les actions d'écriture
        $authProtection = '';
        if ($this->withBootstrap) {
            $authProtection = <<<'AUTH'

        // Require authentication for write operations
        if (class_exists('\\App\\Core\\AuthMiddleware')) {
            \App\Core\AuthMiddleware::requireAuth();
        }
AUTH;
        }

        return <<<EOT
<?php

namespace App\Controller;

use App\Entity\\{$this->entityName};
use App\Repository\\{$this->entityName}Repository;
use App\Core\Hydrator;

class {$this->entityName}Controller
{
    private {$this->entityName}Repository \$repository;

    public function __construct()
    {
        \$this->repository = new {$this->entityName}Repository();
    }

{$indexMethod}

{$showMethod}

    /**
     * Show creation form (or return schema for API)
     */
    public function create(): void
    {
        // For API usage: return expected fields
        header('Content-Type: application/json');
        echo json_encode([
            'fields' => [
{$this->generateFieldsSchema()}
            ]
        ]);
    }

    /**
     * Store a new {$entityLower}
     */
    public function store(): void
    {{$authProtection}
        // Validation
{$validation}
        
        if (!empty(\$errors)) {
            http_response_code(400);
            echo json_encode(['errors' => \$errors]);
            return;
        }
        
        // Create entity
        \${$entityLower} = new {$this->entityName}();
        \${$entityLower}->hydrate(\$_POST);
        
        // Save
        \$this->repository->save(\${$entityLower});
        
        http_response_code(201);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'id' => \${$entityLower}->getId(),
            'message' => '{$this->entityName} created successfully'
        ]);
    }

    /**
     * Show edit form (or return current data for API)
     */
    public function edit(\$id): void
    {
        \${$entityLower} = \$this->repository->findById((int)\$id);
        
        if (!\${$entityLower}) {
            http_response_code(404);
            echo json_encode(['error' => '{$this->entityName} not found']);
            return;
        }
        
        header('Content-Type: application/json');
        echo json_encode(Hydrator::extract(\${$entityLower}));
    }

    /**
     * Update a {$entityLower}
     */
    public function update(\$id): void
    {{$authProtection}
        \${$entityLower} = \$this->repository->findById((int)\$id);
        
        if (!\${$entityLower}) {
            http_response_code(404);
            echo json_encode(['error' => '{$this->entityName} not found']);
            return;
        }
        
        // Validation
{$validation}
        
        if (!empty(\$errors)) {
            http_response_code(400);
            echo json_encode(['errors' => \$errors]);
            return;
        }
        
        // Update entity
        \${$entityLower}->hydrate(\$_POST);
        
        // Save
        \$this->repository->save(\${$entityLower});
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => '{$this->entityName} updated successfully'
        ]);
    }

    /**
     * Delete a {$entityLower}
     */
    public function delete(\$id): void
    {{$authProtection}
        \${$entityLower} = \$this->repository->findById((int)\$id);
        
        if (!\${$entityLower}) {
            http_response_code(404);
            echo json_encode(['error' => '{$this->entityName} not found']);
            return;
        }
        
        \$this->repository->delete((int)\$id);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => '{$this->entityName} deleted successfully'
        ]);
    }

    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest(): bool
    {
        return !empty(\$_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower(\$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
EOT;
    }

    private function generateHybridIndex(): string
    {
        $entityLower = strtolower($this->entityName);

        return <<<EOT
    /**
     * List all {$entityLower}s (public access)
     */
    public function index(): void
    {
        \${$entityLower}s = \$this->repository->findAll();
        
        // If AJAX request, return JSON
        if (\$this->isAjaxRequest()) {
            \$data = array_map(function(\${$entityLower}) {
                return Hydrator::extract(\${$entityLower});
            }, \${$entityLower}s);
            
            header('Content-Type: application/json');
            echo json_encode(\$data);
            return;
        }
        
        // Otherwise, render HTML view
        require __DIR__ . '/../View/{$entityLower}_index.php';
    }
EOT;
    }

    private function generateApiIndex(): string
    {
        $entityLower = strtolower($this->entityName);
        
        return <<<EOT
    /**
     * List all {$entityLower}s
     */
    public function index(): void
    {
        \${$entityLower}s = \$this->repository->findAll();
        
        \$data = array_map(function(\${$entityLower}) {
            return Hydrator::extract(\${$entityLower});
        }, \${$entityLower}s);
        
        header('Content-Type: application/json');
        echo json_encode(\$data);
    }
EOT;
    }

    private function generateHybridShow(): string
    {
        $entityLower = strtolower($this->entityName);
        
        return <<<EOT
    /**
     * Show a single {$entityLower} (public access)
     */
    public function show(\$id): void
    {
        \${$entityLower} = \$this->repository->findById((int)\$id);
        
        if (!\${$entityLower}) {
            http_response_code(404);
            
            if (\$this->isAjaxRequest()) {
                echo json_encode(['error' => '{$this->entityName} not found']);
            } else {
                echo "404 - {$this->entityName} not found";
            }
            return;
        }
        
        // Always return JSON for show (used by AJAX)
        header('Content-Type: application/json');
        echo json_encode(Hydrator::extract(\${$entityLower}));
    }
EOT;
    }

    private function generateApiShow(): string
    {
        $entityLower = strtolower($this->entityName);
        
        return <<<EOT
    /**
     * Show a single {$entityLower}
     */
    public function show(\$id): void
    {
        \${$entityLower} = \$this->repository->findById((int)\$id);
        
        if (!\${$entityLower}) {
            http_response_code(404);
            echo json_encode(['error' => '{$this->entityName} not found']);
            return;
        }
        
        header('Content-Type: application/json');
        echo json_encode(Hydrator::extract(\${$entityLower}));
    }
EOT;
    }

    private function generateValidation(): string
    {
        $validation = "        \$errors = [];\n";
        
        foreach ($this->fields as $field) {
            $fieldName = $field['name'];
            $fieldType = $field['type'];
            
            $validation .= "        \n";
            
            // Required validation
            $validation .= "        if (empty(\$_POST['{$fieldName}'])) {\n";
            $validation .= "            \$errors[] = \"Field '{$fieldName}' is required\";\n";
            $validation .= "        }";
            
            // Type-specific validation
            switch (strtolower($fieldType)) {
                case 'int':
                case 'integer':
                    $validation .= " elseif (!is_numeric(\$_POST['{$fieldName}'])) {\n";
                    $validation .= "            \$errors[] = \"Field '{$fieldName}' must be a number\";\n";
                    $validation .= "        }";
                    break;
                    
                case 'float':
                case 'double':
                    $validation .= " elseif (!is_numeric(\$_POST['{$fieldName}'])) {\n";
                    $validation .= "            \$errors[] = \"Field '{$fieldName}' must be a number\";\n";
                    $validation .= "        }";
                    break;
                    
                case 'string':
                case 'text':
                    // Special case for email
                    if (strpos($fieldName, 'email') !== false) {
                        $validation .= " elseif (!filter_var(\$_POST['{$fieldName}'], FILTER_VALIDATE_EMAIL)) {\n";
                        $validation .= "            \$errors[] = \"Field '{$fieldName}' must be a valid email\";\n";
                        $validation .= "        }";
                    } else {
                        $validation .= " elseif (strlen(\$_POST['{$fieldName}']) > 255) {\n";
                        $validation .= "            \$errors[] = \"Field '{$fieldName}' must not exceed 255 characters\";\n";
                        $validation .= "        }";
                    }
                    break;
            }
            
            $validation .= "\n";
        }
        
        return $validation;
    }

    private function generateFieldsSchema(): string
    {
        $schema = '';
        
        foreach ($this->fields as $index => $field) {
            $fieldName = $field['name'];
            $fieldType = $field['type'];
            
            $schema .= "                '{$fieldName}' => '{$fieldType}'";
            
            if ($index < count($this->fields) - 1) {
                $schema .= ",\n";
            }
        }
        
        return $schema;
    }
}