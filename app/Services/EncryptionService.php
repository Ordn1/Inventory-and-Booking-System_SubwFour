<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class EncryptionService
{
    /**
     * Encrypt a value using Laravel's built-in encryption (AES-256-CBC)
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
        if (is_null($encryptedValue) || $encryptedValue === '') {
            return null;
        }

        try {
            return Crypt::decryptString($encryptedValue);
        } catch (DecryptException $e) {
            // Log decryption failure for security monitoring
            \Log::warning('Decryption failed', [
                'error' => $e->getMessage(),
                'ip' => request()->ip(),
            ]);
            return null;
        }
    }

    /**
     * Check if a value is encrypted
     */
    public static function isEncrypted(?string $value): bool
    {
        if (is_null($value) || $value === '') {
            return false;
        }

        try {
            Crypt::decryptString($value);
            return true;
        } catch (DecryptException $e) {
            return false;
        }
    }

    /**
     * Encrypt an array of values
     */
    public static function encryptArray(array $data, array $fieldsToEncrypt): array
    {
        foreach ($fieldsToEncrypt as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $data[$field] = self::encrypt($data[$field]);
            }
        }
        return $data;
    }

    /**
     * Decrypt an array of values
     */
    public static function decryptArray(array $data, array $fieldsToDecrypt): array
    {
        foreach ($fieldsToDecrypt as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $data[$field] = self::decrypt($data[$field]);
            }
        }
        return $data;
    }

    /**
     * Hash sensitive data for comparison purposes (one-way)
     */
    public static function hash(string $value): string
    {
        return hash('sha256', $value . config('app.key'));
    }

    /**
     * Verify a value against its hash
     */
    public static function verifyHash(string $value, string $hash): bool
    {
        return hash_equals($hash, self::hash($value));
    }

    /**
     * Mask sensitive data for display (e.g., SSS number: ***-**-1234)
     */
    public static function mask(string $value, int $visibleChars = 4, string $maskChar = '*'): string
    {
        $length = strlen($value);
        
        if ($length <= $visibleChars) {
            return str_repeat($maskChar, $length);
        }

        $masked = str_repeat($maskChar, $length - $visibleChars);
        return $masked . substr($value, -$visibleChars);
    }

    /**
     * Mask an email address (e.g., j***@example.com)
     */
    public static function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        
        if (count($parts) !== 2) {
            return self::mask($email);
        }

        $username = $parts[0];
        $domain = $parts[1];

        if (strlen($username) <= 2) {
            $maskedUsername = $username[0] . '***';
        } else {
            $maskedUsername = $username[0] . str_repeat('*', strlen($username) - 2) . substr($username, -1);
        }

        return $maskedUsername . '@' . $domain;
    }
}
