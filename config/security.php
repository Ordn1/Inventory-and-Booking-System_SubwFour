<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Password Policy
    |--------------------------------------------------------------------------
    |
    | Define password complexity and rotation requirements.
    |
    */

    'password' => [
        // Minimum password length
        'min_length' => 8,
        
        // Require uppercase letters
        'require_uppercase' => true,
        
        // Require lowercase letters
        'require_lowercase' => true,
        
        // Require numeric characters
        'require_numbers' => true,
        
        // Require special characters
        'require_special' => true,
        
        // Password expiry in days (0 = never expires)
        'expiry_days' => 90,
        
        // Number of previous passwords to remember (prevent reuse)
        'history_count' => 5,
        
        // Grace period after expiry before forced change (days)
        'grace_period_days' => 7,
        
        // Warn user X days before expiry
        'warning_days' => 14,
    ],

    /*
    |--------------------------------------------------------------------------
    | Login Attempt Policy
    |--------------------------------------------------------------------------
    |
    | Define account lock rules after failed login attempts.
    |
    */

    'login' => [
        // Maximum failed attempts before lockout
        'max_attempts' => 5,
        
        // Lockout duration in minutes
        'lockout_minutes' => 15,
        
        // Progressive lockout (multiply lockout time after repeated lockouts)
        'progressive_lockout' => true,
        
        // Maximum lockout duration in minutes (for progressive)
        'max_lockout_minutes' => 60,
        
        // Require CAPTCHA after X failed attempts (0 = disabled)
        'captcha_after_attempts' => 3,
        
        // Session timeout in minutes
        'session_timeout_minutes' => 120,
        
        // Allow concurrent sessions
        'allow_concurrent_sessions' => false,
        
        // Maximum concurrent sessions (if allowed)
        'max_concurrent_sessions' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Handling Policy
    |--------------------------------------------------------------------------
    |
    | Define encryption and data access rules.
    |
    */

    'data' => [
        // Fields that must be encrypted at rest
        'encrypted_fields' => [
            'employees' => ['sss_number', 'contact_number'],
            'suppliers' => ['number'],
            'bookings' => ['email', 'contact_number'],
        ],
        
        // Sensitive data masking for display
        'masked_fields' => [
            'sss_number' => '***-**-####',
            'contact_number' => '*******####',
            'email' => 'a***z@domain.com',
        ],
        
        // Data retention periods (in days, 0 = indefinite)
        'retention' => [
            'activity_logs' => 365,
            'audit_logs' => 365,
            'login_attempts' => 90,
            'security_incidents' => 365,
            'security_logs' => 365,
            'system_logs' => 90,
            'sessions' => 30,
        ],
        
        // Require encryption for data exports
        'encrypt_exports' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Access Control Policy
    |--------------------------------------------------------------------------
    |
    | Define role-based access restrictions.
    |
    */

    'access' => [
        // Available roles in the system
        'roles' => ['admin', 'employee', 'security'],
        
        // Admin-only routes/resources
        'admin_only_routes' => [
            'audit_logs.index',
            'security.index',
            'system_logs.index',
            'employees.index',
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
        
        // Log unauthorized access attempts
        'log_violations' => true,
        
        // Block after X unauthorized attempts in Y minutes
        'block_after_violations' => 10,
        'violation_window_minutes' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging and Monitoring Policy
    |--------------------------------------------------------------------------
    |
    | Define what activities are logged and how.
    |
    */

    'logging' => [
        // Events to log
        'events' => [
            'login_success',
            'login_failure',
            'logout',
            'password_change',
            'profile_update',
            'data_access',
            'data_create',
            'data_update',
            'data_delete',
            'export_data',
            'admin_actions',
        ],
        
        // Log channels
        'security_channel' => 'security',
        'audit_channel' => 'audit',
        
        // Log retention in days
        'retention_days' => [
            'security' => 365,
            'audit' => 365,
            'error' => 90,
            'info' => 30,
        ],
        
        // Real-time alerts for critical events
        'alerts' => [
            'multiple_failed_logins' => true,
            'account_lockout' => true,
            'suspicious_activity' => true,
            'unauthorized_access' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup and Recovery Policy
    |--------------------------------------------------------------------------
    |
    | Define backup schedules and recovery procedures.
    |
    */

    'backup' => [
        // Backup enabled
        'enabled' => true,
        
        // Backup frequency (daily, weekly, monthly)
        'frequency' => 'daily',
        
        // Time to run backup (24-hour format)
        'time' => '02:00',
        
        // Backup retention in days
        'retention_days' => 30,
        
        // Include in backup
        'include' => [
            'database' => true,
            'uploads' => true,
            'logs' => false,
        ],
        
        // Backup storage disk
        'disk' => 'local',
        
        // Backup path
        'path' => 'backups',
        
        // Compress backups
        'compress' => true,
        
        // Encrypt backup files
        'encrypt' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers Policy
    |--------------------------------------------------------------------------
    |
    | Define HTTP security headers.
    |
    */

    'headers' => [
        'x_frame_options' => 'SAMEORIGIN',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'hsts_max_age' => 31536000,
        'hsts_include_subdomains' => true,
    ],

];
