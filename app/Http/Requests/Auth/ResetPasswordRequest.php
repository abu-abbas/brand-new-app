<?php

namespace App\Http\Requests\Auth;

use App\Core\ErrorDefinition\Traits\HasErrorDefinitions;
use App\Errors\AuthError;
use App\Errors\UserManagementError;
use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    use HasErrorDefinitions;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $email = $this->input('email');
        $user = $email
            ? User::query()->where('v_email', $email)->whereNull('dt_deleted_at')->first()
            : null;

        return [
            'email' => ['required', 'email', 'max:255'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', new StrongPassword($user)],
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
            'password.app\rules\strongpassword' => AuthError::PASSWORD_REUSED,
        ];
    }
}
