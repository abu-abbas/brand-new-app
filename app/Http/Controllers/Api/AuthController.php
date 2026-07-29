<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Mews\Captcha\Captcha;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Authenticate a user with a username, password, and CAPTCHA.
     *
     * @summary Login
     */
    public function login(LoginRequest $request): UserResource
    {
        $user = $this->authService->authenticate(
            $request->safe()->only(['username', 'password']),
            $request->ip() ?? 'unknown',
        );
        $request->session()->regenerate();

        return new UserResource($user);
    }

    /**
     * Return a single-use CAPTCHA challenge as a base64 data URI.
     *
     * @summary CAPTCHA challenge
     *
     * @return array{img: string, key: string}
     */
    public function captcha(Captcha $captcha): array
    {
        $challenge = $captcha->create('flat', true);

        return [
            'img' => (string) $challenge['img'],
            'key' => $challenge['key'],
        ];
    }

    /**
     * Return the authenticated user.
     *
     * @summary Current user
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * End the authenticated session.
     *
     * @summary Logout
     */
    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
