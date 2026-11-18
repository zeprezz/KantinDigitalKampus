<?php

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        // Semua error dikembalikan dalam format yang sama dengan Accept header (JSON atau XML),
        // bukan HTML bawaan Lumen.
        if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
            return ApiResponse::error($request, 'Data atau endpoint tidak ditemukan', 404);
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return ApiResponse::error($request, 'Method tidak diizinkan untuk endpoint ini', 405);
        }

        if ($exception instanceof HttpException) {
            return ApiResponse::error(
                $request,
                $exception->getMessage() ?: 'Terjadi kesalahan pada request',
                $exception->getStatusCode()
            );
        }

        $message = env('APP_DEBUG', false)
            ? $exception->getMessage()
            : 'Terjadi kesalahan pada server';

        return ApiResponse::error($request, $message, 500);
    }
}
