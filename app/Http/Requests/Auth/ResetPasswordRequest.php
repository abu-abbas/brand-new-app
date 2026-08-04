<?php

namespace App\Http\Requests\Auth;

use App\Core\ErrorDefinition\Traits\HasErrorDefinitions;
use App\Errors\AuthError;
use App\Errors\UserManagementError;
use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    use HasErrorDefinitions;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email') && is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', new StrongPassword],
        ];
    }

    public function errorCodes(): array
    {
        return [
            'email.required' => UserManagementError::EMAIL_INVALID,
            'email.email' => UserManagementError::EMAIL_INVALID,
            'email.max' => UserManagementError::EMAIL_MAX,
            'token.required' => AuthError::TOKEN_INVALID,
            'token.string' => AuthError::TOKEN_INVALID,
            'password.required' => AuthError::PASSWORD_REQUIRED,
            'password.confirmed' => AuthError::PASSWORD_STRING,
            'password.app\rules\strongpassword' => AuthError::PASSWORD_INVALID,
        ];
    }
}
