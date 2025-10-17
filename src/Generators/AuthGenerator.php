<?php

namespace PhpMvc\Generators;

class AuthGenerator
{
    private string $projectPath;
    private bool $withBootstrap;

    public function __construct(string $projectPath, bool $withBootstrap = false)
    {
        $this->projectPath = $projectPath;
        $this->withBootstrap = $withBootstrap;
    }

    public function generate(): void
    {
        // 1. Create User entity
        $this->createUserEntity();
        
        // 2. Create UserRepository
        $this->createUserRepository();
        
        // 3. Create AuthController
        $this->createAuthController();
        
        // 4. Create Session class
        $this->createSessionClass();
        
        // 5. Create Auth middleware
        $this->createAuthMiddleware();
        
        // 6. Create views if Bootstrap enabled
        if ($this->withBootstrap) {
            $this->createLoginView();
            $this->createRegisterView();
        }
    }

    private function createUserEntity(): void
    {
        $entityContent = <<<'EOT'
<?php

namespace App\Entity;

class User
{
    private ?int $id = null;
    private string $email;
    private string $password;
    private string $name;
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Hash the password
     */
    public function hashPassword(): void
    {
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->password);
    }
}
EOT;

        file_put_contents(
            $this->projectPath . '/src/Entity/User.php',
            $entityContent
        );
    }

    private function createUserRepository(): void
    {
        $repositoryContent = <<<'EOT'
<?php

namespace App\Repository;

use App\Core\Database;
use App\Core\Hydrator;
use App\Entity\User;

class UserRepository
{
    private Database $db;
    private string $table = 'users';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $data = $this->db->fetchAll($sql);
        
        return Hydrator::hydrateMultiple(User::class, $data);
    }

    public function findById(int $id): ?User
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $data = $this->db->fetch($sql, [$id]);
        
        if (!$data) {
            return null;
        }
        
        return Hydrator::hydrate(new User(), $data);
    }

    public function findByEmail(string $email): ?User
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        $data = $this->db->fetch($sql, [$email]);
        
        if (!$data) {
            return null;
        }
        
        return Hydrator::hydrate(new User(), $data);
    }

    public function save(User $entity): void
    {
        $data = Hydrator::extract($entity);
        
        if ($entity->getId()) {
            // UPDATE
            $id = $entity->getId();
            unset($data['id']);
            
            $fields = [];
            foreach (array_keys($data) as $key) {
                $fields[] = "$key = ?";
            }
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
            $values = array_values($data);
            $values[] = $id;
            
            $this->db->execute($sql, $values);
        } else {
            // INSERT
            unset($data['id']);
            
            // Format DateTime for MySQL
            if (isset($data['createdAt']) && $data['createdAt'] instanceof \DateTime) {
                $data['createdAt'] = $data['createdAt']->format('Y-m-d H:i:s');
            }
            
            $columns = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));
            
            $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
            $this->db->execute($sql, array_values($data));
            
            // Update entity ID
            $reflection = new \ReflectionClass($entity);
            $idProperty = $reflection->getProperty('id');
            $idProperty->setAccessible(true);
            $idProperty->setValue($entity, (int)$this->db->lastInsertId());
        }
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $this->db->execute($sql, [$id]);
    }

    public function emailExists(string $email): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = ?";
        $result = $this->db->fetch($sql, [$email]);
        return $result['count'] > 0;
    }
}
EOT;

        file_put_contents(
            $this->projectPath . '/src/Repository/UserRepository.php',
            $repositoryContent
        );
    }

    private function createAuthController(): void
    {
        $loginMethod = $this->withBootstrap ? $this->generateHybridLoginMethod() : $this->generateApiLoginMethod();
        $registerMethod = $this->withBootstrap ? $this->generateHybridRegisterMethod() : $this->generateApiRegisterMethod();

        $controllerContent = <<<EOT
<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Core\Session;

class AuthController
{
    private UserRepository \$repository;

    public function __construct()
    {
        \$this->repository = new UserRepository();
    }

{$loginMethod}

{$registerMethod}

    /**
     * Logout
     */
    public function logout(): void
    {
        Session::destroy();
        
        header('Location: /login');
        exit;
    }

    /**
     * Check if user is authenticated
     */
    public function check(): void
    {
        header('Content-Type: application/json');
        echo json_encode([
            'authenticated' => Session::isAuthenticated(),
            'user' => Session::get('user')
        ]);
    }
}
EOT;

        file_put_contents(
            $this->projectPath . '/src/Controller/AuthController.php',
            $controllerContent
        );
    }

    private function generateHybridLoginMethod(): string
    {
        return <<<'EOT'
    /**
     * Show login form or process login
     */
    public function login(): void
    {
        // If already logged in, redirect
        if (Session::isAuthenticated()) {
            header('Location: /');
            exit;
        }

        // If GET request, show form
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            require __DIR__ . '/../View/auth_login.php';
            return;
        }

        // If POST, process login
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $errors = [];

        if (empty($email)) {
            $errors[] = 'Email is required';
        }
        if (empty($password)) {
            $errors[] = 'Password is required';
        }

        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            return;
        }

        $user = $this->repository->findByEmail($email);

        if (!$user || !$user->verifyPassword($password)) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
            return;
        }

        // Store user in session
        Session::set('user_id', $user->getId());
        Session::set('user', [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail()
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'redirect' => '/'
        ]);
    }
EOT;
    }

    private function generateApiLoginMethod(): string
    {
        return <<<'EOT'
    /**
     * Process login
     */
    public function login(): void
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $errors = [];

        if (empty($email)) {
            $errors[] = 'Email is required';
        }
        if (empty($password)) {
            $errors[] = 'Password is required';
        }

        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            return;
        }

        $user = $this->repository->findByEmail($email);

        if (!$user || !$user->verifyPassword($password)) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
            return;
        }

        // Store user in session
        Session::set('user_id', $user->getId());
        Session::set('user', [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail()
        ]);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail()
            ]
        ]);
    }
EOT;
    }

    private function generateHybridRegisterMethod(): string
    {
        return <<<'EOT'
    /**
     * Show register form or process registration
     */
    public function register(): void
    {
        // If already logged in, redirect
        if (Session::isAuthenticated()) {
            header('Location: /');
            exit;
        }

        // If GET request, show form
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            require __DIR__ . '/../View/auth_register.php';
            return;
        }

        // If POST, process registration
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $errors = [];

        if (empty($name)) {
            $errors[] = 'Name is required';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required';
        }
        if (empty($password) || strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }
        if ($this->repository->emailExists($email)) {
            $errors[] = 'Email already exists';
        }

        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            return;
        }

        // Create user
        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setPassword($password);
        $user->hashPassword();

        $this->repository->save($user);

        // Auto-login
        Session::set('user_id', $user->getId());
        Session::set('user', [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail()
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Registration successful',
            'redirect' => '/'
        ]);
    }
EOT;
    }

    private function generateApiRegisterMethod(): string
    {
        return <<<'EOT'
    /**
     * Process registration
     */
    public function register(): void
    {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $errors = [];

        if (empty($name)) {
            $errors[] = 'Name is required';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required';
        }
        if (empty($password) || strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        if ($this->repository->emailExists($email)) {
            $errors[] = 'Email already exists';
        }

        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            return;
        }

        // Create user
        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setPassword($password);
        $user->hashPassword();

        $this->repository->save($user);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful',
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail()
            ]
        ]);
    }
EOT;
    }

    private function createSessionClass(): void
    {
        $sessionContent = <<<'EOT'
<?php

namespace App\Core;

class Session
{
    /**
     * Start session if not already started
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Set a session value
     */
    public static function set(string $key, $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session value
     */
    public static function get(string $key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if key exists
     */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session value
     */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Destroy session
     */
    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        session_destroy();
    }

    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated(): bool
    {
        return self::has('user_id');
    }

    /**
     * Get authenticated user ID
     */
    public static function getUserId(): ?int
    {
        return self::get('user_id');
    }
}
EOT;

        file_put_contents(
            $this->projectPath . '/src/Core/Session.php',
            $sessionContent
        );
    }

    private function createAuthMiddleware(): void
    {
        $middlewareContent = <<<'EOT'
<?php

namespace App\Core;

class AuthMiddleware
{
    /**
     * Check if user is authenticated, redirect to login if not
     */
    public static function requireAuth(): void
    {
        if (!Session::isAuthenticated()) {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Check if user is guest (not authenticated), redirect to home if authenticated
     */
    public static function requireGuest(): void
    {
        if (Session::isAuthenticated()) {
            header('Location: /');
            exit;
        }
    }
}
EOT;

        file_put_contents(
            $this->projectPath . '/src/Core/AuthMiddleware.php',
            $middlewareContent
        );
    }

    private function createLoginView(): void
    {
        $loginContent = <<<'EOT'
<?php
$title = 'Login';
$menu = [];

ob_start();
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-5">
        <div class="card shadow">
            <div class="card-body p-5">
                <h2 class="text-center mb-4">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </h2>
                
                <form id="loginForm">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
                
                <div class="text-center mt-3">
                    <p class="mb-0">Don't have an account? <a href="/register">Register</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/login', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect || '/';
        } else {
            showAlert('danger', data.error || data.errors.join('<br>'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'An error occurred');
    });
});

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const form = document.getElementById('loginForm');
    form.parentNode.insertBefore(alertDiv, form);
    
    setTimeout(() => alertDiv.remove(), 5000);
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
EOT;

        file_put_contents(
            $this->projectPath . '/src/View/auth_login.php',
            $loginContent
        );
    }

    private function createRegisterView(): void
    {
        $registerContent = <<<'EOT'
<?php
$title = 'Register';
$menu = [];

ob_start();
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-5">
        <div class="card shadow">
            <div class="card-body p-5">
                <h2 class="text-center mb-4">
                    <i class="bi bi-person-plus"></i> Register
                </h2>
                
                <form id="registerForm">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <small class="text-muted">Minimum 8 characters</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Register</button>
                </form>
                
                <div class="text-center mt-3">
                    <p class="mb-0">Already have an account? <a href="/login">Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/register', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect || '/';
        } else {
            showAlert('danger', data.error || data.errors.join('<br>'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'An error occurred');
    });
});

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const form = document.getElementById('registerForm');
    form.parentNode.insertBefore(alertDiv, form);
    
    setTimeout(() => alertDiv.remove(), 5000);
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
EOT;

        file_put_contents(
            $this->projectPath . '/src/View/auth_register.php',
            $registerContent
        );
    }
}