<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProfileService
{
    /**
     * Mengambil log aktivitas terpaginasi milik pengguna.
     */
    public function getActivityLogsForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = min(max($perPage, 1), 50);

        return ActivityLog::query()
            ->with(['causer', 'impersonator'])
            ->where(function ($query) use ($user) {
                $query->where('v_causer_id', $user->v_userid)
                    ->orWhere('v_impersonator_id', $user->v_userid);
            })
            ->orderByDesc('i_id')
            ->paginate($perPage);
    }
}
