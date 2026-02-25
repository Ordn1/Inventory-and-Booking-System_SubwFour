<?php

namespace App\Casts;

use App\Services\EncryptionService;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class Encrypted implements CastsAttributes
{
    /**
     * Cast the given value from the database.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        return EncryptionService::decrypt($value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        // Don't double-encrypt
        if (EncryptionService::isEncrypted($value)) {
            return $value;
        }

        return EncryptionService::encrypt($value);
    }
}
