<?php

namespace App\Http\Middleware;

use App\Core\ErrorDefinition\ErrorDefinitionReader;
use App\Core\ErrorDefinition\Exceptions\ApplicationException;
use App\Errors\ImpersonateError;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockImpersonatedSensitiveActions
{
    public function __construct(
        private readonly ErrorDefinitionReader $reader,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('impersonator_id')) {
            $routeName = $request->route()->getName();
            $blockedRoutes = [
                'api.auth.password',
                'api.auth.active-group',
                'api.auth.reset-default-group',
                'api.users.destroy',
                'api.users.send-password-link',
                'api.users.reset-password',
            ];

            if (is_string($routeName)) {
                $isBlockedRoute = in_array($routeName, $blockedRoutes, true);
                $isEmailUpdateAttempt = ($routeName === 'api.users.update' && ($request->has('email') || $request->exists('email')));

                if ($isBlockedRoute || $isEmailUpdateAttempt) {
                    throw new ApplicationException(
                        definition: $this->reader->read(ImpersonateError::SENSITIVE_ACTION_BLOCKED),
                        context: [
                            'route' => $routeName,
                            'impersonator_id' => (string) session('impersonator_id'),
                        ]
                    );
                }
            }
        }

        return $next($request);
    }
}
