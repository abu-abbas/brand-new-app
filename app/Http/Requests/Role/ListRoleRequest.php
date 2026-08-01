<?php

namespace App\Http\Requests\Role;

use App\Core\ErrorDefinition\Traits\HasErrorDefinitions;
use App\Errors\RoleError;
use Illuminate\Foundation\Http\FormRequest;

class ListRoleRequest extends FormRequest
{
    use HasErrorDefinitions;

    private const FIELDS = [
        'code', 'name', 'features', 'need_region', 'need_unit', 'locked', 'updated_at', 'deleted_at',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
            'search_fields' => ['nullable', 'array'],
            'search_fields.*' => ['string', 'in:'.implode(',', self::FIELDS)],
            'sort_by' => ['nullable', 'string', 'in:'.implode(',', self::FIELDS)],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'include_deleted' => ['nullable', 'string', 'in:true,false,1,0'],
            'updated_at_from' => ['nullable', 'date_format:Y-m-d'],
            'updated_at_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:updated_at_from'],
        ];
    }

    public function errorCodes(): array
    {
        return [
            'page.integer' => RoleError::INVALID_PAGE_TYPE,
            'page.min' => RoleError::INVALID_PAGE_MIN,
            'per_page.integer' => RoleError::INVALID_PER_PAGE_TYPE,
            'per_page.min' => RoleError::INVALID_PER_PAGE_MIN,
            'per_page.max' => RoleError::INVALID_PER_PAGE_MAX,
            'search.string' => RoleError::INVALID_SEARCH_TYPE,
            'search.max' => RoleError::INVALID_SEARCH_MAX,
            'search_fields.array' => RoleError::INVALID_SEARCH_FIELDS_TYPE,
            'search_fields.*.string' => RoleError::INVALID_SEARCH_FIELD_TYPE,
            'search_fields.*.in' => RoleError::INVALID_SEARCH_FIELD,
            'sort_by.string' => RoleError::INVALID_SORT_BY_TYPE,
            'sort_by.in' => RoleError::INVALID_SORT_BY,
            'sort_direction.string' => RoleError::INVALID_SORT_DIRECTION_TYPE,
            'sort_direction.in' => RoleError::INVALID_SORT_DIRECTION,
            'include_deleted.string' => RoleError::INVALID_INCLUDE_DELETED_TYPE,
            'include_deleted.in' => RoleError::INVALID_INCLUDE_DELETED,
            'updated_at_from.date_format' => RoleError::UPDATED_AT_FROM_FORMAT,
            'updated_at_to.date_format' => RoleError::UPDATED_AT_TO_FORMAT,
            'updated_at_to.after_or_equal' => RoleError::UPDATED_AT_TO_BEFORE_FROM,
            'updated_at_to.afterorequal' => RoleError::UPDATED_AT_TO_BEFORE_FROM,
        ];
    }
}
