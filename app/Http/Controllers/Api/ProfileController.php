<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ListProfileActivityLogRequest;
use App\Http\Resources\ActivityLogResource;
use App\Services\ProfileService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService,
    ) {}

    /**
     * Mengambil daftar log aktivitas milik pengguna yang sedang terautentikasi.
     */
    public function activityLogs(ListProfileActivityLogRequest $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $perPage = (int) $request->input('per_page', 15);

        $logs = $this->profileService->getActivityLogsForUser($user, $perPage);

        return ActivityLogResource::collection($logs);
    }
}
