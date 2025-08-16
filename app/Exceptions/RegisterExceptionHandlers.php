<?php
namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Exceptions\ApiResponseException;
use App\Traits\Responses\HandlesApiErrors;

/**
 * Exception Handlers Configuration
 *
 * This file registers custom exception handlers for API responses.
 * It ensures that all exceptions are returned in a consistent JSON format.
 *
 * Example:
 * - Accessing a non-existent route will return a JSON 404 response:
 * {
 *   "status": false,
 *   "message": "Route not found."
 * }
 */
return function (Exceptions $exceptions) {
    $helper = new class {
        use HandlesApiErrors; // Mixin for reusable API error responses
    };

    // 1. Handle validation errors (e.g., invalid email format, missing fields)
    $exceptions->renderable(function (ValidationException $e, $request) use ($helper) {
        if ($request->expectsJson()) {
            
            return $helper->errorResponse('Validation failed', 422, [
                'errors' => $e->errors(), // Returns array of field-specific errors
            ]);
        }
    });

    // 2. Handle unauthenticated user access
    $exceptions->renderable(function (AuthenticationException $e, $request) use ($helper) {
        if ($request->expectsJson()) {
            return $helper->errorResponse('Unauthenticated', 401);
            // return response()->json([
            //     'status'  => false,
            //     'message' => 'Unauthenticated.',
            // ], 401);
        }
    });

    // 3. Handle unauthorized actions (logged in but no permission)
    $exceptions->renderable(function (AuthorizationException $e, $request) use ($helper) {
        if ($request->expectsJson()) {
            return $helper->errorResponse('Unauthorized action.', 403);
            // return response()->json([
            //     'status'  => false,
            //     'message' => 'Unauthorized action.',
            // ], 403);
        }
    });

    // 4. Handle missing model records (e.g., User::find(999))
    $exceptions->renderable(function (ModelNotFoundException $e, $request) use ($helper) {
        if ($request->expectsJson()) {
            return $helper->errorResponse('Resource not found.', 404);

            // return response()->json([
            //     'status'  => false,
            //     'message' => 'Resource not found.',
            // ], 404);
        }
    });

    // 5. Handle invalid database queries
    $exceptions->renderable(function (QueryException $e, $request) use ($helper) {
        if ($request->expectsJson()) {
            return $helper->errorResponse('Database query error.', 500, [
                'errors' => config('app.debug') ? $e->getMessage() : null, // Show details in debug mode only
            ]);
            
            // return response()->json([
            //     'status'  => false,
            //     'message' => 'Database query error.',
            //     'error'   => config('app.debug') ? $e->getMessage() : null, // Show details in debug mode only
            // ], 500);
        }
    });

    // 6. Handle database connection issues
    $exceptions->renderable(function (PDOException $e, $request) use ($helper) {
        if ($request->expectsJson()) {
            return $helper->errorResponse('Database connection error.', 500, [
                'errors' => config('app.debug') ? $e->getMessage() : null, // Show details in debug mode only
            ]);

            // return response()->json([
            //     'status'  => false,
            //     'message' => 'Database connection error.',
            //     'error'   => config('app.debug') ? $e->getMessage() : null,
            // ], 500);
        }
    });

    // 7. Handle missing routes (404)
    $exceptions->renderable(function (NotFoundHttpException $e, $request) use ($helper) {
        if ($request->expectsJson()) {
            return $helper->errorResponse('Route not found.', 404);

            // return response()->json([
            //     'status'  => false,
            //     'message' => 'Route not found.',
            // ], 404);
        }
    });

    // 8. Handle invalid HTTP methods (e.g., GET instead of POST)
    $exceptions->renderable(function (MethodNotAllowedHttpException $e, $request) use ($helper) {
        if ($request->expectsJson()) {
            return $helper->errorResponse('HTTP method not allowed.', 405);

            // return response()->json([
            //     'status'  => false,
            //     'message' => 'HTTP method not allowed.',
            // ], 405);
        }
    });

    // 9. Handle access denial (user lacks role)
    $exceptions->renderable(function (AccessDeniedHttpException $e, $request) use ($helper) {
        if ($request->expectsJson()) {
            return $helper->errorResponse('Access denied.', 403);

            // return response()->json([
            //     'status'  => false,
            //     'message' => 'Access denied.',
            // ], 403);
        }
    });

    // 10. Generic HTTP exception handler
    $exceptions->renderable(function (HttpException $e, $request) use ($helper) {
        if ($request->expectsJson()) {
            return $helper->errorResponse($e->getMessage() ?: 'HTTP error occurred.', $e->getStatusCode());

            // return response()->json([
            //     'status'  => false,
            //     'message' => $e->getMessage() ?: 'HTTP error occurred.',
            // ], $e->getStatusCode());
        }
    });

    // 11. Optional: Custom project exception using ApiResponseException
    // Uncomment to use project-specific error formatting
    // $exceptions->renderable(function (ApiResponseException $e, $request) use ($helper) {
    //     if ($request->expectsJson()) {
            //return $helper->errorResponse($e->getMessage(), $e->status ?? 500);

    //         return response()->json([
    //             'status'  => false,
    //             'message' => $e->getMessage(),
    //             'data'    => $e->data ?? [],
    //         ], $e->status ?? 500);
    //     }
    // });

    // 12. Optional: Catch-all for any unhandled exceptions
    // $exceptions->renderable(function (Throwable $e, $request) use ($helper) {
    //     if ($request->expectsJson()) {
            //return $helper->errorResponse('Server error.', 500, [
            // 'errors' => config('app.debug') ? $e->getMessage() : null
            // ]);

    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'Server error.',
    //             'error'   => config('app.debug') ? $e->getMessage() : null,
    //         ], 500);
    //     }
    // });

};
