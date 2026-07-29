<?php

namespace App\Http\Requests\Auth;

use App\Core\ErrorDefinition\Traits\HasErrorDefinitions;
use App\Errors\AuthError;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class LoginRequest extends FormRequest
{
    use HasErrorDefinitions;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $presence = config('captcha.disable') ? 'nullable' : 'required';

        return [
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'captcha_key' => [$presence, 'string', 'size:60'],
            'captcha' => [$presence, 'string', 'max:10'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (
                    config('captcha.disable')
                    ||
                    $validator->errors()->hasAny(['captcha', 'captcha_key'])
                    || captcha_api_check($this->string('captcha'), $this->string('captcha_key'), 'flat')
                ) {
                    return;
                }

                $this->addValidationError(
                    $validator,
                    'captcha',
                    'captcha_api',
                    AuthError::CAPTCHA_INVALID,
                );
            },
        ];
    }

    public function errorCodes(): array
    {
        return [
            'username.required' => AuthError::USERNAME_REQUIRED,
            'username.string' => AuthError::USERNAME_STRING,
            'username.max' => AuthError::USERNAME_MAX,
            'password.required' => AuthError::PASSWORD_REQUIRED,
            'password.string' => AuthError::PASSWORD_STRING,
            'password.max' => AuthError::PASSWORD_MAX,
            'captcha_key.required' => AuthError::CAPTCHA_KEY_REQUIRED,
            'captcha_key.string' => AuthError::CAPTCHA_KEY_STRING,
            'captcha_key.size' => AuthError::CAPTCHA_KEY_SIZE,
            'captcha.required' => AuthError::CAPTCHA_REQUIRED,
            'captcha.string' => AuthError::CAPTCHA_STRING,
            'captcha.max' => AuthError::CAPTCHA_MAX,
        ];
    }
}
