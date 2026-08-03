<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ListUserRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
    ) {}

    /**
     * Display a paginated list of users.
     *
     * @summary Mengambil daftar pengguna ter-paginasi dari server.
     */
    public function index(ListUserRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $users = $this->userService->getPaginatedUsers($request->validated());

        return UserResource::collection($users);
    }

    /**
     * Display detail of a user.
     *
     * @summary Mengambil detail data pengguna berdasarkan ID.
     */
    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        $userDetail = $this->userService->getUserDetail($user);

        return new UserResource($userDetail);
    }

    /**
     * Create a new user.
     *
     * @summary Membuat pengguna baru lokal beserta penugasan role & scope.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        /** @var User|null $authUser */
        $authUser = Auth::user();
        $authUserId = $authUser?->v_userid;

        $user = $this->userService->createUser($request->validated(), $authUserId);

        return response()->json([
            'message' => 'Pengguna berhasil dibuat.',
            'data' => new UserResource($user),
        ], 201);
    }

    /**
     * Update an existing user.
     *
     * @summary Memperbarui data profil dan penugasan role pengguna.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        /** @var User|null $authUser */
        $authUser = Auth::user();
        $authUserId = $authUser?->v_userid;

        $updatedUser = $this->userService->updateUser($user, $request->validated(), $authUserId);

        return response()->json([
            'message' => 'Data pengguna berhasil diperbarui.',
            'data' => new UserResource($updatedUser),
        ]);
    }

    /**
     * Soft delete a user.
     *
     * @summary Mengarsipkan (soft delete) data pengguna.
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        /** @var User|null $authUser */
        $authUser = Auth::user();
        $authUserId = $authUser?->v_userid;

        $this->userService->deleteUser($user, $authUserId);

        return response()->json([
            'message' => 'Pengguna berhasil dihapus.',
        ]);
    }

    /**
     * Toggle active status of a user.
     *
     * @summary Mengubah status aktif/nonaktif pengguna.
     */
    public function toggleStatus(User $user): JsonResponse
    {
        $this->authorize('update', $user);

        /** @var User|null $authUser */
        $authUser = Auth::user();
        $authUserId = $authUser?->v_userid;

        $updatedUser = $this->userService->toggleUserStatus($user, $authUserId);

        return response()->json([
            'message' => 'Status pengguna berhasil diperbarui.',
            'data' => new UserResource($updatedUser),
        ]);
    }
}
