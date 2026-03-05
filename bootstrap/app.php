<?php

use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api:[
        __DIR__ . '/../routes/Api/api.php',
        __DIR__ . '/../routes/Api/auth.php',
        ], 
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
    // Helper to build a unified error response
    $apiError = function ($message, $code, $errors = null) {
        return response()->json([
            'success' => false,
            'code'    => $code,
            'message' => $message,
            'data'    => null,
            'errors'  => $errors,
        ], $code);
    };

    $exceptions->render(function (ValidationException $e, $request) use ($apiError) {
        if (! $request->is('api/*')) return null;
        return $apiError($e->getMessage(), $e->status, $e->errors());
    });

    $exceptions->render(function (AuthenticationException $e, $request) use ($apiError) {
        if (! $request->is('api/*')) return null;
        return $apiError('Unauthenticated.', 401);
    });

    $exceptions->render(function (AuthorizationException $e, $request) use ($apiError) {
        if (! $request->is('api/*')) return null;
        return $apiError($e->getMessage() ?: 'Forbidden.', 403);
    });

    $exceptions->render(function (ModelNotFoundException $e, $request) use ($apiError) {
        if (! $request->is('api/*')) return null;
        return $apiError('Resource not found.', 404);
    });

    $exceptions->render(function (NotFoundHttpException $e, $request) use ($apiError) {
        if (! $request->is('api/*')) return null;
        return $apiError('Route not found.', 404);
    });

    $exceptions->render(function (MethodNotAllowedHttpException $e, $request) use ($apiError) {
        if (! $request->is('api/*')) return null;
        return $apiError('Method not allowed.', 405);
    });

    $exceptions->render(function (HttpException $e, $request) use ($apiError) {
        if (! $request->is('api/*')) return null;
        return $apiError($e->getMessage() ?: 'Request error.', $e->getStatusCode());
    });

    $exceptions->render(function (\Throwable $e, $request) use ($apiError) {
        if (! $request->is('api/*')) return null;
        $message = config('app.debug') ? $e->getMessage() : 'Server Error.';
        return $apiError($message, 500);
    });
    })->create();
