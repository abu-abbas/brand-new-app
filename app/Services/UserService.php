<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService
{
  /**
   * @param array{
   *     page?: int|string,
   *     per_page?: int|string,
   *     search?: string|null,
   *     search_fields?: array<string>|null,
   *     sort_by?: string|null,
   *     sort_direction?: string|null,
   *     active?: string|null
   * } $params
   */
  public function getPaginatedUsers(array $params): LengthAwarePaginator
  {
    $query = User::query();

    // Search multi-kolom berdasarkan search_fields yang dipilih
    if (!empty($params['search'])) {
      $search = trim($params['search']);
      $searchFields = $params['search_fields'] ?? [];

      $fieldMap = [
        'name' => 'name',
        'username' => 'username',
        'email' => 'email',
        'unit.name' => 'unit_name',
        'unit_name' => 'unit_name',
        'roles' => 'role',
        'role' => 'role',
      ];

      if (!empty($searchFields) && is_array($searchFields)) {
        $columnsToSearch = [];
        foreach ($searchFields as $fieldKey) {
          if (isset($fieldMap[$fieldKey])) {
            $columnsToSearch[] = $fieldMap[$fieldKey];
          }
        }
        $columnsToSearch = array_unique($columnsToSearch);
      } else {
        $columnsToSearch = ['name', 'username', 'email', 'unit_name', 'role'];
      }

      if (!empty($columnsToSearch)) {
        $query->where(function ($q) use ($search, $columnsToSearch) {
          $first = true;
          foreach ($columnsToSearch as $column) {
            if ($first) {
              $q->where($column, 'like', "%{$search}%");
              $first = false;
            } else {
              $q->orWhere($column, 'like', "%{$search}%");
            }
          }
        });
      }
    }

    // Filter status active
    if (isset($params['active']) && $params['active'] !== '') {
      $activeBool = in_array(strtolower((string)$params['active']), ['true', '1'], true);
      $query->where('is_active', $activeBool);
    }

    // Sorting
    $sortBy = $params['sort_by'] ?? 'id';
    $sortDir = strtolower($params['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

    $allowedSorts = ['id', 'name', 'username', 'email', 'unit_name', 'role', 'is_active', 'created_at'];
    if (in_array($sortBy, $allowedSorts, true)) {
      $query->orderBy($sortBy, $sortDir);
    } else {
      $query->orderBy('id', 'desc');
    }

    $perPage = (int) ($params['per_page'] ?? 10);
    $perPage = min(max($perPage, 1), 100);

    return $query->paginate($perPage);
  }
}
