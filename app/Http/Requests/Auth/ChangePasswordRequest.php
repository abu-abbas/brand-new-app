<?php

namespace App\Http\Requests\Auth;

use App\Core\ErrorDefinition\Traits\HasErrorDefinitions;
use App\Errors\AuthError;
use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    use HasErrorDefinitions;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', new StrongPassword],
        ];
    }

    public function errorCodes(): array
    {
        return [
            'current_password.required' => AuthError::PASSWORD_REQUIRED,
            'current_password.string' => AuthError::PASSWORD_STRING,
            'password.required' => AuthError::PASSWORD_REQUIRED,
            'password.confirmed' => AuthError::PASSWORD_STRING,
            'password.app\rules\strongpassword' => AuthError::PASSWORD_INVALID,
        ];
    }
}
