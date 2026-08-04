<?php

namespace App\Rules;

use App\Models\User;
use App\Models\UserPasswordHistory;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

class StrongPassword implements ValidationRule
{
    public function __construct(
        private readonly ?User $user = null,
    ) {}

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

        if ($this->user) {
            if ($this->user->v_password && Hash::check($value, $this->user->v_password)) {
                $fail('Password baru tidak boleh sama dengan dua password terakhir Anda.');
                return;
            }

            $recentHistories = UserPasswordHistory::query()
                ->where('v_userid', $this->user->v_userid)
                ->orderBy('dt_created_at', 'desc')
                ->limit(2)
                ->get();

            foreach ($recentHistories as $history) {
                if (Hash::check($value, $history->v_password_hash)) {
                    $fail('Password baru tidak boleh sama dengan dua password terakhir Anda.');
                    return;
                }
            }
        }
    }
}
