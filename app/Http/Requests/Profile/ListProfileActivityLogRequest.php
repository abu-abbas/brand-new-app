<?php

namespace App\Http\Requests\Profile;

use App\Core\ErrorDefinition\Traits\HasErrorDefinitions;
use App\Errors\ProfileError;
use Illuminate\Foundation\Http\FormRequest;

class ListProfileActivityLogRequest extends FormRequest
{
    use HasErrorDefinitions;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * @return array<string, ProfileError>
     */
    public function errorCodes(): array
    {
        return [
            'page.integer' => ProfileError::INVALID_PAGE_TYPE,
            'page.min' => ProfileError::INVALID_PAGE_MIN,
            'per_page.integer' => ProfileError::INVALID_PER_PAGE_TYPE,
            'per_page.min' => ProfileError::INVALID_PER_PAGE_MIN,
            'per_page.max' => ProfileError::INVALID_PER_PAGE_MAX,
        ];
    }
}
