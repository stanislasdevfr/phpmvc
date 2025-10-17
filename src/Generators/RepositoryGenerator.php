<?php

namespace PhpMvc\Generators;

class RepositoryGenerator
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
        $repositoryContent = $this->buildRepositoryClass();

        $filePath = $this->projectPath . '/src/Repository/' . $this->entityName . 'Repository.php';
        file_put_contents($filePath, $repositoryContent);
    }

    private function buildRepositoryClass(): string
    {
        $tableName = strtolower($this->entityName) . 's';

        return <<<EOT
<?php

namespace App\Repository;

use App\Core\Database;
use App\Core\Hydrator;
use App\Entity\\{$this->entityName};

class {$this->entityName}Repository
{
    private Database \$db;
    private string \$table = '{$tableName}';

    public function __construct()
    {
        \$this->db = Database::getInstance();
    }

    /**
     * Find all records
     */
    public function findAll(): array
    {
        \$sql = "SELECT * FROM {\$this->table}";
        \$data = \$this->db->fetchAll(\$sql);
        
        return Hydrator::hydrateMultiple({$this->entityName}::class, \$data);
    }

    /**
     * Find a record by ID
     */
    public function findById(int \$id): ?{$this->entityName}
    {
        \$sql = "SELECT * FROM {\$this->table} WHERE id = ?";
        \$data = \$this->db->fetch(\$sql, [\$id]);
        
        if (!\$data) {
            return null;
        }
        
        return Hydrator::hydrate(new {$this->entityName}(), \$data);
    }

    /**
     * Save (create or update) a record
     */
    public function save({$this->entityName} \$entity): void
    {
        \$data = Hydrator::extract(\$entity);
        
        if (\$entity->getId()) {
            // UPDATE
            \$id = \$entity->getId();
            unset(\$data['id']);
            
            \$fields = [];
            foreach (array_keys(\$data) as \$key) {
                \$fields[] = "\$key = ?";
            }
            
            \$sql = "UPDATE {\$this->table} SET " . implode(', ', \$fields) . " WHERE id = ?";
            \$values = array_values(\$data);
            \$values[] = \$id;
            
            \$this->db->execute(\$sql, \$values);
        } else {
            // INSERT
            unset(\$data['id']);
            
            \$columns = implode(', ', array_keys(\$data));
            \$placeholders = implode(', ', array_fill(0, count(\$data), '?'));
            
            \$sql = "INSERT INTO {\$this->table} (\$columns) VALUES (\$placeholders)";
            \$this->db->execute(\$sql, array_values(\$data));
            
            // Update entity ID with the auto-incremented value
            \$reflection = new \ReflectionClass(\$entity);
            \$idProperty = \$reflection->getProperty('id');
            \$idProperty->setAccessible(true);
            \$idProperty->setValue(\$entity, (int)\$this->db->lastInsertId());
        }
    }

    /**
     * Delete a record by ID
     */
    public function delete(int \$id): void
    {
        \$sql = "DELETE FROM {\$this->table} WHERE id = ?";
        \$this->db->execute(\$sql, [\$id]);
    }

    /**
     * Count total records
     */
    public function count(): int
    {
        \$sql = "SELECT COUNT(*) as total FROM {\$this->table}";
        \$result = \$this->db->fetch(\$sql);
        return (int)\$result['total'];
    }
}
EOT;
    }
}