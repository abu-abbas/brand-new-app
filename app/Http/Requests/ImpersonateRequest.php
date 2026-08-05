<?php

namespace App\Http\Requests;

use App\Core\ErrorDefinition\Traits\HasErrorDefinitions;
use App\Errors\ImpersonateError;
use Illuminate\Foundation\Http\FormRequest;

class ImpersonateRequest extends FormRequest
{
    use HasErrorDefinitions;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'target_group_id' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, ImpersonateError>
     */
    public function errorCodes(): array
    {
        return [
            'target_group_id.string' => ImpersonateError::TARGET_GROUP_MUST_BE_STRING,
            'target_group_id.max' => ImpersonateError::TARGET_GROUP_MAX_LENGTH,
        ];
    }
}
