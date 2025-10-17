# PhpMvc Generator

<p align="center">
    <img src="https://img.shields.io/badge/PHP-8.0%2B-blue" alt="PHP Version">
    <img src="https://img.shields.io/badge/License-MIT-green" alt="License">
    <img src="https://img.shields.io/badge/Status-Stable-success" alt="Status">
</p>

A lightweight and powerful PHP MVC project generator that creates a complete, production-ready application with entities, repositories, controllers, and optional Bootstrap views and authentication system.

## ✨ Features

- **🚀 Rapid Development**: Generate a complete MVC structure in seconds
- **🎨 Bootstrap 5 Integration**: Optional responsive UI with modals and AJAX
- **🔐 Authentication System**: Complete login/register system with password hashing
- **🗄️ Repository Pattern**: Clean separation between business logic and data access
- **💉 Auto-Hydration**: Automatic entity hydration from arrays
- **🛣️ Smart Router**: RESTful routing with automatic CRUD routes
- **🔒 Security First**: Prepared statements, password hashing, input validation
- **📱 Hybrid Controllers**: Support both API (JSON) and HTML views
- **⚡ AJAX Ready**: Modern SPA-like experience with Bootstrap modals
- **🎯 PSR-4 Autoloading**: Modern PHP standards
- **🌐 i18n Ready**: All generated code in English

## 📋 Requirements

- **PHP** 8.0 or higher
- **Composer** (dependency manager)
- **MySQL** or **MariaDB** database
- Web server (Apache, Nginx) or PHP built-in server

## 📦 Installation

Install the generator globally via Composer:
```bash
composer global require phpmvc/phpmvc
```

Or install it in a specific project:
```bash
composer require phpmvc/phpmvc --dev
```

## 🚀 Quick Start

### 1. Generate a new project
```bash
php vendor/bin/phpmvc init
```

Or if installed globally:
```bash
phpmvc init
```

### 2. Answer the interactive questions
```
📁 Project name: my-blog
🔢 Number of entities: 2

━━━ Entity #1 ━━━
📝 Entity #1 name: Post
➜ Field name: title
➜ Type (string/int/float/bool/datetime): string
➜ Field name: content
➜ Type: text
➜ Field name: (leave empty to finish)

━━━ Entity #2 ━━━
📝 Entity #2 name: Category
➜ Field name: name
➜ Type: string
➜ Field name: (leave empty)

🎨 Generate Bootstrap views? (y/n): y
🔐 Generate authentication system? (y/n): y
```

### 3. ⚠️ **IMPORTANT - Database Setup**

**Before running your project, you MUST create your MySQL database:**
```sql
CREATE DATABASE my_blog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

If you enabled authentication, also create the users table:
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

Create tables for your entities:
```sql
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);
```

### 4. Configure database connection

Edit `config/database.php`:
```php
return [
    'host' => 'localhost',
    'database' => 'my_blog',        // ← Your database name
    'username' => 'root',           // ← Your MySQL username
    'password' => '',               // ← Your MySQL password
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
```

### 5. Install dependencies and run
```bash
cd my-blog
composer install
php -S localhost:8000 -t public
```

### 6. Access your application

Open your browser:
- **Home**: http://localhost:8000/posts
- **Login**: http://localhost:8000/login
- **Register**: http://localhost:8000/register

## 📁 Generated Project Structure
```
my-blog/
├── public/
│   └── index.php              # Application entry point
├── src/
│   ├── Entity/                # Your entities (Post, Category, User)
│   ├── Repository/            # Database access layer
│   ├── Controller/            # Business logic (CRUD operations)
│   ├── View/                  # Bootstrap views (if enabled)
│   └── Core/                  # Core classes
│       ├── Database.php       # PDO connection (singleton)
│       ├── Hydrator.php       # Entity hydration
│       ├── Router.php         # URL routing
│       ├── Session.php        # Session management (if auth enabled)
│       └── AuthMiddleware.php # Route protection (if auth enabled)
├── config/
│   ├── database.php           # Database configuration
│   └── routes.php             # Route definitions
├── vendor/                    # Composer dependencies
├── composer.json              # Project dependencies
├── .gitignore                 # Git ignore rules
└── README.md                  # Project documentation
```

## 🎯 What Gets Generated

### For each entity (e.g., `Post`):

#### 1. **Entity** (`src/Entity/Post.php`)
```php
- Private properties with type hints
- Constructor
- hydrate() method for automatic population
- Getters and setters for all fields
```

#### 2. **Repository** (`src/Repository/PostRepository.php`)
```php
- findAll(): array          // Get all records
- findById(int $id): ?Post  // Get by ID
- save(Post $post): void    // Create or update
- delete(int $id): void     // Delete record
```

#### 3. **Controller** (`src/Controller/PostController.php`)
```php
- index()           // List all (public access)
- show($id)         // Show one (public access)
- create()          // Show form (public access)
- store()           // Create (requires auth if enabled)
- edit($id)         // Get data for edit (public access)
- update($id)       // Update (requires auth if enabled)
- delete($id)       // Delete (requires auth if enabled)
```

#### 4. **View** (`src/View/post_index.php`) - If Bootstrap enabled
```php
- Responsive table with data
- Create/Edit/View/Delete modals
- AJAX operations (no page reload)
- Real-time alerts
```

#### 5. **Routes** (`config/routes.php`)
```php
GET    /posts           → PostController::index()
GET    /posts/{id}      → PostController::show($id)
GET    /posts/create    → PostController::create()
POST   /posts           → PostController::store()
GET    /posts/{id}/edit → PostController::edit($id)
POST   /posts/{id}      → PostController::update($id) [_method=PUT]
POST   /posts/{id}      → PostController::delete($id) [_method=DELETE]
```

## 🔐 Authentication System (if enabled)

### Features

- **User Registration** with validation
- **Login/Logout** with session management
- **Password Hashing** using bcrypt
- **Route Protection** for write operations
- **Session Management** with helper class

### Authentication Routes
```php
GET/POST /login         // Login page and process
GET/POST /register      // Registration page and process
GET      /logout        // Logout and destroy session
GET      /auth/check    // Check auth status (API)
```

### Protected Routes

When authentication is enabled:
- ✅ **Public access**: `index()`, `show()`, `create()`, `edit()`
- 🔒 **Requires login**: `store()`, `update()`, `delete()`

### Using Authentication in Your Code
```php
// In a controller
use App\Core\Session;
use App\Core\AuthMiddleware;

// Check if user is authenticated
if (Session::isAuthenticated()) {
    $userId = Session::getUserId();
    $user = Session::get('user');
}

// Protect a route manually
AuthMiddleware::requireAuth();

// Require guest (not logged in)
AuthMiddleware::requireGuest();
```

## 🌐 API Usage

All controllers support JSON responses for API usage:

### List all posts
```bash
curl http://localhost:8000/posts \
  -H "X-Requested-With: XMLHttpRequest"
```

Response:
```json
[
    {"id": 1, "title": "First Post", "content": "..."},
    {"id": 2, "title": "Second Post", "content": "..."}
]
```

### Create a post
```bash
curl -X POST http://localhost:8000/posts \
  -d "title=New Post&content=Great content"
```

Response:
```json
{
    "success": true,
    "id": 3,
    "message": "Post created successfully"
}
```

### Update a post
```bash
curl -X POST http://localhost:8000/posts/3 \
  -d "_method=PUT&title=Updated&content=New content"
```

### Delete a post
```bash
curl -X POST http://localhost:8000/posts/3 \
  -d "_method=DELETE"
```

## 🎨 Bootstrap Views

When Bootstrap is enabled, you get:

- **Responsive Layout** with navbar and footer
- **Modal-based CRUD** (no page reloads)
- **AJAX Operations** with real-time updates
- **Bootstrap Icons** for actions
- **Alert System** for feedback
- **Form Validation** with visual feedback

### View Structure

Each entity gets:
```php
{entity}_index.php
├── List table with data
├── Create modal
├── Edit modal
├── View modal
└── Delete confirmation modal
```

## 🔧 Customization

### Adding Custom Routes

Edit `config/routes.php`:
```php
// Custom route
$router->get('/about', 'PageController', 'about');
$router->post('/contact', 'ContactController', 'send');

// Protect a specific route
// In your controller:
\App\Core\AuthMiddleware::requireAuth();
```

### Adding New Fields to Entity

1. Add the field to your database table
2. Add property, getter, and setter to entity
3. Update repository if needed
4. Add field to view forms

### Modifying Views

Views are in `src/View/`. Edit them directly:
- Modify HTML structure
- Change Bootstrap classes
- Add custom JavaScript
- Customize forms

## 🐛 Troubleshooting

### Error: "Connection error"

**Problem**: Cannot connect to MySQL database

**Solutions**:
1. Verify MySQL is running: `mysql --version`
2. Check credentials in `config/database.php`
3. Ensure database exists: `CREATE DATABASE your_db_name;`
4. Check MySQL port (default: 3306)

### Error: "Class not found"

**Problem**: Autoloading issue

**Solution**: Run `composer dump-autoload`

### Error: "Route not found"

**Problem**: Routes not configured correctly

**Solutions**:
1. Check `config/routes.php` exists
2. Verify router is loaded in `public/index.php`
3. Clear browser cache

### Error: "Call to undefined method"

**Problem**: Missing method in controller/repository

**Solution**: Regenerate the entity/controller or add method manually

### Bootstrap views not working

**Problem**: AJAX requests failing

**Solutions**:
1. Check browser console for JavaScript errors
2. Verify routes are correct in `config/routes.php`
3. Ensure CDN links are accessible (check internet connection)

### Authentication not working

**Problem**: Login/register not functioning

**Solutions**:
1. Verify `users` table exists in database
2. Check Session class is loaded
3. Clear browser cookies/session
4. Verify `AuthMiddleware.php` exists in `src/Core/`

## 🔒 Security Features

PhpMvc Generator includes several security features:

- ✅ **Prepared Statements**: All SQL queries use PDO prepared statements (prevents SQL injection)
- ✅ **Password Hashing**: Bcrypt hashing for passwords (no plain text storage)
- ✅ **Input Validation**: Automatic validation based on field types
- ✅ **Type Safety**: PHP 8 type hints throughout
- ✅ **Session Security**: Secure session management
- ✅ **CSRF Protection**: Ready for CSRF token implementation
- ✅ **XSS Prevention**: Use `htmlspecialchars()` in views for user input

### Additional Security Recommendations

1. **In Production**:
   - Change default database credentials
   - Use HTTPS
   - Set secure session cookies
   - Enable error logging (disable display)
   - Keep dependencies updated

2. **Add CSRF Protection**:
```php
// In forms:
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

// In controllers:
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid CSRF token');
}
```

## 📚 Examples

### Example 1: Simple Blog
```bash
phpmvc init
# Project: blog
# Entities: Post (title, content), Comment (author, content)
# Bootstrap: Yes
# Auth: Yes
```

Result: Full blog with posts, comments, and user authentication

### Example 2: E-commerce
```bash
phpmvc init
# Project: shop
# Entities: Product (name, price, stock), Category (name)
# Bootstrap: Yes
# Auth: Yes
```

Result: Product catalog with admin panel

### Example 3: REST API
```bash
phpmvc init
# Project: api
# Entities: User (name, email), Task (title, status)
# Bootstrap: No
# Auth: No
```

Result: Pure JSON API without views

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## 📄 License

This project is open-source and available under the [MIT License](LICENSE).

## 👤 Author

**Your Name**
- GitHub: [@stanislasdevfr](https://github.com/stanislasdevfr)
- Email: duartestanislas.pro@gmail.com

## 🙏 Acknowledgments

- Symfony Console for CLI interactions
- Bootstrap 5 for UI components
- The PHP community

---

<p align="center">Made by developer, for developers</p>
<p align="center">⭐ Star this project if you find it useful!</p>
