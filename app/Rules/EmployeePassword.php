<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Employee Password Rule
 * 
 * Requirements:
 * - 12-18 characters in length
 * - At least 1 uppercase letter
 * - At least 1 lowercase letter
 * - At least 1 number
 * - Alphanumeric only (no special characters)
 */
class EmployeePassword implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $password = $value;

        // Check length (12-18 characters)
        $length = strlen($password);
        if ($length < 12) {
            $fail('Password must be at least 12 characters long. Currently: ' . $length . ' characters.');
            return;
        }
        
        if ($length > 18) {
            $fail('Password must not exceed 18 characters. Currently: ' . $length . ' characters.');
            return;
        }

        // Check for special characters (only alphanumeric allowed)
        if (!preg_match('/^[a-zA-Z0-9]+$/', $password)) {
            $fail('Password must contain only letters and numbers. Special characters are not allowed.');
            return;
        }

        // Check for at least 1 uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            $fail('Password must contain at least 1 uppercase letter (A-Z).');
            return;
        }

        // Check for at least 1 lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            $fail('Password must contain at least 1 lowercase letter (a-z).');
            return;
        }

        // Check for at least 1 number
        if (!preg_match('/[0-9]/', $password)) {
            $fail('Password must contain at least 1 number (0-9).');
            return;
        }
    }
}
