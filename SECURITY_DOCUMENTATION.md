# SubWFour System - Security Documentation

**Version:** 1.0  
**Last Updated:** January 2025  
**Classification:** Internal Use Only

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Secure Coding Practices](#2-secure-coding-practices)
3. [Authentication and Authorization](#3-authentication-and-authorization)
4. [Data Encryption](#4-data-encryption)
5. [Input Validation and Sanitization](#5-input-validation-and-sanitization)
6. [Error Handling and Logging](#6-error-handling-and-logging)
7. [Access Control](#7-access-control)
8. [Code Auditing Tools](#8-code-auditing-tools)
9. [Testing](#9-testing)
10. [Security Policies](#10-security-policies)
11. [Incident Response Plan](#11-incident-response-plan)

---

## 1. Project Overview

### 1.1 System Description

SubWFour is a comprehensive web-based inventory and service management system designed for businesses requiring secure data handling, multi-role access control, and real-time security monitoring. The system integrates inventory management, booking services, employee management, and robust security features into a unified platform.

**Core Features:**
- **Inventory Management:** Track items, categories, stock movements (stock-in/stock-out), and supplier information
- **Booking Management:** Handle service bookings with customer information
- **Employee Management:** Manage employee records with encrypted sensitive data
- **Security Monitoring:** Real-time incident detection, comprehensive audit logging, and threat assessment
- **Multi-Role Access Control:** Three distinct user roles with granular permissions

### 1.2 Purpose of the System

The system aims to:

1. **Provide Secure Data Management:** Protect sensitive employee, customer, and business data through encryption and access controls
2. **Prevent Cyber Threats:** Implement defenses against SQL injection, XSS attacks, brute force attempts, and unauthorized access
3. **Enable Audit Compliance:** Maintain comprehensive logs of all system activities for security audits
4. **Streamline Business Operations:** Provide intuitive interfaces for inventory tracking, booking management, and reporting
5. **Real-Time Threat Detection:** Automatically detect and respond to security incidents

### 1.3 Intended Users

| Role | Description | Primary Functions |
|------|-------------|-------------------|
| **Administrator (Admin)** | System administrators with full access | User management, system configuration, security monitoring, audit logs, employee management |
| **Employee** | Regular staff members | Inventory management, booking operations, view own profile |
| **Security** | Security personnel | Security incident monitoring, system logs review, threat assessment |

### 1.4 Platform and Technology

| Component | Technology |
|-----------|------------|
| **Programming Language** | PHP 8.2+ |
| **Framework** | Laravel 12 |
| **Database** | MySQL |
| **Platform** | Web Application |
| **Frontend** | Blade Templates, CSS, JavaScript |
| **HTTPS Tunneling** | ngrok (for secure local development) |
| **Code Auditing** | Larastan (PHPStan), PHP_CodeSniffer, Laravel Debugbar |

---

## 2. Secure Coding Practices

### 2.1 Environment Variables for Sensitive Data

All sensitive credentials and configuration values are stored in environment variables, never hardcoded in the source code.

**Implementation:**

The `.env` file (excluded from version control via `.gitignore`) contains all sensitive configuration:

```env
# Database credentials stored securely
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=subwfour
DB_USERNAME=your_username
DB_PASSWORD=your_secure_password

# Application encryption key (auto-generated)
APP_KEY=base64:...

# Mail configuration
MAIL_MAILER=smtp
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_app_password
```

**Accessing Environment Variables in Code:**

```php
// Secure way - using env() or config()
$dbPassword = config('database.connections.mysql.password');
$appKey = config('app.key');

// Never do this:
// $password = 'hardcoded_password'; // WRONG!
```

### 2.2 Configuration Caching

Production environments use cached configuration to prevent direct `.env` file access during runtime:

```bash
php artisan config:cache
```

### 2.3 Secure Dependency Management

- Dependencies managed through Composer with version constraints
- Regular security audits using `composer audit`
- Lock file (`composer.lock`) committed to ensure consistent installations

### 2.4 CSRF Protection

All forms include CSRF token protection:

```php
// In Blade templates
<form method="POST" action="...">
    @csrf
    <!-- form fields -->
</form>
```

Laravel automatically validates CSRF tokens for all POST, PUT, PATCH, and DELETE requests.

### 2.5 Mass Assignment Protection

Models explicitly define fillable fields to prevent mass assignment vulnerabilities:

```php
// Example from User model
protected $fillable = [
    'name',
    'email',
    'password',
    'role',
    'is_active',
    'password_changed_at',
    'must_change_password',
];

// Sensitive fields excluded from mass assignment
protected $hidden = [
    'password',
    'remember_token',
];
```

**Screenshot Placeholder:** *[INSERT: Screenshot of .env.example file showing secure credential handling]*

---

## 3. Authentication and Authorization

### 3.1 Login Process

The authentication flow implements multiple security layers:

**Step 1: Credential Validation**
1. User submits username and password
2. System checks for account lockout status
3. Credentials validated against database
4. Account active status verified

**Step 2: CAPTCHA Verification**
1. Upon successful credential validation, user is presented with CAPTCHA
2. CAPTCHA must be completed correctly to proceed
3. Failed CAPTCHA returns user to credential entry

**Step 3: Session Establishment**
1. Session regenerated to prevent session fixation
2. Login attempt recorded in `login_attempts` table
3. User redirected to role-appropriate dashboard

Login Flow Code Structure:

```php
public function login(Request $request)
{
    // 1. Validate input
    $request->validate([
        'name' => 'required|string',
        'password' => 'required|string',
    ]);

    // 2. Check lockout status
    $lockoutInfo = LoginAttempt::isLockedOut($username, $ipAddress);
    if ($lockoutInfo['locked']) {
        SecurityIncident::recordLockout($username, $ipAddress);
        return response()->json(['success' => false, 'message' => '...'], 429);
    }

    // 3. Validate credentials
    if (Auth::validate($credentials)) {
        // 4. Check account status
        if (!$user->isActive()) {
            return response()->json(['success' => false, 'message' => 'Account deactivated'], 403);
        }
        // 5. Proceed to CAPTCHA verification
        session(['pending_login' => [...]]);
        return response()->json(['success' => true, 'require_captcha' => true]);
    }

    // 6. Record failed attempt
    LoginAttempt::recordFailure($username, 'Invalid credentials');
}
```

### 3.2 Password Protection

**Hashing Algorithm:** Passwords are hashed using **bcrypt** (Laravel's default), which is an adaptive cryptographic hashing algorithm designed to be computationally intensive to resist brute-force attacks.

**Implementation:**

```php
// Password automatically hashed via Eloquent cast
protected function casts(): array
{
    return [
        'password' => 'hashed',  // Laravel auto-hashes on assignment
    ];
}

// Manual hashing when needed
use Illuminate\Support\Facades\Hash;
$hashedPassword = Hash::make($plainPassword);

// Password verification
if (Hash::check($plainPassword, $hashedPassword)) {
    // Password matches
}
```

**Password History Tracking:**

The system prevents password reuse by maintaining a history of previous passwords:

```php
public function updatePassword(string $newPassword): bool
{
    // Check password history
    if (PasswordHistory::wasUsedBefore($this->id, $newPassword)) {
        return false;  // Reject previously used password
    }

    // Hash and update
    $this->update([
        'password' => Hash::make($newPassword),
        'password_changed_at' => now(),
    ]);

    // Record in history
    PasswordHistory::record($this->id, $hashedPassword);
    return true;
}
```

### 3.3 User Roles and Role-Based Access Control (RBAC)

| Role | Access Level | Permissions |
|------|-------------|-------------|
| **Administrator** | Full Access | User management, system configuration, all data access, audit logs, security settings |
| **Employee** | Limited Access | Inventory operations, booking management, own profile view |
| **Security** | Security Access | Security incidents, system logs, login attempts, threat monitoring |

**Role Assignment:**

```php
// User model role field
'role' => 'admin' | 'employee' | 'security'

// Role-based redirection after login
if ($role === 'admin') {
    return redirect()->route('system');
} elseif ($role === 'security') {
    return redirect()->route('security.dashboard');
} else {
    return redirect()->route('employee.dashboard');
}
```

**Screenshot Placeholder:** *[INSERT: Screenshot demonstrating role-based dashboard access]*

---

## 4. Data Encryption

### 4.1 Encryption Methods

The system employs **AES-256-CBC** encryption through Laravel's built-in encryption service for sensitive data at rest.

**Encryption Service Implementation:**

```php
namespace App\Services;
use Illuminate\Support\Facades\Crypt;
class EncryptionService
{
    /**
     * Encrypt a value using AES-256-CBC
     */
    public static function encrypt(mixed $value): ?string
    {
        if (is_null($value) || $value === '') {
            return null;
        }
        return Crypt::encryptString((string) $value);
    }

    /**
     * Decrypt a value
     */
    public static function decrypt(?string $encryptedValue): ?string
    {
        if (is_null($encryptedValue)) {
            return null;
        }

        try {
            return Crypt::decryptString($encryptedValue);
        } catch (DecryptException $e) {
            \Log::warning('Decryption failed', [
                'error' => $e->getMessage(),
                'ip' => request()->ip(),
            ]);
            return null;
        }
    }
}
```

### 4.2 Encrypted Data Fields

| Table | Encrypted Fields | Purpose |
|-------|-----------------|---------|
| `employees` | `sss_number`, `contact_number` | Protect personal identification |
| `suppliers` | `number` | Protect business contact information |
| `bookings` | `email`, `contact_number` | Protect customer data |

**Configuration (config/security.php):**

```php
'data' => [
    'encrypted_fields' => [
        'employees' => ['sss_number', 'contact_number'],
        'suppliers' => ['number'],
        'bookings' => ['email', 'contact_number'],
    ],
],
```

### 4.3 Data Masking for Display

Sensitive data is masked when displayed to users:

```php
/**
 * Mask sensitive data (e.g., SSS: ***-**-1234)
 */
public static function mask(string $value, int $visibleChars = 4): string
{
    $length = strlen($value);
    if ($length <= $visibleChars) {
        return str_repeat('*', $length);
    }
    return str_repeat('*', $length - $visibleChars) . substr($value, -$visibleChars);
}

/**
 * Mask email (e.g., j***@example.com)
 */
public static function maskEmail(string $email): string
{
    $parts = explode('@', $email);
    $username = $parts[0];
    $domain = $parts[1];
    $maskedUsername = $username[0] . str_repeat('*', strlen($username) - 2) . substr($username, -1);
    return $maskedUsername . '@' . $domain;
}
```

### 4.4 Transport Layer Security

- All production traffic uses **HTTPS/TLS** encryption
- Development environments utilize **ngrok** for secure HTTPS tunneling
- HTTP Strict Transport Security (HSTS) headers enforced

**Screenshot Placeholder:** *[INSERT: Screenshot showing encrypted data in database]*

---

## 5. Input Validation and Sanitization

### 5.1 Validated User Inputs

| Input Type | Validation Applied | Location |
|------------|-------------------|----------|
| Login Credentials | Required, string type | AuthController |
| Employee Data | Required fields, email format, unique constraints | EmployeeController |
| Booking Information | Required, email validation, contact format | BookingController |
| Inventory Items | Required, numeric quantities, category validation | ItemController |
| Search Queries | Sanitized for XSS/SQL injection | All controllers |
| File Uploads | Type, size, extension validation | Applicable controllers |

### 5.2 Server-Side Validation

Laravel's validation rules are applied to all form inputs:

```php
$request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users,email',
    'password' => 'required|min:8|confirmed',
    'role' => 'required|in:admin,employee,security',
    'contact_number' => 'required|regex:/^[0-9]{10,11}$/',
]);
```

### 5.3 Input Sanitization Middleware

The `SanitizeInput` middleware processes all incoming requests to neutralize potential attacks:

```php
namespace App\Http\Middleware;

class SanitizeInput
{
    protected array $sqlPatterns = [
        '/(\bunion\b.*\bselect\b)/i',
        '/(\bselect\b.*\bfrom\b)/i',
        '/(\binsert\b.*\binto\b)/i',
        '/(\bdelete\b.*\bfrom\b)/i',
        '/(\bdrop\b.*\btable\b)/i',
        '/(\bupdate\b.*\bset\b)/i',
        '/(\'|\")\s*(or|and)\s*(\'|\")/i',
        '/(\bexec\b|\bexecute\b)/i',
    ];

    protected array $xssPatterns = [
        '/<script\b[^>]*>(.*?)<\/script>/is',
        '/javascript:/i',
        '/on\w+\s*=/i',
        '/<iframe\b/i',
        '/<object\b/i',
    ];

    protected function sanitizeValue(string $value): string
    {
        // Remove HTML tags
        $value = strip_tags($value);

        // Encode special characters
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        // Remove SQL injection patterns
        foreach ($this->sqlPatterns as $pattern) {
            $value = preg_replace($pattern, '', $value);
        }

        return $value;
    }
}
```

### 5.4 XSS Prevention

**Output Encoding:** All output in Blade templates is automatically escaped:

```php
// Escaped output (safe)
{{ $userInput }}

// Unescaped output (use with caution, only for trusted HTML)
{!! $trustedHtml !!}
```

### 5.5 SQL Injection Prevention

- **Eloquent ORM:** All database queries use Eloquent, which automatically parameterizes queries
- **Query Builder:** When raw queries are needed, bindings are used

```php
// Safe - Eloquent (parameterized)
$user = User::where('email', $email)->first();

// Safe - Query Builder with bindings
DB::select('SELECT * FROM users WHERE email = ?', [$email]);

// NEVER do this:
// DB::select("SELECT * FROM users WHERE email = '$email'");  // VULNERABLE!
```

**Screenshot Placeholder:** *[INSERT: Screenshot showing sanitization in action]*

---

## 6. Error Handling and Logging

### 6.1 Secure Error Handling

**Production Error Display:**
- Generic error messages displayed to users
- Technical details hidden from public view
- Detailed errors logged internally

**Configuration (config/app.php):**

```php
// Production environment
'debug' => env('APP_DEBUG', false),  // Set to false in production
```

### 6.2 Logging System

The system maintains comprehensive logs across multiple channels:

| Log Channel | Purpose | Retention |
|-------------|---------|-----------|
| **Security** | Security events, authentication, incidents | 365 days |
| **Audit** | User actions, data modifications | 365 days |
| **Error** | System errors, exceptions | 90 days |
| **Info** | General system information | 30 days |

**Log Entry Structure:**

```php
class SystemLog extends Model
{
    protected $fillable = [
        'user_id',
        'channel',
        'level',
        'action',
        'message',
        'context',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'logged_at',
    ];
}
```

### 6.3 Logged Events

| Event Type | Description | Log Channel |
|------------|-------------|-------------|
| `login_success` | Successful user authentication | Audit |
| `login_failure` | Failed login attempt | Security |
| `logout` | User logout | Audit |
| `password_change` | Password modification | Security |
| `data_create` | New record creation | Audit |
| `data_update` | Record modification | Audit |
| `data_delete` | Record deletion | Audit |
| `unauthorized_access` | Access violation attempt | Security |
| `security_incident` | Detected security threat | Security |

### 6.4 Security Logging Examples

```php
// Logging a successful login
SystemLog::audit(
    "User {$username} logged in successfully",
    'user.login',
    ['role' => Auth::user()->role]
);

// Logging a failed login attempt
SystemLog::security(
    "Failed login attempt for username: {$username}",
    'user.login.failed',
    ['username' => $username]
);

// Logging security incidents
SystemLog::security(
    "Account lockout triggered for username: {$username}",
    'user.lockout',
    ['username' => $username, 'remaining_seconds' => $lockoutInfo['remaining_seconds']]
);
```

**Screenshot Placeholder:** *[INSERT: Screenshot of system logs interface]*

---

## 7. Access Control

### 7.1 Protected Resources

| Resource | Access Level | Protection Method |
|----------|-------------|-------------------|
| `/admin/*` | Admin only | CheckRole middleware |
| `/security/*` | Security role only | CheckRole middleware |
| `/employee/*` | Employee role only | CheckRole middleware |
| `/audit-logs` | Admin only | Route middleware |
| `/system-logs` | Admin, Security | Route middleware |
| `/incidents` | Security, Admin | Route middleware |
| `/employees` | Admin only | Route middleware |

### 7.2 Middleware Stack

The system applies multiple middleware layers for comprehensive protection:

```php
Route::middleware(['auth', 'sanitize.input', 'security.headers', 'enforce.security'])->group(function () {
    
    // Employee routes
    Route::middleware(['role:employee'])->prefix('employee')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'employee']);
        // Inventory, booking routes...
    });

    // Admin routes
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
        Route::resource('/employees', EmployeeController::class);
        // System configuration routes...
    });

    // Security routes
    Route::middleware(['role:security'])->prefix('security')->group(function () {
        Route::get('/dashboard', [SecurityController::class, 'dashboard']);
        Route::get('/incidents', [IncidentsController::class, 'index']);
        // Security monitoring routes...
    });
});
```

### 7.3 Role Check Middleware

```php
namespace App\Http\Middleware;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $userRole = Auth::user()->role;

        if (!in_array($userRole, $roles)) {
            // Log unauthorized access attempt
            SecurityIncident::recordUnauthorized($request->path(), Auth::id());
            
            abort(403, 'Unauthorized access');
        }

        return $next($request);
    }
}
```

### 7.4 Session Security

- **Session Regeneration:** Session ID regenerated after login to prevent fixation attacks
- **Session Timeout:** Sessions expire after 120 minutes of inactivity
- **Concurrent Session Control:** Limited to 1 session per user by default

```php
// config/security.php
'login' => [
    'session_timeout_minutes' => 120,
    'allow_concurrent_sessions' => false,
    'max_concurrent_sessions' => 1,
],
```

### 7.5 IP Blocking

Malicious IPs are automatically blocked after repeated violations:

```php
class CheckBlockedIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        
        if ($this->isBlocked($ip)) {
            abort(403, 'Access denied. Your IP has been blocked.');
        }

        return $next($request);
    }
}
```

**Screenshot Placeholder:** *[INSERT: Screenshot showing access denied for unauthorized user]*

---

## 8. Code Auditing Tools

### 8.1 Tools Implemented

| Tool | Version | Purpose |
|------|---------|---------|
| **Larastan (PHPStan)** | v3.9.3 | Static code analysis for type safety and Laravel-specific checks |
| **PHP_CodeSniffer** | v4.0.1 | Coding standards enforcement (PSR-12) |
| **Laravel Debugbar** | v4.0.10 | Runtime debugging and performance monitoring |

### 8.2 PHPStan (Larastan) Configuration

**Configuration File (phpstan.neon):**

```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - app/
    level: 5
    reportUnmatchedIgnoredErrors: false
```

**Running Static Analysis:**

```bash
composer analyse
# or
vendor/bin/phpstan analyse
```

**Common Issues Detected:**
- Type mismatches (e.g., nullable types not handled)
- Missing method return types
- Undefined properties or methods
- Incorrect Eloquent relationship types

### 8.3 PHP_CodeSniffer Configuration

**Configuration File (phpcs.xml):**

```xml
<?xml version="1.0"?>
<ruleset name="SubWFour Coding Standard">
    <description>PSR-12 coding standard for SubWFour</description>

    <file>app</file>
    <file>config</file>
    <file>database</file>
    <file>routes</file>
    <file>tests</file>

    <exclude-pattern>vendor/*</exclude-pattern>
    <exclude-pattern>node_modules/*</exclude-pattern>
    <exclude-pattern>*.blade.php</exclude-pattern>

    <rule ref="PSR12"/>
</ruleset>
```

**Running Code Style Checks:**

```bash
composer phpcs       # Check for violations
composer phpcbf      # Auto-fix violations
```

**Common Issues Detected:**
- Incorrect indentation
- Missing or incorrect docblocks
- Line length violations
- Improper spacing

### 8.4 Laravel Debugbar

**Features:**
- Query monitoring and N+1 detection
- Request/response inspection
- Timeline and memory usage
- Exception tracking
- Route information

**Configuration:**
- Automatically enabled in development (`APP_DEBUG=true`)
- Automatically disabled in production for security

### 8.5 Composer Scripts

```json
{
    "scripts": {
        "analyse": "vendor/bin/phpstan analyse",
        "phpcs": "vendor/bin/phpcs",
        "phpcbf": "vendor/bin/phpcbf",
        "code-audit": [
            "@analyse",
            "@phpcs"
        ]
    }
}
```

**Running Full Code Audit:**

```bash
composer code-audit
```

### 8.6 Vulnerabilities Summary

| Category | Issues Found | Status |
|----------|-------------|--------|
| Type Safety | ~50+ hints | Informational |
| Code Style | Various PSR-12 violations | Auto-fixable |
| Security Vulnerabilities | None critical | N/A |

**Note:** PHPStan findings at level 5 are primarily type hints and best practices, not security vulnerabilities. The system follows secure coding practices.

**Screenshot Placeholder:** *[INSERT: Screenshot of PHPStan/PHPCS output]*

---

## 9. Testing

### 9.1 Testing Framework

The system uses **PHPUnit** for unit and feature testing, integrated through Laravel's testing utilities.

### 9.2 Test Categories

| Test Type | Purpose | Location |
|-----------|---------|----------|
| **Unit Tests** | Test individual components in isolation | `tests/Unit/` |
| **Feature Tests** | Test complete features and HTTP endpoints | `tests/Feature/` |
| **Authentication Tests** | Validate login, logout, session management | `tests/Feature/` |
| **Authorization Tests** | Verify role-based access controls | `tests/Feature/` |

### 9.3 Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/AuthenticationTest.php

# Run with coverage
php artisan test --coverage
```

### 9.4 Test Examples

**Authentication Test:**

```php
public function test_user_can_login_with_valid_credentials()
{
    $user = User::factory()->create([
        'password' => Hash::make('password123'),
        'is_active' => true,
    ]);

    $response = $this->post('/login', [
        'name' => $user->name,
        'password' => 'password123',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true, 'require_captcha' => true]);
}

public function test_user_cannot_login_with_invalid_credentials()
{
    $response = $this->post('/login', [
        'name' => 'invalid@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401);
}
```

**Authorization Test:**

```php
public function test_employee_cannot_access_admin_routes()
{
    $employee = User::factory()->create(['role' => 'employee']);

    $this->actingAs($employee)
         ->get('/admin/audit-logs')
         ->assertStatus(403);
}

public function test_admin_can_access_admin_routes()
{
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
         ->get('/admin/audit-logs')
         ->assertStatus(200);
}
```

**Screenshot Placeholder:** *[INSERT: Screenshot of test execution results]*

---

## 10. Security Policies

### 10.1 Password Policy

| Requirement | Value | Configuration Key |
|-------------|-------|-------------------|
| Minimum Length | 8 characters | `security.password.min_length` |
| Require Uppercase | Yes | `security.password.require_uppercase` |
| Require Lowercase | Yes | `security.password.require_lowercase` |
| Require Numbers | Yes | `security.password.require_numbers` |
| Require Special Characters | Yes | `security.password.require_special` |
| Password Expiry | 90 days | `security.password.expiry_days` |
| Password History | 5 previous passwords | `security.password.history_count` |
| Warning Before Expiry | 14 days | `security.password.warning_days` |

**Configuration (config/security.php):**

```php
'password' => [
    'min_length' => 8,
    'require_uppercase' => true,
    'require_lowercase' => true,
    'require_numbers' => true,
    'require_special' => true,
    'expiry_days' => 90,
    'history_count' => 5,
    'grace_period_days' => 7,
    'warning_days' => 14,
],
```

### 10.2 Login Attempt Policy

| Setting | Value | Purpose |
|---------|-------|---------|
| Max Failed Attempts | 3 | Attempts before lockout |
| Lockout Duration | 30 seconds | Initial lockout period |
| Progressive Lockout | Yes | Increases lockout time on repeated violations |
| Max Lockout Duration | 60 minutes | Maximum lockout time |
| CAPTCHA Requirement | After 3 attempts | Additional verification |
| Session Timeout | 120 minutes |    |

**Configuration:**

```php
'login' => [
    'max_attempts' => 3,
    'lockout_seconds' => 30,
    'progressive_lockout' => true,
    'max_lockout_minutes' => 60,
    'captcha_after_attempts' => 3,
    'session_timeout_minutes' => 120,
    'allow_concurrent_sessions' => false,
    'max_concurrent_sessions' => 1,
],
```

### 10.3 Data Handling Policy

**Encryption:**
- Employee SSS numbers encrypted at rest
- Contact information encrypted at rest
- Customer booking data encrypted
- All data exports optionally encrypted

**Data Retention:**

| Data Type | Retention Period |
|-----------|------------------|
| Activity Logs | 365 days |
| Audit Logs | 365 days |
| Login Attempts | 90 days |
| Security Incidents | 365 days |
| System Logs | 90 days |
| Sessions | 30 days |

### 10.4 Access Control Policy

**Role Definitions:**

```php
'access' => [
    'roles' => ['admin', 'employee', 'security'],
    
    'admin_only_routes' => [
        'audit_logs.index',
        'employees.store',
        'employees.update',
        'employees.destroy',
    ],
    
    'admin_only_features' => [
        'user_management',
        'system_configuration',
        'security_monitoring',
        'data_export',
    ],
    
    'log_violations' => true,
    'block_after_violations' => 10,
    'violation_window_minutes' => 30,
],
```

### 10.5 Logging and Monitoring Policy

**Events Logged:**
- All login attempts (success/failure)
- User logouts
- Password changes
- Profile updates
- All CRUD operations on data
- Administrative actions
- Security incidents

**Real-Time Alerts:**

```php
'alerts' => [
    'multiple_failed_logins' => true,
    'account_lockout' => true,
    'suspicious_activity' => true,
    'unauthorized_access' => true,
],
```

### 10.6 Backup and Recovery Policy

| Setting | Value |
|---------|-------|
| Backup Enabled | Yes |
| Frequency | Daily |
| Backup Time | 02:00 AM |
| Retention | 30 days |
| Include Database | Yes |
| Include Uploads | Yes |
| Compression | Yes |
| Encryption | Yes |

**Configuration:**

```php
'backup' => [
    'enabled' => true,
    'frequency' => 'daily',
    'time' => '02:00',
    'retention_days' => 30,
    'include' => [
        'database' => true,
        'uploads' => true,
        'logs' => false,
    ],
    'compress' => true,
    'encrypt' => true,
],
```

### 10.7 Security Headers Policy

All responses include security headers:

| Header | Value | Purpose |
|--------|-------|---------|
| X-Frame-Options | SAMEORIGIN | Prevent clickjacking |
| X-Content-Type-Options | nosniff | Prevent MIME sniffing |
| X-XSS-Protection | 1; mode=block | XSS filter |
| Referrer-Policy | strict-origin-when-cross-origin | Control referrer information |
| Strict-Transport-Security | max-age=31536000 | Force HTTPS |

---

## 11. Incident Response Plan

### 11.1 Detection

**Automated Detection:**

The `IncidentDetectionService` continuously monitors for security threats:

| Detection Type | Threshold | Severity |
|----------------|-----------|----------|
| **Brute Force** | 10+ failed logins/IP/hour | High-Critical |
| **Distributed Attack** | 5+ unique IPs, 10+ attempts/30min | Critical |
| **Account Compromise** | Login from new IP after failures | High |
| **Unusual Activity** | Admin login outside business hours | Low |

**Detection Implementation:**

```php
class IncidentDetectionService
{
    protected array $thresholds = [
        'failed_logins_per_ip' => 10,
        'failed_logins_per_user' => 5,
        'requests_per_minute' => 100,
        'suspicious_patterns_per_hour' => 3,
        'concurrent_sessions' => 5,
    ];

    public function runDetection(): array
    {
        return [
            'brute_force' => $this->detectBruteForceAttempts(),
            'distributed_attack' => $this->detectDistributedAttack(),
            'account_compromise' => $this->detectAccountCompromise(),
            'unusual_activity' => $this->detectUnusualActivity(),
        ];
    }
}
```

**Incident Types:**

| Type | Constant | Description |
|------|----------|-------------|
| Brute Force | `TYPE_BRUTE_FORCE` | Multiple failed login attempts |
| Suspicious Input | `TYPE_SUSPICIOUS_INPUT` | Detected XSS/SQL injection patterns |
| Rate Limit | `TYPE_RATE_LIMIT` | Excessive request rate |
| Unauthorized | `TYPE_UNAUTHORIZED` | Access attempt to restricted resource |
| SQL Injection | `TYPE_SQL_INJECTION` | SQL injection pattern detected |
| XSS Attempt | `TYPE_XSS_ATTEMPT` | Cross-site scripting attempt |
| Session Hijack | `TYPE_SESSION_HIJACK` | Potential session hijacking |
| Account Lockout | `TYPE_ACCOUNT_LOCKOUT` | Account locked due to failures |

### 11.2 Reporting

**Incident Recording:**

All detected incidents are automatically recorded in the `security_incidents` table:

```php
SecurityIncident::record(
    type: $incidentType,
    description: "Descriptive message about the incident",
    severity: SecurityIncident::SEVERITY_HIGH,
    userId: $affectedUserId,
    targetResource: '/affected/resource',
    metadata: [
        'ip_address' => $attackerIp,
        'attempts' => $attemptCount,
        'timeframe' => '1 hour',
    ]
);
```
    
**Severity Levels:**

| Level | Priority | Examples |
|-------|----------|----------|
| **Critical** | Immediate action required | Distributed attacks, mass brute force (20+ attempts) |
| **High** | Urgent response needed | Brute force (10+ attempts), account lockouts, unauthorized access |
| **Medium** | Investigation required | Suspicious input patterns, moderate failed logins |
| **Low** | Monitor and review | Unusual activity, off-hours admin logins |

**Threat Level Assessment:**

```php
public function getThreatLevel(): string
{
    $recentCritical = SecurityIncident::where('severity', 'critical')
        ->where('detected_at', '>=', now()->subHours(24))
        ->whereIn('status', ['open', 'investigating'])
        ->count();

    $recentHigh = SecurityIncident::where('severity', 'high')
        ->where('detected_at', '>=', now()->subHours(24))
        ->whereIn('status', ['open', 'investigating'])
        ->count();

    if ($recentCritical >= 3 || $recentHigh >= 10) {
        return 'critical';
    } elseif ($recentCritical >= 1 || $recentHigh >= 5) {
        return 'high';
    } elseif ($recentHigh >= 1) {
        return 'elevated';
    }

    return 'normal';
}
```

### 11.3 Containment

**Immediate Response Actions:**

| Trigger | Automatic Action |
|---------|-----------------|
| Max failed logins | Account lockout (30s - 60min progressive) |
| Brute force detected | IP logged, incident recorded |
| Suspicious input | Request sanitized, incident logged |
| Repeated violations | IP blocking |

**Manual Containment Steps:**

1. **Identify Scope:** Review incident details, affected users/resources
2. **Isolate Threat:** Block offending IPs, disable compromised accounts
3. **Preserve Evidence:** Export relevant logs before any changes
4. **Notify Stakeholders:** Alert administrators and security team

### 11.4 Recovery

**System Recovery Steps:**

1. **Verify Threat Elimination:**
   - Confirm malicious IPs are blocked
   - Verify compromised accounts are secured
   - Check for any persistent unauthorized access

2. **Restore Services:**
   - Unblock legitimate users affected by lockouts
   - Reset passwords for potentially compromised accounts
   - Re-enable temporarily disabled features

3. **Data Integrity Check:**
   - Review audit logs for unauthorized changes
   - Verify database integrity
   - Restore from backup if necessary

4. **Post-Incident Review:**
   - Document incident timeline
   - Identify root cause
   - Update security measures as needed
   - Brief relevant stakeholders

**Incident Resolution:**

```php
// Mark incident as resolved
$incident->update([
    'status' => 'resolved',
    'resolution_notes' => 'Description of resolution actions taken',
    'resolved_by' => Auth::id(),
    'resolved_at' => now(),
]);
```

### 11.5 Incident Dashboard

Security personnel can monitor all incidents through the Security Dashboard:

- Real-time threat level indicator
- Recent incidents list with severity
- Statistics: Total, Open, Investigating, Resolved
- Quick access to incident details and resolution actions

**Screenshot Placeholder:** *[INSERT: Screenshot of security incident dashboard]*

---

## Appendices

### Appendix A: File Structure Overview

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── EmployeeController.php
│   │   ├── SecurityController.php
│   │   ├── IncidentsController.php
│   │   └── ...
│   └── Middleware/
│       ├── SanitizeInput.php
│       ├── SecurityHeaders.php
│       ├── CheckRole.php
│       ├── CheckBlockedIp.php
│       ├── CheckPasswordExpiry.php
│       ├── EnforceSecurityPolicies.php
│       ├── ForceHttps.php
│       └── RateLimiter.php
├── Models/
│   ├── User.php
│   ├── SecurityIncident.php
│   ├── LoginAttempt.php
│   ├── SystemLog.php
│   ├── ActivityLog.php
│   └── ...
├── Services/
│   ├── EncryptionService.php
│   └── IncidentDetectionService.php
└── Providers/
    └── AppServiceProvider.php

config/
├── security.php        # Security policies configuration
├── auth.php           # Authentication configuration
└── ...

routes/
└── web.php            # Route definitions with middleware
```

### Appendix B: Configuration Files

| File | Purpose |
|------|---------|
| `.env` | Environment variables (credentials, keys) |
| `config/security.php` | Security policies |
| `phpstan.neon` | Static analysis configuration |
| `phpcs.xml` | Coding standards configuration |
| `phpunit.xml` | Testing configuration |

### Appendix C: Useful Commands

```bash
# Code auditing
composer analyse         # Run PHPStan analysis
composer phpcs          # Check coding standards
composer phpcbf         # Auto-fix coding standards
composer code-audit     # Run full code audit

# Testing
php artisan test        # Run all tests
php artisan test --coverage  # Run with coverage

# Maintenance
php artisan config:cache    # Cache configuration
php artisan route:cache     # Cache routes
php artisan view:cache      # Cache views
```

---

**Document Control:**

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | January 2025 | System Administrator | Initial documentation |

---

*This document is for internal use only. Do not distribute without authorization.*
