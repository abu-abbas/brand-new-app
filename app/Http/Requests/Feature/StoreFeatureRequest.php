<?php

namespace App\Http\Requests\Feature;

use App\Core\ErrorDefinition\Traits\HasErrorDefinitions;
use App\Enums\PermissionType;
use App\Errors\FeatureError;
use App\Models\Feature;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreFeatureRequest extends FormRequest
{
    use HasErrorDefinitions;

    protected function prepareForValidation(): void
    {
        $feature = $this->route('feature');
        if ($feature instanceof Feature) {
            $this->merge(['alias' => $feature->v_alias]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $feature = $this->route('feature');
        $featureId = $feature instanceof Feature ? $feature->getKey() : 'NULL';

        return [
            'name' => ['required', 'string', 'max:100'],
            'alias' => [
                'required',
                'string',
                'max:150',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                "unique:tm_features,v_alias,{$featureId},i_id,dt_deleted_at,NULL",
            ],
            'type' => ['required', 'string', 'in:menu,crud,filter'],
            'parent' => [
                'nullable',
                'string',
                'max:150',
                'exists:tm_features,v_alias,dt_deleted_at,NULL',
                'different:alias',
            ],
            'route' => ['nullable', 'string', 'max:250'],
            'icon' => ['nullable', 'string', 'max:50'],
            'order' => ['nullable', 'integer', 'min:1', 'max:32767'],
            'show_on_sidebar' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function errorCodes(): array
    {
        return [
            'name.required' => FeatureError::NAME_REQUIRED,
            'name.string' => FeatureError::NAME_STRING,
            'name.max' => FeatureError::NAME_MAX,
            'alias.required' => FeatureError::ALIAS_REQUIRED,
            'alias.string' => FeatureError::ALIAS_STRING,
            'alias.max' => FeatureError::ALIAS_MAX,
            'alias.regex' => FeatureError::ALIAS_FORMAT,
            'alias.unique' => FeatureError::ALIAS_UNIQUE,
            'type.required' => FeatureError::TYPE_REQUIRED,
            'type.string' => FeatureError::TYPE_STRING,
            'type.in' => FeatureError::TYPE_INVALID,
            'parent.string' => FeatureError::PARENT_STRING,
            'parent.max' => FeatureError::PARENT_MAX,
            'parent.exists' => FeatureError::PARENT_NOT_FOUND,
            'parent.different' => FeatureError::PARENT_SELF,
            'route.string' => FeatureError::ROUTE_STRING,
            'route.max' => FeatureError::ROUTE_MAX,
            'icon.string' => FeatureError::ICON_STRING,
            'icon.max' => FeatureError::ICON_MAX,
            'order.integer' => FeatureError::ORDER_INTEGER,
            'order.min' => FeatureError::ORDER_MIN,
            'order.max' => FeatureError::ORDER_MAX,
            'show_on_sidebar.boolean' => FeatureError::SHOW_ON_SIDEBAR_BOOLEAN,
            'description.string' => FeatureError::DESCRIPTION_STRING,
            'description.max' => FeatureError::DESCRIPTION_MAX,
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (
                ! $this->filled('parent')
                || $validator->errors()->has('parent')
            ) {
                return;
            }

            $parent = Feature::query()
                ->where('v_alias', $this->string('parent'))
                ->first();

            $feature = $this->route('feature');
            if ($feature instanceof Feature && $parent?->is($feature)) {
                $this->addValidationError(
                    $validator,
                    'parent',
                    'different',
                    FeatureError::PARENT_SELF,
                );

                return;
            }

            if (
                $this->input('type') === PermissionType::MENU->value
                && $parent?->e_type !== PermissionType::MENU
            ) {
                $this->addValidationError(
                    $validator,
                    'parent',
                    'menu_parent',
                    FeatureError::MENU_PARENT_INVALID,
                );
            }
        });
    }
}
