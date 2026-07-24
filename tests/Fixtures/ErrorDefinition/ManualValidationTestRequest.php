<?php

namespace Tests\Fixtures\ErrorDefinition;

use App\Core\ErrorDefinition\Traits\HasErrorDefinitions;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ManualValidationTestRequest extends FormRequest
{
    use HasErrorDefinitions;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'field_uji' => ['required'],
        ];
    }

    public function errorCodes(): array
    {
        return [
            'field_uji.required' => ManualValidationError::FIELD_UJI_REQUIRED,
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->addValidationError(
                $validator,
                'username',
                'unique',
                ManualValidationError::USERNAME_TAKEN,
            );
        });
    }
}
