<?php

namespace App\Http\Middleware;

use App\Core\ErrorDefinition\ErrorDefinitionReader;
use App\Core\ErrorDefinition\Exceptions\ApplicationException;
use App\Errors\AuthError;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsFresh
{
    public function __construct(
        private readonly ErrorDefinitionReader $errorDefinitionReader = new ErrorDefinitionReader,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (session()->has('impersonator_id')) {
            return $next($request);
        }

        if ($user instanceof User && $user->mustChangePassword()) {
            $allowedRoutes = [
                'api.auth.me',
                'api.auth.password',
                'api.auth.logout',
                'api.impersonate.leave',
            ];

            $routeName = $request->route()?->getName();
            $isAllowed = is_string($routeName) && in_array($routeName, $allowedRoutes, true);

            if (! $isAllowed) {
                throw new ApplicationException(
                    definition: $this->errorDefinitionReader->read(AuthError::PASSWORD_EXPIRED),
                    context: ['v_userid' => $user->v_userid]
                );
            }
        }

        return $next($request);
    }
}
