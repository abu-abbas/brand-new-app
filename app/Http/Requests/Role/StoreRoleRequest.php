<?php

namespace App\Http\Requests\Role;

use App\Core\ErrorDefinition\Traits\HasErrorDefinitions;
use App\Errors\RoleError;
use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    use HasErrorDefinitions;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9_\-]+$/',
                'unique:tm_roles,v_code,NULL,i_id,dt_deleted_at,NULL',
            ],
            'name' => ['required', 'string', 'max:255'],
            'need_region' => ['sometimes', 'boolean'],
            'need_unit' => ['sometimes', 'boolean'],
            'active_periode' => ['nullable', 'array'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string'],
        ];
    }

    public function errorCodes(): array
    {
        return [
            'code.required' => RoleError::CODE_REQUIRED,
            'code.string' => RoleError::CODE_STRING,
            'code.max' => RoleError::CODE_MAX,
            'code.regex' => RoleError::CODE_FORMAT,
            'code.unique' => RoleError::CODE_UNIQUE,
            'name.required' => RoleError::NAME_REQUIRED,
            'name.string' => RoleError::NAME_STRING,
            'name.max' => RoleError::NAME_MAX,
            'need_region.boolean' => RoleError::NEED_REGION_BOOLEAN,
            'need_unit.boolean' => RoleError::NEED_UNIT_BOOLEAN,
            'active_periode.array' => RoleError::ACTIVE_PERIODE_ARRAY,
            'features.array' => RoleError::FEATURES_ARRAY,
            'features.*.string' => RoleError::FEATURES_ITEM_STRING,
        ];
    }
}
