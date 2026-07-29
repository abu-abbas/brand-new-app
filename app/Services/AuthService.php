<?php

namespace App\Services;

use App\Core\ErrorDefinition\ErrorDefinitionReader;
use App\Core\ErrorDefinition\Exceptions\ApplicationException;
use App\Errors\AuthError;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(
        private readonly ErrorDefinitionReader $reader,
    ) {}

    /**
     * @param  array{username: string, password: string}  $credentials
     */
    public function authenticate(array $credentials, string $ip): User
    {
        $throttleKey = 'login:'.Str::lower($credentials['username']).'|'.$ip;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw new ApplicationException(
                definition: $this->reader->read(AuthError::TOO_MANY_ATTEMPTS),
                context: ['username' => $credentials['username']],
            );
        }

        if (! Auth::guard('web')->attempt([...$credentials, 'is_active' => true])) {
            RateLimiter::hit($throttleKey, 60);

            throw new ApplicationException(
                definition: $this->reader->read(AuthError::INVALID_CREDENTIALS),
                context: ['username' => $credentials['username']],
            );
        }

        RateLimiter::clear($throttleKey);

        /** @var User $user */
        $user = Auth::guard('web')->user();

        return $user;
    }
}
