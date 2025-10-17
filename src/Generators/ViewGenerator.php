<?php

namespace PhpMvc\Generators;

class ViewGenerator
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
        $this->createIndexView();
    }

    private function createIndexView(): void
    {
        $entityLower = strtolower($this->entityName);
        $entityPlural = $entityLower . 's';
        
        $tableHeaders = $this->generateTableHeaders();
        $tableColumns = $this->generateTableColumns();
        $formFields = $this->generateFormFields();
        $detailFields = $this->generateDetailFields();

        $viewContent = <<<EOT
<?php
\$title = '{$this->entityName} Management';
\$menu = []; // Will be populated dynamically

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-list-ul"></i> {$this->entityName} List</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-plus-lg"></i> New {$this->entityName}
    </button>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="{$entityLower}Table">
                <thead class="table-light">
                    <tr>
{$tableHeaders}
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be loaded via JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create {$this->entityName}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createForm">
                <div class="modal-body">
{$formFields}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit {$this->entityName}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editForm">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
{$formFields}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{$this->entityName} Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
{$detailFields}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete {$this->entityName}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this {$entityLower}?</p>
                <p class="text-danger"><strong>This action cannot be undone.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentDeleteId = null;

// Load data on page load
document.addEventListener('DOMContentLoaded', function() {
    load{$this->entityName}s();
});

// Load all {$entityPlural}
function load{$this->entityName}s() {
    fetch('/{$entityPlural}', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        const tbody = document.querySelector('#{$entityLower}Table tbody');
        tbody.innerHTML = '';
        
        data.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = \`
{$tableColumns}
                <td>
                    <button class="btn btn-sm btn-info" onclick="view{$this->entityName}(\${item.id})" title="View">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="edit{$this->entityName}(\${item.id})" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="confirmDelete{$this->entityName}(\${item.id})" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            \`;
            tbody.appendChild(row);
        });
    })
    .catch(error => {
        console.error('Error loading {$entityPlural}:', error);
        showAlert('danger', 'Error loading data');
    });
}

// Create {$entityLower}
document.getElementById('createForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/{$entityPlural}', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('createModal')).hide();
            this.reset();
            load{$this->entityName}s();
            showAlert('success', '{$this->entityName} created successfully');
        } else {
            showAlert('danger', data.errors ? data.errors.join('<br>') : 'Error creating {$entityLower}');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Error creating {$entityLower}');
    });
});

// View {$entityLower}
function view{$this->entityName}(id) {
    fetch(\`/{$entityPlural}/\${id}\`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            showAlert('danger', data.error);
            return;
        }
        
{$this->generateViewScript()}
        
        new bootstrap.Modal(document.getElementById('viewModal')).show();
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Error loading {$entityLower}');
    });
}

// Edit {$entityLower}
function edit{$this->entityName}(id) {
    fetch(\`/{$entityPlural}/\${id}\`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            showAlert('danger', data.error);
            return;
        }
        
        document.getElementById('edit_id').value = data.id;
{$this->generateEditScript()}
        
        new bootstrap.Modal(document.getElementById('editModal')).show();
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Error loading {$entityLower}');
    });
}

// Update {$entityLower}
document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const id = document.getElementById('edit_id').value;
    const formData = new FormData(this);
    formData.append('_method', 'PUT');
    
    fetch(\`/{$entityPlural}/\${id}\`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            load{$this->entityName}s();
            showAlert('success', '{$this->entityName} updated successfully');
        } else {
            showAlert('danger', data.errors ? data.errors.join('<br>') : 'Error updating {$entityLower}');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Error updating {$entityLower}');
    });
});

// Confirm delete
function confirmDelete{$this->entityName}(id) {
    currentDeleteId = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Delete {$entityLower}
document.getElementById('confirmDelete').addEventListener('click', function() {
    if (!currentDeleteId) return;
    
    const formData = new FormData();
    formData.append('_method', 'DELETE');
    
    fetch(\`/{$entityPlural}/\${currentDeleteId}\`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
            load{$this->entityName}s();
            showAlert('success', '{$this->entityName} deleted successfully');
        } else {
            showAlert('danger', 'Error deleting {$entityLower}');
        }
        currentDeleteId = null;
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Error deleting {$entityLower}');
    });
});

// Show alert
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = \`alert alert-\${type} alert-dismissible fade show\`;
    alertDiv.innerHTML = \`
        \${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    \`;
    
    const main = document.querySelector('main');
    main.insertBefore(alertDiv, main.firstChild);
    
    setTimeout(() => alertDiv.remove(), 5000);
}
</script>

<?php
\$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
EOT;

        file_put_contents(
            $this->projectPath . '/src/View/' . $entityLower . '_index.php',
            $viewContent
        );
    }

    private function generateTableHeaders(): string
    {
        $headers = "                        <th>ID</th>\n";
        
        foreach ($this->fields as $field) {
            $fieldName = ucfirst($field['name']);
            $headers .= "                        <th>{$fieldName}</th>\n";
        }
        
        return rtrim($headers);
    }

    private function generateTableColumns(): string
    {
        $columns = "                    <td>\${item.id}</td>\n";
        
        foreach ($this->fields as $field) {
            $fieldName = $field['name'];
            $columns .= "                    <td>\${item.{$fieldName}}</td>\n";
        }
        
        return rtrim($columns);
    }

    private function generateFormFields(): string
    {
        $fields = '';
        
        foreach ($this->fields as $field) {
            $fieldName = $field['name'];
            $fieldLabel = ucfirst($fieldName);
            $fieldType = $field['type'];
            
            $inputType = $this->getInputType($fieldType);
            
            $fields .= "                    <div class=\"mb-3\">\n";
            $fields .= "                        <label for=\"{$fieldName}\" class=\"form-label\">{$fieldLabel}</label>\n";
            
            if ($inputType === 'textarea') {
                $fields .= "                        <textarea class=\"form-control\" id=\"{$fieldName}\" name=\"{$fieldName}\" required></textarea>\n";
            } else {
                $fields .= "                        <input type=\"{$inputType}\" class=\"form-control\" id=\"{$fieldName}\" name=\"{$fieldName}\" required>\n";
            }
            
            $fields .= "                    </div>\n";
        }
        
        return rtrim($fields);
    }

    private function generateDetailFields(): string
    {
        $fields = "                <dl class=\"row\">\n";
        $fields .= "                    <dt class=\"col-sm-4\">ID:</dt>\n";
        $fields .= "                    <dd class=\"col-sm-8\" id=\"view_id\"></dd>\n";
        
        foreach ($this->fields as $field) {
            $fieldName = $field['name'];
            $fieldLabel = ucfirst($fieldName);
            
            $fields .= "                    <dt class=\"col-sm-4\">{$fieldLabel}:</dt>\n";
            $fields .= "                    <dd class=\"col-sm-8\" id=\"view_{$fieldName}\"></dd>\n";
        }
        
        $fields .= "                </dl>\n";
        
        return rtrim($fields);
    }

    private function generateViewScript(): string
    {
        $script = "        document.getElementById('view_id').textContent = data.id;\n";
        
        foreach ($this->fields as $field) {
            $fieldName = $field['name'];
            $script .= "        document.getElementById('view_{$fieldName}').textContent = data.{$fieldName};\n";
        }
        
        return rtrim($script);
    }

    private function generateEditScript(): string
    {
        $script = '';
        
        foreach ($this->fields as $field) {
            $fieldName = $field['name'];
            $script .= "        document.getElementById('{$fieldName}').value = data.{$fieldName};\n";
        }
        
        return rtrim($script);
    }

    private function getInputType(string $type): string
    {
        return match(strtolower($type)) {
            'int', 'integer' => 'number',
            'float', 'double' => 'number',
            'bool', 'boolean' => 'checkbox',
            'datetime', 'date' => 'date',
            'text' => 'textarea',
            default => 'text'
        };
    }
}