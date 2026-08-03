<?php

namespace App\Services;

use App\Constants\RoleConstant;
use App\Core\ErrorDefinition\ErrorDefinitionReader;
use App\Core\ErrorDefinition\Exceptions\ApplicationException;
use App\Errors\UserManagementError;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    public function __construct(
        private readonly ErrorDefinitionReader $errorDefinitionReader = new ErrorDefinitionReader,
    ) {}

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
        $query = User::query()
            ->with(['userRoles.roleModel'])
            ->whereNull('dt_deleted_at');

        /** @var User|null $currentUser */
        $currentUser = Auth::user();

        if ($currentUser && ! $currentUser->isRoot()) {
            $currentUserLevel = $currentUser->role_level;

            // Filter 1: Level role harus dibawah currentUser (< currentUserLevel)
            $query->whereDoesntHave('userRoles.roleModel', function ($q) use ($currentUserLevel) {
                $q->where('i_level', '>=', $currentUserLevel);
            });

            // Filter 2: Wilayah (substring 2 digit awal v_kolok)
            $userWilayahCodes = $currentUser->userRoles
                ->pluck('v_wilayah')
                ->filter()
                ->map(fn ($w) => substr((string) $w, 0, 2))
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            if (! empty($userWilayahCodes)) {
                $query->where(function ($q) use ($userWilayahCodes) {
                    foreach ($userWilayahCodes as $index => $wCode) {
                        if ($index === 0) {
                            $q->where(DB::raw('SUBSTRING(v_kolok, 1, 2)'), '=', $wCode);
                        } else {
                            $q->orWhere(DB::raw('SUBSTRING(v_kolok, 1, 2)'), '=', $wCode);
                        }
                    }
                });
            }
        } else {
            // Sembunyikan user tingkat Root dari daftar pengguna biasa
            $query->whereDoesntHave('userRoles.roleModel', function ($q) {
                $q->where('i_level', '>=', RoleConstant::ROOT_LEVEL);
            });
        }

        // Search multi-kolom
        if (! empty($params['search'])) {
            $search = trim($params['search']);
            $query->where(function ($q) use ($search) {
                $q->where('v_userid', 'like', "%{$search}%")
                    ->orWhere('v_username', 'like', "%{$search}%")
                    ->orWhere('v_email', 'like', "%{$search}%");
            });
        }

        // Filter status active
        if (isset($params['active']) && $params['active'] !== '') {
            $activeBool = in_array(strtolower((string) $params['active']), ['true', '1'], true);
            $query->where('b_is_active', $activeBool);
        }

        // Sorting
        $sortBy = $params['sort_by'] ?? 'dt_created_at';
        $sortDir = strtolower($params['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $columnMap = [
            'id' => 'i_id',
            'userid' => 'v_userid',
            'v_userid' => 'v_userid',
            'username' => 'v_username',
            'name' => 'v_username',
            'v_username' => 'v_username',
            'v_email' => 'v_email',
            'email' => 'v_email',
            'is_active' => 'b_is_active',
            'b_is_active' => 'b_is_active',
            'dt_created_at' => 'dt_created_at',
            'created_at' => 'dt_created_at',
        ];

            $orderColumn = $columnMap[$sortBy] ?? 'dt_created_at';

        return $query->orderBy($orderColumn, $sortDir)
            ->paginate((int) ($params['per_page'] ?? 15), ['*'], 'page', (int) ($params['page'] ?? 1));
    }

    public function getUserDetail(User $user): User
    {
        return $user->load(['userRoles.roleModel']);
    }

    private function validateRoleAssignments(array $rolesData, ?User $actor = null): void
    {
        if (! $actor) {
            /** @var User|null $actor */
            $actor = Auth::user();
        }

        if (! $actor || $actor->isRoot()) {
            return;
        }

        $actorLevel = $actor->role_level;
        $roleCodes = [];

        foreach ($rolesData as $roleItem) {
            $code = $roleItem['role_code'] ?? $roleItem['v_role_code'] ?? null;
            if ($code) {
                $roleCodes[] = $code;
            }
        }

        if (empty($roleCodes)) {
            return;
        }

        $assignedRoles = \App\Models\Role::whereIn('v_code', $roleCodes)->get();
        foreach ($assignedRoles as $assignedRole) {
            if ((int) $assignedRole->i_level >= $actorLevel) {
                throw new ApplicationException(
                    definition: $this->errorDefinitionReader->read(UserManagementError::CANNOT_ASSIGN_HIGHER_ROLE),
                    context: [
                        'actor_userid' => $actor->v_userid,
                        'actor_level' => $actorLevel,
                        'target_role_code' => $assignedRole->v_code,
                        'target_role_level' => $assignedRole->i_level,
                    ]
                );
            }
        }
    }

    public function createUser(array $data, ?string $authUserId = null): User
    {
        if (! empty($data['roles']) && is_array($data['roles'])) {
            $this->validateRoleAssignments($data['roles']);
        }

        return DB::transaction(function () use ($data, $authUserId) {
            $now = Carbon::now();

            $rawPassword = $data['password'] ?? $data['v_password'] ?? null;
            $password = ! empty($rawPassword)
                ? Hash::make($rawPassword)
                : Hash::make(Str::random(32));

            $user = User::create([
                'v_userid' => $data['userid'] ?? $data['v_userid'],
                'v_username' => $data['username'] ?? $data['v_username'],
                'v_email' => $data['email'] ?? $data['v_email'] ?? null,
                'v_kolok' => $data['unit_code'] ?? $data['v_kolok'] ?? null,
                'v_password' => $password,
                'b_is_active' => $data['is_active'] ?? $data['b_is_active'] ?? true,
                'b_use_other' => $data['is_external'] ?? $data['b_use_other'] ?? false,
                'v_created_by' => $authUserId,
                'dt_created_at' => $now,
            ]);

            if (! empty($data['roles']) && is_array($data['roles'])) {
                foreach ($data['roles'] as $roleItem) {
                    UserRole::create([
                        'v_userid' => $user->v_userid,
                        'v_role_code' => $roleItem['role_code'] ?? $roleItem['v_role_code'],
                        'v_wilayah' => $roleItem['wilayah'] ?? $roleItem['v_wilayah'] ?? null,
                        'v_unit' => $roleItem['unit'] ?? $roleItem['v_unit'] ?? null,
                        'v_pelaksana' => $roleItem['pelaksana'] ?? $roleItem['v_pelaksana'] ?? null,
                        'dt_valid_from' => $roleItem['valid_from'] ?? $roleItem['dt_valid_from'] ?? null,
                        'dt_valid_until' => $roleItem['valid_until'] ?? $roleItem['dt_valid_until'] ?? null,
                        'v_created_by' => $authUserId,
                        'dt_created_at' => $now,
                    ]);
                }
            }

            return $user->load(['userRoles.roleModel']);
        });
    }

    public function updateUser(User $user, array $data, ?string $authUserId = null): User
    {
        $currentUserId = $authUserId ?? Auth::user()?->v_userid;

        if ($currentUserId === $user->v_userid && array_key_exists('is_active', $data) && ! $data['is_active']) {
            throw new ApplicationException(
                definition: $this->errorDefinitionReader->read(UserManagementError::CANNOT_DEACTIVATE_SELF),
                context: ['userid' => $user->v_userid]
            );
        }

        if (array_key_exists('roles', $data) && is_array($data['roles'])) {
            $this->validateRoleAssignments($data['roles']);
        }

        return DB::transaction(function () use ($user, $data, $authUserId) {
            $now = Carbon::now();

            $username = $data['username'] ?? $data['v_username'] ?? $user->v_username;
            $email = array_key_exists('email', $data) ? $data['email'] : (array_key_exists('v_email', $data) ? $data['v_email'] : $user->v_email);
            $unitCode = array_key_exists('unit_code', $data) ? $data['unit_code'] : (array_key_exists('v_kolok', $data) ? $data['v_kolok'] : $user->v_kolok);
            $isActive = array_key_exists('is_active', $data) ? $data['is_active'] : (array_key_exists('b_is_active', $data) ? $data['b_is_active'] : $user->b_is_active);
            $isExternal = (bool) (array_key_exists('is_external', $data) ? $data['is_external'] : (array_key_exists('b_use_other', $data) ? $data['b_use_other'] : ($user->b_use_other ?? false)));

            $updateData = [
                'v_username' => $username,
                'v_email' => $email,
                'v_kolok' => $unitCode,
                'b_is_active' => $isActive,
                'b_use_other' => $isExternal,
                'v_updated_by' => $authUserId,
                'dt_updated_at' => $now,
            ];

            $rawPassword = $data['password'] ?? $data['v_password'] ?? null;
            if (! empty($rawPassword)) {
                $updateData['v_password'] = Hash::make($rawPassword);
            }

            $user->update($updateData);

            if (array_key_exists('roles', $data) && is_array($data['roles'])) {
                // Replace roles
                UserRole::where('v_userid', $user->v_userid)->delete();

                foreach ($data['roles'] as $roleItem) {
                    UserRole::create([
                        'v_userid' => $user->v_userid,
                        'v_role_code' => $roleItem['role_code'] ?? $roleItem['v_role_code'],
                        'v_wilayah' => $roleItem['wilayah'] ?? $roleItem['v_wilayah'] ?? null,
                        'v_unit' => $roleItem['unit'] ?? $roleItem['v_unit'] ?? null,
                        'v_pelaksana' => $roleItem['pelaksana'] ?? $roleItem['v_pelaksana'] ?? null,
                        'dt_valid_from' => $roleItem['valid_from'] ?? $roleItem['dt_valid_from'] ?? null,
                        'dt_valid_until' => $roleItem['valid_until'] ?? $roleItem['dt_valid_until'] ?? null,
                        'v_created_by' => $authUserId,
                        'dt_created_at' => $now,
                    ]);
                }
            }

            return $user->load(['userRoles.roleModel']);
        });
    }

    public function toggleUserStatus(User $user, ?string $authUserId = null): User
    {
        $currentUserId = $authUserId ?? Auth::user()?->v_userid;

        if ($currentUserId === $user->v_userid && $user->b_is_active) {
            throw new ApplicationException(
                definition: $this->errorDefinitionReader->read(UserManagementError::CANNOT_DEACTIVATE_SELF),
                context: ['userid' => $user->v_userid]
            );
        }

        $user->update([
            'b_is_active' => ! $user->b_is_active,
            'v_updated_by' => $authUserId,
            'dt_updated_at' => Carbon::now(),
        ]);

        return $user->load(['userRoles.roleModel']);
    }

    public function deleteUser(User $user, ?string $authUserId = null): void
    {
        $currentUserId = $authUserId ?? Auth::user()?->v_userid;

        if ($currentUserId === $user->v_userid) {
            throw new ApplicationException(
                definition: $this->errorDefinitionReader->read(UserManagementError::CANNOT_DELETE_SELF),
                context: ['userid' => $user->v_userid]
            );
        }

        if ($user->b_use_other || empty($user->v_password)) {
            throw new ApplicationException(
                definition: $this->errorDefinitionReader->read(UserManagementError::CANNOT_DELETE_EXTERNAL_USER),
                context: ['userid' => $user->v_userid]
            );
        }

        $user->update([
            'v_deleted_by' => $authUserId,
            'dt_deleted_at' => Carbon::now(),
        ]);
    }
}
