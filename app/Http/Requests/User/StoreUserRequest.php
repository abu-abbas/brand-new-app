<?php

namespace App\Http\Requests\User;

use App\Core\ErrorDefinition\Traits\HasErrorDefinitions;
use App\Errors\UserManagementError;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
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
            'userid' => ['required', 'string', 'max:100', 'unique:tm_users,v_userid'],
            'username' => ['required', 'string', 'max:255'],
            'email' => [
                'required_if:is_external,false',
                'nullable',
                'email',
                'max:255',
                Rule::unique('tm_users', 'v_email'),
            ],
            'unit_code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['required', 'boolean'],
            'is_external' => ['required', 'boolean'],
            'roles' => ['nullable', 'array'],
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
            'userid.required' => UserManagementError::USER_ID_REQUIRED,
            'userid.string' => UserManagementError::USER_ID_STRING,
            'userid.max' => UserManagementError::USER_ID_MAX,
            'userid.unique' => UserManagementError::USER_ID_ALREADY_EXISTS,

            'username.required' => UserManagementError::USERNAME_REQUIRED,
            'username.string' => UserManagementError::USERNAME_STRING,
            'username.max' => UserManagementError::USERNAME_MAX,

            'email.required_if' => UserManagementError::EMAIL_REQUIRED,
            'email.email' => UserManagementError::EMAIL_INVALID,
            'email.max' => UserManagementError::EMAIL_MAX,
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
