<?php

declare(strict_types=1);

namespace Core\Exception;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        ApiException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
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
        $this->renderable(function (ApiException $e, Request $request) {
            if ($request->wantsJson()) {
                $statusCode = match (true) {
                    $e instanceof AuthorizationException => 403,
                    $e instanceof ValidationException => 422,
                    default => 400,
                };

                return new JsonResponse(
                    ['message' => $e->getMessage()],
                    $statusCode
                );
            }
        });

        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
