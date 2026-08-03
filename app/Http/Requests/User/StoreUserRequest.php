<?php

namespace App\Http\Requests\User;

use App\Core\ErrorDefinition\Traits\HasErrorDefinitions;
use App\Errors\UserManagementError;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    use HasErrorDefinitions;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'userid' => $this->userid ?? $this->v_userid,
            'username' => $this->username ?? $this->v_username ?? $this->userid ?? $this->v_userid,
            'email' => $this->email ?? $this->v_email,
            'unit_code' => $this->unit_code ?? $this->v_kolok,
            'password' => $this->password ?? $this->v_password,
            'is_active' => $this->is_active ?? $this->b_is_active ?? true,
            'is_external' => $this->is_external ?? $this->b_use_other ?? false,
        ]);

        if ($this->has('roles') && is_array($this->roles)) {
            $mappedRoles = array_map(function ($r) {
                if (! is_array($r)) {
                    return $r;
                }

                return [
                    'role_code' => $r['role_code'] ?? $r['v_role_code'] ?? null,
                    'wilayah' => $r['wilayah'] ?? $r['v_wilayah'] ?? null,
                    'unit' => $r['unit'] ?? $r['v_unit'] ?? null,
                    'pelaksana' => $r['pelaksana'] ?? $r['v_pelaksana'] ?? null,
                    'valid_from' => $r['valid_from'] ?? $r['dt_valid_from'] ?? null,
                    'valid_until' => $r['valid_until'] ?? $r['dt_valid_until'] ?? null,
                ];
            }, $this->roles);
            $this->merge(['roles' => $mappedRoles]);
        }
    }

    public function rules(): array
    {
        return [
            'userid' => ['required', 'string', 'max:100', 'unique:tm_users,v_userid'],
            'username' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'unit_code' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:6'],
            'is_active' => ['nullable', 'boolean'],
            'is_external' => ['nullable', 'boolean'],
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

            'email.email' => UserManagementError::EMAIL_INVALID,
            'email.max' => UserManagementError::EMAIL_MAX,

            'unit_code.string' => UserManagementError::UNIT_STRING,
            'unit_code.max' => UserManagementError::UNIT_MAX,

            'password.string' => UserManagementError::PASSWORD_STRING,
            'password.min' => UserManagementError::PASSWORD_MIN,

            'is_active.boolean' => UserManagementError::IS_ACTIVE_BOOLEAN,
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
