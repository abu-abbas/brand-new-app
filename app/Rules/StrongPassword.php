<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Password harus berupa teks.');

            return;
        }

        if (mb_strlen($value) < 8 || mb_strlen($value) > 255) {
            $fail('Password harus terdiri dari 8 hingga 255 karakter.');

            return;
        }

        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*()_+\-.]).{8,255}$/';
        if (! preg_match($pattern, $value)) {
            $fail('Password harus mengandung huruf besar, huruf kecil, angka, dan simbol (!@#$%^&*()_+.-).');

            return;
        }

    }
}
