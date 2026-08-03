<?php

namespace App\Http\Requests\Auth;

use App\Core\ErrorDefinition\Traits\HasErrorDefinitions;
use App\Errors\AuthError;
use Illuminate\Foundation\Http\FormRequest;

class SetActiveGroupRequest extends FormRequest
{
    use HasErrorDefinitions;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group_id' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function errorCodes(): array
    {
        return [
            'group_id.required' => AuthError::GROUP_REQUIRED,
            'group_id.string' => AuthError::GROUP_STRING,
        ];
    }
}
