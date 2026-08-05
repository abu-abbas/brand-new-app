<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $incoming = $request->header('X-Request-Id');
        $requestId = is_string($incoming)
          && strlen($incoming) === 36
          && Str::isUuid($incoming, 4)
            ? strtolower($incoming)
            : (string) Str::uuid();

        $request->attributes->set('request_id', $requestId);
        Log::withContext(['request_id' => $requestId]);

        $response = $next($request);

        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
