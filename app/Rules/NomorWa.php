<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NomorWa implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! User::normalisasiWa($value)) {
            $fail('Nomor WhatsApp tidak valid. Contoh: 0812-3456-7890 atau +62 812 3456 7890.');
        }
    }
}
