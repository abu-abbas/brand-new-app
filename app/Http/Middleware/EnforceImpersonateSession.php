<?php

namespace App\Http\Middleware;

use App\Core\ErrorDefinition\ErrorDefinitionReader;
use App\Core\ErrorDefinition\Exceptions\ApplicationException;
use App\Errors\ImpersonateError;
use App\Models\User;
use App\Services\ImpersonateService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnforceImpersonateSession
{
    public function __construct(
        private readonly ImpersonateService $impersonateService,
        private readonly ErrorDefinitionReader $reader,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('impersonator_id')) {
            $impersonatorId = session()->get('impersonator_id');
            $impersonatedGroup = session()->get('impersonated_active_group');
            $startedAtIso = session()->get('impersonate_started_at');

            // Validasi keberadaan dan tipe seluruh required session keys
            $hasValidSessionKeys = is_string($impersonatorId) && $impersonatorId !== ''
                && is_string($impersonatedGroup) && $impersonatedGroup !== ''
                && is_string($startedAtIso) && $startedAtIso !== '';

            $impersonatorIdStr = is_string($impersonatorId) ? $impersonatorId : 'UNKNOWN';

            Log::withContext([
                'impersonator_id' => $impersonatorIdStr,
                'impersonated_active_group' => is_string($impersonatedGroup) ? $impersonatedGroup : 'UNKNOWN',
            ]);

            $routeName = $request->route()->getName();
            $whitelistedRoutes = [
                'api.impersonate.leave',
                'api.auth.logout',
            ];

            $isWhitelisted = is_string($routeName) && in_array($routeName, $whitelistedRoutes, true);

            if (! $isWhitelisted) {
                // 1. Validasi state integrity (jika key required hilang/rusak)
                if (! $hasValidSessionKeys) {
                    $this->impersonateService->stop('session_invalidated');

                    throw new ApplicationException(
                        definition: $this->reader->read(ImpersonateError::SESSION_INVALIDATED),
                        context: ['impersonator_id' => $impersonatorIdStr]
                    );
                }

                // 2. Parse timestamp & Cek Fixed TTL 60 menit
                try {
                    $startedAt = Carbon::parse($startedAtIso);
                } catch (\Throwable) {
                    $this->impersonateService->stop('session_invalidated');

                    throw new ApplicationException(
                        definition: $this->reader->read(ImpersonateError::SESSION_INVALIDATED),
                        context: ['impersonator_id' => $impersonatorIdStr]
                    );
                }

                if (Carbon::now()->greaterThanOrEqualTo($startedAt->copy()->addMinutes(60))) {
                    $this->impersonateService->stop('ttl_expired');

                    throw new ApplicationException(
                        definition: $this->reader->read(ImpersonateError::SESSION_EXPIRED),
                        context: ['impersonator_id' => $impersonatorIdStr]
                    );
                }

                // 3. Cek Real-time Security Invariants (Target active, valid group assignment, frozen group intact)
                $targetUser = $request->user();
                $isValidUser = $targetUser instanceof User && $targetUser->b_is_active;

                $targetRoles = $isValidUser ? $targetUser->getRolesList() : [];
                $hasFrozenGroup = in_array((string) $impersonatedGroup, $targetRoles, true);

                $currentActiveGroup = $isValidUser ? $targetUser->getActiveGroupId() : null;
                $isGroupIntact = ($currentActiveGroup === $impersonatedGroup);

                if (! $isValidUser || ! $hasFrozenGroup || ! $isGroupIntact) {
                    $this->impersonateService->stop('session_invalidated');

                    throw new ApplicationException(
                        definition: $this->reader->read(ImpersonateError::SESSION_INVALIDATED),
                        context: ['impersonator_id' => $impersonatorIdStr]
                    );
                }
            }
        }

        return $next($request);
    }
}
