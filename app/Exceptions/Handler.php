<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            Log::error('Unhandled application exception', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'user_id' => request()->user()?->getAuthIdentifier(),
            ]);
        });
    }

    public function render($request, Throwable $e)
    {
        if ($e instanceof NoCiclosConfiguradosException) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 404);
            }

            return response()->view('academia.empty-ciclos', [
                'message' => $e->getMessage(),
            ], 404);
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'El recurso solicitado no existe.'], 404);
            }

            return response()->view('errors.500', [
                'message' => 'El recurso solicitado no existe o ya no está disponible.',
            ], 404);
        }

        if ($e instanceof QueryException) {
            $errorId = (string) Str::uuid();
            Log::error('Database operation failed', [
                'error_id' => $errorId,
                'sql_state' => $e->errorInfo[0] ?? null,
                'driver_code' => $e->errorInfo[1] ?? null,
                'url' => $request->fullUrl(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No se pudo completar la operación de datos.',
                    'error_id' => $errorId,
                ], 500);
            }

            return response()->view('errors.500', [
                'message' => 'No se pudo completar la operación de datos. Intenta nuevamente o reporta la referencia.',
                'errorId' => $errorId,
            ], 500);
        }

        if ($e instanceof AuthenticationException) {
            return parent::render($request, $e);
        }

        if (! config('app.debug')) {
            $errorId = (string) Str::uuid();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Ocurrió un error inesperado.',
                    'error_id' => $errorId,
                ], 500);
            }

            return response()->view('errors.500', [
                'errorId' => $errorId,
            ], 500);
        }

        return parent::render($request, $e);
    }
}
