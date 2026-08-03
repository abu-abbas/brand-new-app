<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\ListRoleRequest;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleOptionResource;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class RoleController extends Controller
{
    public function __construct(private readonly RoleService $roleService) {}

    /**
     * Display a paginated list of roles.
     *
     * @summary Mengambil daftar role ter-paginasi.
     */
    public function index(ListRoleRequest $request): AnonymousResourceCollection
    {
        return RoleResource::collection(
            $this->roleService->paginate($request->validated())
        );
    }

    /**
     * Display active roles for selection.
     *
     * @summary Mengambil pilihan role aktif.
     */
    public function options(): AnonymousResourceCollection
    {
        return RoleOptionResource::collection($this->roleService->options());
    }

    /**
     * Display the specified role detail.
     *
     * @summary Mengambil detail role.
     */
    public function show(Role $role): RoleResource
    {
        $this->authorize('view', $role);

        return RoleResource::make($role->load('features'));
    }

    /**
     * Store a new role.
     *
     * @summary Menambah role baru.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        return RoleResource::make(
            $this->roleService->create($request->validated())
        )->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update an existing role.
     *
     * @summary Memperbarui role.
     */
    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        $this->authorize('update', $role);

        return RoleResource::make(
            $this->roleService->update($role, $request->validated())
        );
    }

    /**
     * Soft-delete an existing role.
     *
     * @summary Menghapus role.
     */
    public function destroy(Role $role): Response
    {
        $this->authorize('delete', $role);

        $this->roleService->delete($role);

        return response()->noContent();
    }

    /**
     * Restore a soft-deleted role.
     *
     * @summary Memulihkan role terhapus.
     */
    public function restore(Role $role): RoleResource
    {
        $this->authorize('restore', $role);

        return RoleResource::make($this->roleService->restore($role));
    }
}
