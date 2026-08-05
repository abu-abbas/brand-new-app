<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImpersonateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ImpersonateService;
use Illuminate\Http\Request;

class ImpersonateController extends Controller
{
    public function __construct(
        private readonly ImpersonateService $impersonateService,
    ) {}

    /**
     * Memulai sesi impersonate Admin ke Target User.
     */
    public function start(ImpersonateRequest $request, User $user): UserResource
    {
        /** @var User $admin */
        $admin = $request->user();
        $targetGroupId = $request->validated('target_group_id');
        if (! is_string($targetGroupId)) {
            $targetGroupId = null;
        }

        $impersonatedTarget = $this->impersonateService->start(
            admin: $admin,
            target: $user,
            targetGroupId: $targetGroupId
        );

        return new UserResource($impersonatedTarget);
    }

    /**
     * Menghentikan sesi impersonate dan memulihkan sesi Admin.
     */
    public function leave(Request $request): UserResource
    {
        $admin = $this->impersonateService->stop('manual_leave');

        return new UserResource($admin);
    }
}
