# SubWFour System - Security Documentation Descriptions

This document provides narrative descriptions for each section of the security documentation, suitable for reports, presentations, or supplementary reading.

---

## 1. Project Overview

### System Description

SubWFour is a web-based inventory and service management system developed to address the needs of businesses requiring secure handling of sensitive data, comprehensive access controls, and real-time security monitoring capabilities. The system consolidates multiple business functions into a single unified platform, including inventory tracking, service booking management, employee records administration, and security incident monitoring. Built on modern web technologies, the system emphasizes security at every layer while maintaining usability for end users across different organizational roles.

### Purpose of the System

The primary purpose of SubWFour is to provide organizations with a secure and reliable platform for managing day-to-day business operations while protecting sensitive information from both internal and external threats. The system addresses common security challenges faced by businesses, including protection against SQL injection attacks, cross-site scripting vulnerabilities, unauthorized access attempts, and brute force login attacks. By implementing industry-standard security practices and maintaining comprehensive audit logs, the system enables organizations to meet compliance requirements while ensuring operational efficiency.

### Intended Users

The system is designed to serve three distinct user categories, each with specific responsibilities and access levels. Administrators possess full system access and are responsible for user management, system configuration, security monitoring, and reviewing audit logs. They have the authority to create, modify, and deactivate user accounts, as well as configure security policies. Employees represent the standard operational users who interact with the system for daily business activities such as managing inventory, processing bookings, and viewing their own profile information. Security personnel occupy a specialized role focused on monitoring security incidents, reviewing system logs, tracking login attempts, and assessing the overall threat level facing the organization.

### Platform and Technology

SubWFour is built using PHP 8.2 with the Laravel 12 framework, leveraging Laravel's robust security features and elegant syntax for rapid development. The application stores data in a MySQL database, utilizing Laravel's Eloquent ORM for secure and efficient database operations. The frontend is rendered using Blade templates with custom CSS and JavaScript for interactive functionality. For secure local development and testing, the system integrates with ngrok to provide HTTPS tunneling capabilities. Code quality is maintained through integrated auditing tools including Larastan for static analysis, PHP_CodeSniffer for coding standards enforcement, and Laravel Debugbar for runtime debugging.

---

## 2. Secure Coding Practices

### Environment Variables and Credential Management

The system follows security best practices by storing all sensitive configuration values in environment variables rather than hardcoding them in source code. Database credentials, API keys, encryption keys, and other sensitive information are defined in the environment configuration file, which is excluded from version control to prevent accidental exposure. This approach allows deployment across different environments without modifying source code and ensures that credentials are never committed to repositories where they could be discovered by unauthorized parties.

### Configuration Security

In production environments, the system utilizes Laravel's configuration caching mechanism to compile all configuration files into a single cached file. This prevents direct reading of the environment file during runtime and improves application performance. The cached configuration is regenerated only during deployment, reducing the attack surface during normal operation.

### Cross-Site Request Forgery Protection

Every form submission in the system includes a CSRF token that Laravel validates automatically. This protection prevents malicious websites from executing unauthorized actions on behalf of authenticated users. The token is generated for each user session and must be included in all POST, PUT, PATCH, and DELETE requests for them to be processed successfully.

### Mass Assignment Protection

All Eloquent models in the system explicitly define which attributes can be mass-assigned through the fillable property. This prevents attackers from injecting unexpected fields into database operations. Sensitive attributes such as passwords and tokens are hidden from array and JSON representations to prevent accidental exposure in API responses or logs.

---

## 3. Authentication and Authorization

### Login Process

The authentication process implements a multi-step verification flow designed to prevent unauthorized access while maintaining user experience. When a user submits login credentials, the system first checks whether the account or IP address is currently locked out due to previous failed attempts. If access is permitted, the credentials are validated against the database. Upon successful validation, the system verifies that the user account is active before proceeding to a CAPTCHA verification step. Only after completing the CAPTCHA successfully is the user granted access and redirected to their role-appropriate dashboard.

### Password Security

User passwords are hashed using the bcrypt algorithm, which is specifically designed for password storage. Unlike fast hashing algorithms, bcrypt is computationally intensive, making brute force attacks impractical even if the password hashes are compromised. The system automatically hashes passwords when they are assigned to user models, and password verification is performed using timing-safe comparison functions to prevent timing attacks.

### Password History and Rotation

To enhance password security, the system maintains a history of each user's previous passwords. When a user attempts to change their password, the system checks whether the new password matches any of the recent passwords stored in their history. This prevents password reuse, which is a common security weakness. Additionally, passwords are configured to expire after a set period, prompting users to create new passwords regularly.

### Role-Based Access Control

The system implements role-based access control with three predefined roles. Administrators have unrestricted access to all system features including user management, security configuration, and audit log review. Employees have access limited to operational features necessary for their daily work, such as inventory management and booking processing. Security personnel have access to security-specific features including incident monitoring, system logs, and threat assessment dashboards. Each role is assigned during user creation and determines which routes and features the user can access.

---

## 4. Data Encryption

### Encryption at Rest

Sensitive data stored in the database is encrypted using AES-256-CBC encryption, one of the strongest symmetric encryption algorithms available. The system provides a dedicated encryption service that handles all encryption and decryption operations, ensuring consistent implementation across the application. Encrypted data is stored in its ciphertext form and decrypted only when needed for display or processing.

### Encrypted Data Categories

The system encrypts several categories of sensitive information. Employee records containing personal identification numbers such as SSS numbers and contact information are encrypted before storage. Supplier contact details are similarly protected. Customer information collected during booking processes, including email addresses and phone numbers, is encrypted to protect customer privacy and comply with data protection requirements.

### Data Masking

When sensitive information needs to be displayed to users, the system applies masking to show only a portion of the actual data. For example, identification numbers are displayed with most digits replaced by asterisks, showing only the last few characters. Email addresses are masked by replacing most of the username portion with asterisks while preserving the domain. This allows users to verify which record they are viewing without exposing the full sensitive data.

### Transport Layer Security

All communication between users and the system is encrypted using HTTPS with TLS encryption. In production, this is enforced through server configuration and HTTP Strict Transport Security headers that instruct browsers to always use secure connections. During development, ngrok provides HTTPS tunneling to enable testing of secure features in local environments.

---

## 5. Input Validation and Sanitization

### Server-Side Validation

All user inputs are validated on the server side before processing, regardless of any client-side validation that may exist. The system uses Laravel's validation system to enforce rules such as required fields, data types, format patterns, uniqueness constraints, and acceptable value ranges. Validation errors are returned to users with clear messages explaining what needs to be corrected, without revealing internal system details.

### Input Sanitization Middleware

A dedicated middleware processes all incoming requests to neutralize potentially malicious content before it reaches the application logic. This middleware scans input values for patterns commonly used in SQL injection attacks, including union queries, select statements, and boolean-based injection attempts. It also detects and removes cross-site scripting payloads such as script tags, event handlers, and javascript URLs.

### SQL Injection Prevention

The system uses Laravel's Eloquent ORM and Query Builder for all database operations, which automatically parameterize queries to prevent SQL injection. User input is never concatenated directly into SQL statements. When raw queries are necessary, parameter binding is used to ensure that user input is properly escaped and cannot alter the query structure.

### Cross-Site Scripting Prevention

Output encoding is applied automatically to all data rendered in Blade templates. The default output syntax escapes HTML entities, preventing stored or reflected XSS attacks. Only explicitly trusted content is rendered without escaping, and such instances are carefully reviewed to ensure the content source is safe.

---

## 6. Error Handling and Logging

### Secure Error Display

In production environments, the system displays generic error messages to users that acknowledge an error occurred without revealing technical details that could assist attackers. Stack traces, database queries, and internal paths are logged for administrator review but never shown to end users. This prevents information disclosure that could be exploited to craft more targeted attacks.

### Comprehensive Logging System

The system maintains multiple log channels for different types of events. Security logs capture authentication events, security violations, and detected threats. Audit logs record user actions and data modifications for compliance and forensic purposes. Error logs capture system exceptions and failures for troubleshooting. Information logs track general system events for operational monitoring.

### Logged Information

Each log entry captures contextual information including the user who performed the action if authenticated, the IP address and user agent of the request, the URL and HTTP method being accessed, and a timestamp. This information enables security personnel to investigate incidents, identify patterns, and trace the sequence of events leading to any issues.

### Log Retention

Different log types are retained for varying periods based on their purpose and compliance requirements. Security and audit logs are retained for one year to support security investigations and compliance audits. Error logs are retained for 90 days to facilitate troubleshooting of recurring issues. Information logs are retained for 30 days as they primarily serve operational monitoring purposes.

---

## 7. Access Control

### Route Protection

System routes are protected using middleware that verifies authentication and authorization before allowing access. Unauthenticated users are redirected to the login page when attempting to access protected routes. Authenticated users are further checked against role requirements, with unauthorized access attempts being logged and resulting in access denied responses.

### Role Verification Middleware

The role checking middleware validates that the authenticated user has one of the required roles for the requested route or resource. If the user's role does not match the requirements, the system records a security incident noting the unauthorized access attempt, including the user, resource, and timestamp. The user receives an access denied message without details about what roles would be required.

### Session Management

User sessions are managed securely to prevent session-related attacks. Upon successful login, the session identifier is regenerated to prevent session fixation attacks where an attacker could set a session ID before the user authenticates. Sessions expire after a configurable period of inactivity, requiring users to re-authenticate. Concurrent session restrictions prevent the same account from being used simultaneously from multiple locations.

### IP-Based Access Control

The system tracks IP addresses associated with failed login attempts and other security violations. After exceeding configured thresholds, IP addresses can be temporarily or permanently blocked from accessing the system. This provides an additional layer of defense against distributed attacks and persistent malicious actors.

---

## 8. Code Auditing Tools

### Static Analysis with Larastan

Larastan, which extends PHPStan for Laravel-specific analysis, is integrated into the development workflow to identify potential issues before code reaches production. Running at level 5 analysis, the tool checks for type safety issues, incorrect method calls, undefined properties, and other code quality problems. While not all findings represent security vulnerabilities, maintaining high code quality reduces the likelihood of bugs that could create security weaknesses.

### Coding Standards with PHP_CodeSniffer

PHP_CodeSniffer enforces the PSR-12 coding standard across the codebase. Consistent code formatting and style make the codebase easier to review for security issues and reduces the likelihood of subtle bugs caused by formatting ambiguities. The tool can automatically fix many violations, streamlining the process of maintaining coding standards.

### Runtime Debugging with Laravel Debugbar

During development, Laravel Debugbar provides detailed information about request processing, database queries, memory usage, and execution timing. This helps developers identify inefficient queries that could indicate design problems and verify that security features are functioning as expected. The debugbar is automatically disabled in production to prevent information disclosure.

### Audit Execution

Code auditing tools are executed through Composer scripts, allowing developers to easily run analysis with standardized configurations. A combined audit script runs both static analysis and coding standards checks in sequence, providing a comprehensive assessment of code quality. These audits are recommended before committing code and can be integrated into continuous integration pipelines.

---

## 9. Testing

### Testing Framework

The system uses PHPUnit as its testing framework, integrated through Laravel's testing utilities that provide convenient methods for testing web applications. Tests are organized into unit tests that verify individual components in isolation and feature tests that verify complete user workflows including HTTP requests and responses.

### Authentication Testing

Tests verify that the authentication system correctly handles valid and invalid login attempts, account lockouts, and session management. Test cases confirm that valid credentials with completed CAPTCHA result in successful authentication, while invalid credentials result in appropriate error responses. Lockout behavior is tested to ensure accounts are temporarily inaccessible after exceeding the failed attempt threshold.

### Authorization Testing

Role-based access control is verified through tests that confirm each role can access permitted resources while being denied access to restricted resources. Tests authenticate as users with different roles and attempt to access various routes, verifying that the system correctly permits or denies access based on the role configuration.

### Security Feature Testing

Tests verify that security features function correctly, including input sanitization that removes malicious content, encryption that properly protects and recovers data, and logging that records expected events. These tests ensure that security mechanisms remain effective as the codebase evolves.

---

## 10. Security Policies

### Password Policy

The password policy establishes requirements for password complexity and management. Passwords must be at least eight characters long and include a combination of uppercase letters, lowercase letters, numbers, and special characters. This complexity requirement makes passwords more resistant to dictionary attacks and brute force attempts. Passwords expire after 90 days, requiring users to create new passwords regularly. The system remembers the five most recent passwords and prevents their reuse.

### Login Attempt Policy

To protect against brute force attacks, the system limits the number of consecutive failed login attempts. After three failed attempts, the account is temporarily locked for an initial period. If failed attempts continue after the lockout expires, the lockout duration increases progressively up to a maximum of 60 minutes. CAPTCHA verification is required after reaching the attempt threshold, adding an additional barrier against automated attacks.

### Data Handling Policy

The data handling policy defines how sensitive information is protected throughout its lifecycle. Specific fields containing personal identification numbers, contact information, and customer data are encrypted before database storage. Access to sensitive data is restricted based on user roles and job requirements. Data retention periods are defined for each data type, with logs and records being purged after their retention period expires.

### Access Control Policy

The access control policy defines which resources each role can access and what actions they can perform. Administrative functions including user management, system configuration, and security monitoring are restricted to administrator accounts. Operational features for inventory and booking management are available to employees. Security monitoring features are accessible to both administrators and security personnel. All unauthorized access attempts are logged for review.

### Logging and Monitoring Policy

The logging and monitoring policy ensures that system activities are recorded for security analysis and compliance purposes. All security-relevant events including authentication attempts, access violations, and configuration changes are logged. Logs include sufficient detail to investigate incidents including timestamps, user identifiers, IP addresses, and action descriptions. Log retention periods are defined to balance storage requirements with investigation needs.

### Backup and Recovery Policy

The backup policy ensures that data can be recovered in case of system failure, data corruption, or security incidents. Database backups are performed daily during off-peak hours. Backups are retained for 30 days, allowing recovery from issues that may not be immediately discovered. Backup files are compressed and encrypted to protect their contents. The backup process includes the database and user-uploaded files but excludes logs which are retained separately.

---

## 11. Incident Response Plan

### Detection Phase

Security incidents are detected through automated monitoring and user reports. The incident detection service runs periodic checks for suspicious patterns including excessive failed login attempts from single IP addresses, distributed attacks from multiple IPs targeting the same accounts, successful logins from new IP addresses following failed attempts, and administrative access during unusual hours. Detected incidents are recorded with severity levels ranging from low to critical based on the nature and scope of the threat.

### Reporting Phase

When an incident is detected, it is automatically recorded in the security incidents database with comprehensive details. Each incident record includes the type of incident, a description of what was detected, the severity assessment, affected users or resources, source IP address and user agent, and the detection timestamp. Security personnel can view all incidents through the security dashboard and filter by status, severity, or type.

### Containment Phase

Immediate containment actions are automated where possible. Accounts are locked after excessive failed attempts to prevent brute force success. IP addresses generating suspicious traffic can be blocked. Sessions can be terminated if account compromise is suspected. For incidents requiring manual intervention, security personnel follow documented procedures to isolate threats, disable compromised accounts, and preserve evidence for investigation.

### Recovery Phase

After an incident is contained, recovery procedures restore normal operations while preventing recurrence. Legitimate users affected by lockouts or blocks are restored after verification. Compromised accounts have passwords reset and sessions invalidated. System configurations are reviewed to identify any unauthorized changes. Audit logs are examined to determine the full scope of any unauthorized access. Lessons learned are documented and used to improve security measures.

### Threat Level Assessment

The system maintains an overall threat level assessment based on recent incident activity. When multiple high or critical severity incidents are detected within a 24-hour period, the threat level is elevated to alert security personnel to increased risk. The threat level is displayed on the security dashboard and can be used to trigger additional security measures or notifications as defined by organizational policy.

---

## Conclusion

This documentation describes the comprehensive security measures implemented in the SubWFour system. The multi-layered approach to security encompasses secure coding practices, robust authentication and authorization mechanisms, data encryption for sensitive information, thorough input validation and sanitization, secure error handling with comprehensive logging, strict access controls, regular code auditing, systematic testing, clearly defined security policies, and a structured incident response plan. Together, these measures provide defense in depth against common security threats while supporting compliance requirements and operational needs.

---

*This document accompanies the main Security Documentation and provides narrative descriptions suitable for reports and presentations.*
