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

        if ($user instanceof User && $user->mustChangePassword()) {
            $allowedRoutes = [
                'api.auth.me',
                'api.auth.password',
                'api.auth.logout',
            ];

            $routeName = $request->route()?->getName();
            $path = $request->path();

            $isAllowed = ($routeName && in_array($routeName, $allowedRoutes, true))
                || str_contains($path, 'auth/me')
                || str_contains($path, 'auth/password')
                || str_contains($path, 'auth/logout');

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
