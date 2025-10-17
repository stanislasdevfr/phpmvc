<?php

namespace PhpMvc\Generators;

class EntityGenerator
{
    private string $projectPath;
    private string $entityName;
    private array $fields;

    public function __construct(string $projectPath, string $entityName, array $fields)
    {
        $this->projectPath = $projectPath;
        $this->entityName = $entityName;
        $this->fields = $fields;
    }

    public function generate(): void
    {
        $entityContent = $this->buildEntityClass();

        $filePath = $this->projectPath . '/src/Entity/' . $this->entityName . '.php';
        file_put_contents($filePath, $entityContent);
    }

    private function buildEntityClass(): string
    {
        $properties = $this->generateProperties();
        $constructor = $this->generateConstructor();
        $hydrate = $this->generateHydrateMethod();
        $getters = $this->generateGetters();
        $setters = $this->generateSetters();

        return <<<EOT
<?php

namespace App\Entity;

class {$this->entityName}
{
{$properties}

{$constructor}

{$hydrate}

{$getters}

{$setters}
}
EOT;
    }

    private function generateProperties(): string
    {
        $properties = "    private ?int \$id = null;\n";

        foreach ($this->fields as $field) {
            $phpType = $this->getPhpType($field['type']);
            $fieldName = $field['name'];
            $properties .= "    private {$phpType} \${$fieldName};\n";
        }

        return rtrim($properties);
    }

    private function generateConstructor(): string
    {
        return <<<'EOT'
    /**
     * Constructor
     */
    public function __construct()
    {
    }
EOT;
    }

    private function generateHydrateMethod(): string
    {
        return <<<'EOT'
    /**
     * Hydrate the entity from an array
     */
    public function hydrate(array $data): self
    {
        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        return $this;
    }
EOT;
    }

    private function generateGetters(): string
    {
        $getters = "    public function getId(): ?int\n";
        $getters .= "    {\n";
        $getters .= "        return \$this->id;\n";
        $getters .= "    }\n";

        foreach ($this->fields as $field) {
            $fieldName = $field['name'];
            $phpType = $this->getPhpType($field['type']);
            $methodName = 'get' . ucfirst($fieldName);

            $getters .= "\n";
            $getters .= "    public function {$methodName}(): {$phpType}\n";
            $getters .= "    {\n";
            $getters .= "        return \$this->{$fieldName};\n";
            $getters .= "    }\n";
        }

        return rtrim($getters);
    }

    private function generateSetters(): string
    {
        $setters = '';

        foreach ($this->fields as $index => $field) {
            $fieldName = $field['name'];
            $phpType = $this->getPhpType($field['type']);
            $methodName = 'set' . ucfirst($fieldName);

            if ($index > 0) {
                $setters .= "\n";
            }

            $setters .= "    public function {$methodName}({$phpType} \${$fieldName}): self\n";
            $setters .= "    {\n";
            $setters .= "        \$this->{$fieldName} = \${$fieldName};\n";
            $setters .= "        return \$this;\n";
            $setters .= "    }\n";
        }

        return rtrim($setters);
    }

    private function getPhpType(string $type): string
    {
        return match(strtolower($type)) {
            'int', 'integer' => 'int',
            'float', 'double' => 'float',
            'bool', 'boolean' => 'bool',
            'string', 'text' => 'string',
            'datetime', 'date' => '\DateTime',
            default => 'string'
        };
    }
}