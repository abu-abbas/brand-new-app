<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\ImpersonationStartedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendImpersonationStartedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly string $adminUserId,
        public readonly string $adminName,
        public readonly string $targetUserId,
        public readonly string $targetGroupId,
        public readonly string $startedAt,
        public readonly string $expiresAt,
        public readonly ?string $requestId = null,
    ) {}

    public function handle(): void
    {
        $target = User::query()->where('v_userid', $this->targetUserId)->first();
        if ($target === null || empty($target->v_email)) {
            return;
        }

        $target->notify(new ImpersonationStartedNotification(
            adminUserId: $this->adminUserId,
            adminName: $this->adminName,
            targetGroupId: $this->targetGroupId,
            startedAt: $this->startedAt,
            expiresAt: $this->expiresAt,
        ));
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('Gagal mengirimkan email notifikasi impersonation started', [
            'request_id' => $this->requestId,
            'target_userid' => $this->targetUserId,
            'admin_userid' => $this->adminUserId,
            'error' => $exception->getMessage(),
        ]);
    }
}
