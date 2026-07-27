<?php

namespace App\Http\Requests\User;

use App\Core\ErrorDefinition\Traits\HasErrorDefinitions;
use App\Errors\UserManagementError;
use Illuminate\Foundation\Http\FormRequest;

class ListUserRequest extends FormRequest
{
  use HasErrorDefinitions;

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
      'search_fields.*' => ['string'],
      'sort_by' => ['nullable', 'string', 'in:id,name,username,email,unit_name,role,is_active,created_at'],
      'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
      'active' => ['nullable', 'string', 'in:true,false,1,0'],
    ];
  }

  public function errorCodes(): array
  {
    return [
      // page
      'page.integer' => UserManagementError::INVALID_PAGE_TYPE,
      'page.min' => UserManagementError::INVALID_PAGE_MIN,

      // per_page
      'per_page.integer' => UserManagementError::INVALID_PER_PAGE_TYPE,
      'per_page.min' => UserManagementError::INVALID_PER_PAGE_MIN,
      'per_page.max' => UserManagementError::INVALID_PER_PAGE_MAX,

      // search
      'search.string' => UserManagementError::INVALID_SEARCH_TYPE,
      'search.max' => UserManagementError::INVALID_SEARCH_MAX,

      // sort_by
      'sort_by.string' => UserManagementError::INVALID_SORT_BY_TYPE,
      'sort_by.in' => UserManagementError::INVALID_SORT_BY,

      // sort_direction
      'sort_direction.string' => UserManagementError::INVALID_SORT_DIRECTION_TYPE,
      'sort_direction.in' => UserManagementError::INVALID_SORT_DIRECTION,

      // active
      'active.string' => UserManagementError::INVALID_ACTIVE_TYPE,
      'active.in' => UserManagementError::INVALID_ACTIVE_VALUE,
    ];
  }
}
