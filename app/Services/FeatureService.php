<?php

namespace App\Services;

use App\Core\ErrorDefinition\ErrorDefinitionReader;
use App\Core\ErrorDefinition\Exceptions\ApplicationException;
use App\Errors\FeatureError;
use App\Models\Feature;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class FeatureService
{
    public function __construct(private readonly ErrorDefinitionReader $errorDefinitionReader) {}

    private const COLUMNS = [
        'name' => 'v_name',
        'alias' => 'v_alias',
        'type' => 'e_type',
        'parent' => 'v_parent',
        'description' => 'v_desc',
        'route' => 'v_route',
        'icon' => 'v_icon',
        'order' => 'si_order',
        'show_on_sidebar' => 'b_show_on_sidebar',
        'updated_at' => 'dt_updated_at',
        'deleted_at' => 'dt_deleted_at',
    ];

    /**
     * @param  array<string, mixed>  $params
     */
    public function paginate(array $params): LengthAwarePaginator
    {
        $query = Feature::query();

        if (in_array((string) ($params['include_deleted'] ?? 'false'), ['true', '1'], true)) {
            $query->withTrashed();
        }

        $user = Auth::user();
        if ($user && ! $user->isRoot()) {
            $query->where(function ($q) {
                $q->where('b_is_restricted', false)
                    ->orWhereNull('b_is_restricted');
            });
        }

        if ($type = $params['type'] ?? null) {
            $query->where('e_type', $type);
        }

        if ($updatedAtFrom = $params['updated_at_from'] ?? null) {
            $query->whereDate('dt_updated_at', '>=', $updatedAtFrom);
        }

        if ($updatedAtTo = $params['updated_at_to'] ?? null) {
            $query->whereDate('dt_updated_at', '<=', $updatedAtTo);
        }

        if ($search = trim((string) ($params['search'] ?? ''))) {
            $columns = array_values(array_intersect_key(
                self::COLUMNS,
                array_flip($params['search_fields'] ?? [])
            )) ?: ['v_name', 'v_alias', 'v_desc', 'v_route'];

            $query->where(function ($query) use ($columns, $search): void {
                foreach ($columns as $index => $column) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $query->{$method}($column, 'like', "%{$search}%");
                }
            });
        }

        $sortBy = self::COLUMNS[$params['sort_by'] ?? 'order'] ?? 'si_order';
        $sortDirection = ($params['sort_direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $query
            ->orderBy($sortBy, $sortDirection)
            ->orderBy('i_id')
            ->paginate((int) ($params['per_page'] ?? 10));
    }

    /**
     * @return Collection<int, Feature>
     */
    public function options(): Collection
    {
        $query = Feature::query();
        $user = Auth::user();

        if (! $user?->isRoot()) {
            $query->where(fn ($query) => $query
                ->where('b_is_restricted', false)
                ->orWhereNull('b_is_restricted'));
        }

        return $query
            ->orderBy('si_order')
            ->orderBy('v_name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Feature
    {
        $isMenu = $data['type'] === 'menu';

        $attributes = [
            'v_name' => $data['name'],
            'v_alias' => $data['alias'],
            'e_type' => $data['type'],
            'v_parent' => $data['parent'],
            'v_desc' => $data['description'],
            'v_route' => $isMenu ? $data['route'] : null,
            'v_icon' => $isMenu ? $data['icon'] : null,
            'v_created_by' => Auth::user()?->username,
        ];

        // @allow-fallback default order=1 untuk field nullable yang tidak dikirim
        $attributes['si_order'] = $data['order'] ?? 1;

        if (array_key_exists('show_on_sidebar', $data)) {
            $attributes['b_show_on_sidebar'] = $isMenu ? (bool) $data['show_on_sidebar'] : false;
        } else {
            $attributes['b_show_on_sidebar'] = $isMenu;
        }

        if (array_key_exists('is_restricted', $data)) {
            $attributes['b_is_restricted'] = (bool) $data['is_restricted'];
        }

        return Feature::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Feature $feature, array $data): Feature
    {
        $isMenu = $data['type'] === 'menu';

        $updateData = [
            'v_name' => $data['name'],
            'e_type' => $data['type'],
            'v_parent' => $data['parent'],
            'v_desc' => $data['description'],
            'v_route' => $isMenu ? $data['route'] : null,
            'v_icon' => $isMenu ? $data['icon'] : null,
            'v_updated_by' => Auth::user()?->username,
        ];

        // @allow-fallback default order=1 untuk field nullable yang tidak dikirim
        $updateData['si_order'] = $data['order'] ?? 1;

        if (array_key_exists('show_on_sidebar', $data)) {
            $updateData['b_show_on_sidebar'] = $isMenu ? (bool) $data['show_on_sidebar'] : false;
        }

        if (array_key_exists('is_restricted', $data)) {
            $updateData['b_is_restricted'] = (bool) $data['is_restricted'];
        }

        $feature->update($updateData);

        return $feature->refresh();
    }

    public function delete(Feature $feature): void
    {
        $feature->update([
            'v_deleted_by' => Auth::user()?->username,
        ]);
        $feature->delete();
    }

    public function restore(Feature $feature): Feature
    {
        if (Feature::query()->where('v_alias', $feature->v_alias)->exists()) {
            throw new ApplicationException(
                definition: $this->errorDefinitionReader->read(FeatureError::RESTORE_ALIAS_CONFLICT),
                context: ['feature_id' => $feature->i_id],
            );
        }

        $feature->restore();
        $feature->update([
            'v_deleted_by' => null,
            'v_updated_by' => Auth::user()?->username,
        ]);

        return $feature->refresh();
    }
}
