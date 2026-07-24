<?php

namespace App\Core\ErrorDefinition;

use App\Core\ErrorDefinition\Exceptions\ApplicationException;
use App\Core\ErrorDefinition\Exceptions\ErrorValidationException;
use Illuminate\Http\JsonResponse;

/**
 * Membentuk response JSON untuk exception yang membawa Error Definition.
 *
 * Renderer sentral ini didaftarkan pada exception handler Laravel.
 * Tidak melakukan logging atau sanitasi context.
 */
final class ErrorResponseRenderer
{
    /**
     * Render ApplicationException ke JSON response.
     *
     * Response hanya berisi message, code, dan retryable.
     * Category, severity, context, exception class, file, stack trace,
     * dan previous exception TIDAK dikirim kepada client.
     */
    public function application(ApplicationException $exception): JsonResponse
    {
        return response()->json(
            $exception->definition->toPublicArray(),
            $exception->definition->httpStatus
        );
    }

    /**
     * Render ErrorValidationException ke JSON response.
     *
     * Response dikelompokkan berdasarkan attribute aktual.
     * Setiap validation error membawa code, message, dan retryable.
     */
    public function validation(ErrorValidationException $exception): JsonResponse
    {
        return response()->json([
            'message' => $exception->getMessage(),
            'errors' => $exception->structuredErrors(),
        ], 422);
    }
}
