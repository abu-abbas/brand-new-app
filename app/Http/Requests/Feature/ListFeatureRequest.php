<?php

namespace App\Http\Requests\Feature;

use App\Core\ErrorDefinition\Traits\HasErrorDefinitions;
use App\Errors\FeatureError;
use Illuminate\Foundation\Http\FormRequest;

class ListFeatureRequest extends FormRequest
{
    use HasErrorDefinitions;

    private const FIELDS = [
        'name', 'alias', 'type', 'parent', 'description', 'route', 'icon',
        'order', 'show_on_sidebar', 'updated_at', 'deleted_at',
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
            'type' => ['nullable', 'string', 'in:menu,crud,filter'],
            'include_deleted' => ['nullable', 'string', 'in:true,false,1,0'],
            'updated_at_from' => ['nullable', 'date_format:Y-m-d'],
            'updated_at_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:updated_at_from'],
        ];
    }

    public function errorCodes(): array
    {
        return [
            'page.integer' => FeatureError::INVALID_PAGE_TYPE,
            'page.min' => FeatureError::INVALID_PAGE_MIN,
            'per_page.integer' => FeatureError::INVALID_PER_PAGE_TYPE,
            'per_page.min' => FeatureError::INVALID_PER_PAGE_MIN,
            'per_page.max' => FeatureError::INVALID_PER_PAGE_MAX,
            'search.string' => FeatureError::INVALID_SEARCH_TYPE,
            'search.max' => FeatureError::INVALID_SEARCH_MAX,
            'search_fields.array' => FeatureError::INVALID_SEARCH_FIELDS_TYPE,
            'search_fields.*.string' => FeatureError::INVALID_SEARCH_FIELD_TYPE,
            'search_fields.*.in' => FeatureError::INVALID_SEARCH_FIELD,
            'sort_by.string' => FeatureError::INVALID_SORT_BY_TYPE,
            'sort_by.in' => FeatureError::INVALID_SORT_BY,
            'sort_direction.string' => FeatureError::INVALID_SORT_DIRECTION_TYPE,
            'sort_direction.in' => FeatureError::INVALID_SORT_DIRECTION,
            'type.string' => FeatureError::TYPE_STRING,
            'type.in' => FeatureError::TYPE_INVALID,
            'include_deleted.string' => FeatureError::INVALID_INCLUDE_DELETED_TYPE,
            'include_deleted.in' => FeatureError::INVALID_INCLUDE_DELETED,
            'updated_at_from.date_format' => FeatureError::UPDATED_AT_FROM_FORMAT,
            'updated_at_to.date_format' => FeatureError::UPDATED_AT_TO_FORMAT,
            'updated_at_to.after_or_equal' => FeatureError::UPDATED_AT_TO_BEFORE_FROM,
            'updated_at_to.afterorequal' => FeatureError::UPDATED_AT_TO_BEFORE_FROM,
        ];
    }
}
