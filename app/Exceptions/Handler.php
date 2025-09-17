<?php

namespace App\Exceptions;

use App\Traits\BaseTrait;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;
use Illuminate\Validation\ValidationException;
use Illuminate\Routing\Router;

class Handler extends ExceptionHandler
{
    use BaseTrait;
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    // public function render($request, Throwable $e)
    // {
    //     $e = $this->mapException($e);

    //     if (method_exists($e, 'render') && $response = $e->render($request)) {
    //         return Router::toResponse($request, $response);
    //     }

    //     if ($e instanceof Responsable) {
    //         return $e->toResponse($request);
    //     }
    //     if ($e instanceof AuthenticationException )
    //     {
    //         return $this->sendError('error', $e->getMessage(), 500);
    //     }
    //     if ($e instanceof MethodNotAllowedHttpException)
    //     {
    //         return $this->sendError('error', $e->getMessage(), 405);
       
    //     }
    //     if ($e instanceof NotFoundHttpException ) {
      
    //         return $this->sendError('error', $e->getMessage(), 405);
    //     }
    //     if ( $e instanceof RouteNotFoundException ) {
         
    //         return $this->sendError('error', $e->getMessage(), 405);
    //     }
    //     if ($e instanceof \BadMethodCallException) {
    //         return $this->sendError('error',$e->getMessage(), 404);
    //     }

    //     $e = $this->prepareException($e);

    //     if ($response = $this->renderViaCallbacks($request, $e)) {
    //         return $response;
    //     }

    //     return match (true) {
    //         $e instanceof HttpResponseException => $e->getResponse(),
    //         $e instanceof AuthenticationException => $this->unauthenticated($request, $e),
    //         $e instanceof ValidationException => $this->convertValidationExceptionToResponse($e, $request),
    //         default => $this->renderExceptionResponse($request, $e),
    //     };
    // }

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
