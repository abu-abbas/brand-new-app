<?php

namespace App\Http\Requests\User;

use App\Core\ErrorDefinition\Traits\HasErrorDefinitions;
use App\Errors\UserManagementError;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $user = $this->route('user');
        $uniqueEmail = Rule::unique('tm_users', 'v_email');
        $emailRules = ['sometimes', 'nullable', 'email', 'max:255', $uniqueEmail];

        if ($user instanceof User) {
            $targetIsExternal = $user->b_use_other;
            if ($this->has('is_external')) {
                $parsedExternal = filter_var($this->input('is_external'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if (is_bool($parsedExternal)) {
                    $targetIsExternal = $parsedExternal;
                }
            }

            $emailRules[4] = $uniqueEmail->ignore($user->i_id, 'i_id');
            if (! $targetIsExternal && ($this->has('email') || empty($user->v_email))) {
                $emailRules[0] = 'required';
            }
        }

        return [
            'username' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => $emailRules,
            'unit_code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'required', 'boolean'],
            'is_external' => ['sometimes', 'required', 'boolean'],
            'roles' => ['sometimes', 'array'],
            'roles.*.role_code' => ['required', 'string', 'exists:tm_roles,v_code'],
            'roles.*.wilayah' => ['nullable', 'string', 'max:50'],
            'roles.*.unit' => ['nullable', 'string', 'max:50'],
            'roles.*.pelaksana' => ['nullable', 'string', 'max:10'],
            'roles.*.valid_from' => ['nullable', 'date'],
            'roles.*.valid_until' => ['nullable', 'date', 'after_or_equal:roles.*.valid_from'],
        ];
    }

    public function errorCodes(): array
    {
        return [
            'username.required' => UserManagementError::USERNAME_REQUIRED,
            'username.string' => UserManagementError::USERNAME_STRING,
            'username.max' => UserManagementError::USERNAME_MAX,

            'email.email' => UserManagementError::EMAIL_INVALID,
            'email.max' => UserManagementError::EMAIL_MAX,
            'email.required' => UserManagementError::EMAIL_REQUIRED,
            'email.illuminate\validation\rules\unique' => UserManagementError::EMAIL_UNIQUE,

            'unit_code.string' => UserManagementError::UNIT_STRING,
            'unit_code.max' => UserManagementError::UNIT_MAX,

            'is_active.required' => UserManagementError::IS_ACTIVE_REQUIRED,
            'is_active.boolean' => UserManagementError::IS_ACTIVE_BOOLEAN,
            'is_external.required' => UserManagementError::USE_OTHER_REQUIRED,
            'is_external.boolean' => UserManagementError::USE_OTHER_BOOLEAN,

            'roles.array' => UserManagementError::ROLES_ARRAY,
            'roles.*.role_code.required' => UserManagementError::ROLE_CODE_REQUIRED,
            'roles.*.role_code.string' => UserManagementError::ROLE_CODE_STRING,
            'roles.*.role_code.exists' => UserManagementError::ROLE_CODE_EXISTS,

            'roles.*.wilayah.string' => UserManagementError::WILAYAH_STRING,
            'roles.*.wilayah.max' => UserManagementError::WILAYAH_MAX,

            'roles.*.unit.string' => UserManagementError::UNIT_STRING,
            'roles.*.unit.max' => UserManagementError::UNIT_MAX,

            'roles.*.pelaksana.string' => UserManagementError::PELAKSANA_STRING,
            'roles.*.pelaksana.max' => UserManagementError::PELAKSANA_MAX,

            'roles.*.valid_from.date' => UserManagementError::VALID_FROM_DATE,
            'roles.*.valid_until.date' => UserManagementError::VALID_UNTIL_DATE,
            'roles.*.valid_until.after_or_equal' => UserManagementError::VALID_UNTIL_AFTER_OR_EQUAL,
        ];
    }
}
