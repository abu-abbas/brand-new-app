<?php

namespace App\Services;

use App\Core\ErrorDefinition\ErrorDefinitionReader;
use App\Core\ErrorDefinition\Exceptions\ApplicationException;
use App\Errors\RoleError;
use App\Models\Feature;
use App\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RoleService
{
    public function __construct(private readonly ErrorDefinitionReader $errorDefinitionReader) {}

    private const COLUMNS = [
        'code' => 'v_code',
        'name' => 'v_name',
        'need_region' => 'b_need_region',
        'need_unit' => 'b_need_unit',
        'locked' => 'b_locked',
        'updated_at' => 'dt_updated_at',
        'deleted_at' => 'dt_deleted_at',
    ];

    /**
     * @param  array<string, mixed>  $params
     */
    public function paginate(array $params): LengthAwarePaginator
    {
        $query = Role::query()->with('features');

        if (in_array((string) ($params['include_deleted'] ?? 'false'), ['true', '1'], true)) {
            $query->withTrashed();
        }

        if ($updatedAtFrom = $params['updated_at_from'] ?? null) {
            $query->whereDate('dt_updated_at', '>=', $updatedAtFrom);
        }

        if ($updatedAtTo = $params['updated_at_to'] ?? null) {
            $query->whereDate('dt_updated_at', '<=', $updatedAtTo);
        }

        if ($search = trim((string) ($params['search'] ?? ''))) {
            $searchFields = $params['search_fields'] ?? [];
            $columns = array_values(array_intersect_key(
                self::COLUMNS,
                array_flip($searchFields)
            ));

            if (empty($searchFields)) {
                $columns = ['v_code', 'v_name'];
            }

            $includeFeatures = empty($searchFields) || in_array('features', $searchFields, true);

            $query->where(function ($query) use ($columns, $search, $includeFeatures): void {
                foreach ($columns as $index => $column) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $query->{$method}($column, 'like', "%{$search}%");
                }

                if ($includeFeatures) {
                    $method = empty($columns) ? 'whereHas' : 'orWhereHas';
                    $query->{$method}('features', function ($fq) use ($search): void {
                        $fq->where('v_name', 'like', "%{$search}%")
                            ->orWhere('v_alias', 'like', "%{$search}%");
                    });
                }
            });
        }

        $sortBy = self::COLUMNS[$params['sort_by'] ?? 'code'] ?? 'v_code';
        $sortDirection = ($params['sort_direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $query
            ->orderBy($sortBy, $sortDirection)
            ->orderBy('i_id')
            ->paginate((int) ($params['per_page'] ?? 10));
    }

    /**
     * @return Collection<int, Role>
     */
    public function options(): Collection
    {
        return Role::query()
            ->orderBy('v_name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            /** @var Role $role */
            $role = Role::query()->create([
                'v_code' => $data['code'],
                'v_name' => $data['name'],
                'b_need_region' => $data['need_region'] ?? false,
                'b_need_unit' => $data['need_unit'] ?? false,
                'v_active_periode' => $data['active_periode'] ?? null,
                'b_locked' => false,
                'v_created_by' => Auth::user()?->username,
            ]);

            if (array_key_exists('features', $data) && is_array($data['features'])) {
                $featureIds = Feature::query()
                    ->whereIn('v_alias', $data['features'])
                    ->pluck('i_id')
                    ->toArray();
                $role->features()->sync($featureIds);
            }

            return $role->load('features');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $updateData = [
                'v_name' => $data['name'],
                'b_need_region' => $data['need_region'] ?? $role->b_need_region,
                'b_need_unit' => $data['need_unit'] ?? $role->b_need_unit,
                'v_active_periode' => array_key_exists('active_periode', $data) ? $data['active_periode'] : $role->v_active_periode,
                'v_updated_by' => Auth::user()?->username,
            ];

            if (! $role->b_locked && isset($data['code'])) {
                $updateData['v_code'] = $data['code'];
            }

            $role->update($updateData);

            if (array_key_exists('features', $data) && is_array($data['features'])) {
                $featureIds = Feature::query()
                    ->whereIn('v_alias', $data['features'])
                    ->pluck('i_id')
                    ->toArray();
                $role->features()->sync($featureIds);
            }

            return $role->fresh(['features']);
        });
    }

    public function delete(Role $role): void
    {
        if ($role->b_locked) {
            throw new ApplicationException(
                definition: $this->errorDefinitionReader->read(RoleError::ROLE_LOCKED_CANNOT_DELETE),
                context: ['role_id' => $role->i_id, 'code' => $role->v_code]
            );
        }

        $role->update([
            'v_deleted_by' => Auth::user()?->username,
        ]);
        $role->delete();
    }

    public function restore(Role $role): Role
    {
        if (Role::query()->where('v_code', $role->v_code)->exists()) {
            throw new ApplicationException(
                definition: $this->errorDefinitionReader->read(RoleError::RESTORE_CODE_CONFLICT),
                context: ['role_id' => $role->i_id, 'code' => $role->v_code]
            );
        }

        $role->restore();
        $role->update([
            'v_deleted_by' => null,
        ]);

        return $role->refresh()->load('features');
    }
}
