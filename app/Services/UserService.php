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
   *     sort_by?: string|null,
   *     sort_direction?: string|null,
   *     active?: string|null
   * } $params
   */
  public function getPaginatedUsers(array $params): LengthAwarePaginator
  {
    $query = User::query();

    // Search multi-kolom
    if (!empty($params['search'])) {
      $search = trim($params['search']);
      $query->where(function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
          ->orWhere('username', 'like', "%{$search}%")
          ->orWhere('email', 'like', "%{$search}%")
          ->orWhere('unit_name', 'like', "%{$search}%")
          ->orWhere('role', 'like', "%{$search}%");
      });
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
