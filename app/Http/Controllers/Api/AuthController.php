<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\SetActiveGroupRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        $user = $this->loadAuthorizationContext($user);
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
     * Return audio (WAV format) pronouncing the CAPTCHA code for accessibility.
     *
     * @summary CAPTCHA audio
     */
    public function captchaAudio(Request $request): Response
    {
        $key = $request->query('key');
        if (! is_string($key) || empty($key)) {
            return response('', 400);
        }

        $cacheKey = 'captcha_'.md5($key);
        $value = Cache::get($cacheKey);

        if (! $value) {
            return response('', 404);
        }

        $digits = is_array($value) ? $value : str_split((string) $value);
        $pcmData = '';
        $sampleHeader = '';

        foreach ($digits as $digit) {
            $filePath = resource_path("captcha-audio/{$digit}.wav");
            if (! file_exists($filePath)) {
                continue;
            }
            $content = file_get_contents($filePath);
            if ($content === false || strlen($content) < 44) {
                continue;
            }
            if (empty($sampleHeader)) {
                $sampleHeader = substr($content, 0, 44);
            }
            $pcmData .= substr($content, 44);
        }

        if (empty($pcmData)) {
            return response('', 404);
        }

        $dataSize = strlen($pcmData);
        $fileSize = $dataSize + 36;
        $header = pack('a4Va4a4VvvVVvv', 'RIFF', $fileSize, 'WAVE', 'fmt ', 16, 1, 1, 22050, 44100, 2, 16).pack('a4V', 'data', $dataSize);

        return response($header.$pcmData, 200, [
            'Content-Type' => 'audio/wav',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Return the authenticated user.
     *
     * @summary Current user
     */
    public function me(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();
        $user = $this->loadAuthorizationContext($user);

        return new UserResource($user);
    }

    /**
     * Set the active group/role for the current session.
     *
     * @summary Set active group
     */
    public function setActiveGroup(SetActiveGroupRequest $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();
        $groupId = (string) $request->input('group_id');
        $remember = filter_var($request->input('remember'), FILTER_VALIDATE_BOOLEAN);

        $updatedUser = $this->authService->setActiveGroup($user, $groupId, $remember);
        $updatedUser = $this->loadAuthorizationContext($updatedUser);

        return new UserResource($updatedUser);
    }

    /**
     * Reset the default group preference for the current user.
     *
     * @summary Reset default group preference
     */
    public function resetDefaultGroup(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();
        $updatedUser = $this->authService->resetDefaultGroup($user);
        $updatedUser = $this->loadAuthorizationContext($updatedUser);

        return new UserResource($updatedUser);
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

    /**
     * Request a password reset link.
     *
     * @summary Lupa password
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->forgotPassword(
            (string) $request->input('email'),
            $request->ip() ?? 'unknown',
        );

        return response()->json([
            'message' => 'Jika email terdaftar sebagai akun internal, tautan akan dikirim.',
        ]);
    }

    /**
     * Reset/setup user password using a token.
     *
     * @summary Reset/setup password
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetPassword(
            (string) $request->input('email'),
            (string) $request->input('token'),
            (string) $request->input('password'),
        );

        return response()->json([
            'message' => 'Password berhasil diperbarui. Silakan login menggunakan password baru Anda.',
        ]);
    }

    /**
     * Change user password for authenticated session.
     *
     * @summary Ubah password
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authService->changePassword(
            $user,
            (string) $request->input('current_password'),
            (string) $request->input('password'),
        );

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Password berhasil diubah. Seluruh sesi Anda telah diakhiri, silakan login kembali.',
        ]);
    }

    private function loadAuthorizationContext(User $user): User
    {
        $user->load(['userRoles.roleModel.features']);
        $user->setRelation('userRoles', $user->getCurrentUserRoles());

        return $user;
    }
}
