<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    /**
     * Password policy configuration
     */
    protected int $minLength = 8;
    protected bool $requireUppercase = true;
    protected bool $requireLowercase = true;
    protected bool $requireNumbers = true;
    protected bool $requireSpecialChars = true;

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Skip validation if value is empty (for nullable password updates)
        if (empty($value)) {
            return;
        }

        $errors = [];

        if (strlen($value) < $this->minLength) {
            $errors[] = "at least {$this->minLength} characters";
        }

        if ($this->requireUppercase && !preg_match('/[A-Z]/', $value)) {
            $errors[] = "at least one uppercase letter";
        }

        if ($this->requireLowercase && !preg_match('/[a-z]/', $value)) {
            $errors[] = "at least one lowercase letter";
        }

        if ($this->requireNumbers && !preg_match('/[0-9]/', $value)) {
            $errors[] = "at least one number";
        }

        if ($this->requireSpecialChars && !preg_match('/[!@#$%^&*(),.?":{}|<>_\-+=\[\]\\\\\/]/', $value)) {
            $errors[] = "at least one special character (!@#$%^&*(),.?\":{}|<>)";
        }

        // Check for common weak passwords
        $weakPasswords = [
            'password', 'password123', '12345678', 'qwerty123', 'admin123',
            'letmein', 'welcome', 'monkey123', 'dragon123', 'master123'
        ];
        
        if (in_array(strtolower($value), $weakPasswords)) {
            $errors[] = "not be a commonly used password";
        }

        if (!empty($errors)) {
            $fail("The :attribute must contain " . implode(', ', $errors) . ".");
        }
    }
}
