<?php

use App\Core\ErrorDefinition\ApplicationExceptionReporter;
use App\Core\ErrorDefinition\ContextSanitizer;
use App\Core\ErrorDefinition\ErrorResponseRenderer;
use App\Core\ErrorDefinition\Exceptions\ApplicationException;
use App\Core\ErrorDefinition\Exceptions\ErrorValidationException;
use App\Http\Middleware\AssignRequestId;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->prepend(AssignRequestId::class);
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : '/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->wantsJson(),
        );

        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        });

        $renderer = new ErrorResponseRenderer;

        // Rendering sentral — ApplicationException
        $exceptions->renderable(function (ApplicationException $e, Request $request) use ($renderer) {
            return $renderer->application($e);
        });

        // Rendering sentral — ErrorValidationException
        $exceptions->renderable(function (ErrorValidationException $e, Request $request) use ($renderer) {
            return $renderer->validation($e);
        });

        // Reporting sentral — ApplicationException (satu kali, structured logging)
        $exceptions->reportable(function (ApplicationException $e) {
            $sanitizer = ContextSanitizer::fromConfig();
            $reporter = new ApplicationExceptionReporter(
                logger: app('log'),
                sanitizer: $sanitizer,
            );
            $reporter->report($e);

            return false; // Hentikan default reporting Laravel agar tidak duplicate
        });

        // ErrorValidationException tidak dilaporkan (volume tinggi, nilai rendah)
        $exceptions->reportable(function (ErrorValidationException $e) {
            return false;
        });
    })->create();
