<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Trait for debuggable JSON responses in Employee Wizard
 * Provides detailed error logging and response formatting
 */
trait DebuggableWizardResponses
{
    /**
     * Log detailed request information for debugging
     */
    protected function logRequest(string $context, Request $request, array $extra = []): void
    {
        $user = auth()->user();

        Log::channel('single')->debug("WIZARD DEBUG: {$context}", [
            'timestamp' => now()->toIso8601String(),
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'company_id' => $user?->company_id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'route' => $request->route()?->getName(),
            'payload' => $request->all(),
            'extra' => $extra,
        ]);
    }

    /**
     * Log exception with full details
     */
    protected function logException(string $context, \Throwable $exception, Request $request): array
    {
        $user = auth()->user();

        $logData = [
            'timestamp' => now()->toIso8601String(),
            'context' => $context,
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'company_id' => $user?->company_id,
            'ip_address' => $request->ip(),
            'request_payload' => $request->all(),
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'exception_file' => $exception->getFile(),
            'exception_line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];

        // Add SQL details if QueryException
        if ($exception instanceof \Illuminate\Database\QueryException) {
            $logData['sql_query'] = $exception->getSql();
            $logData['sql_bindings'] = $exception->getBindings();
            $logData['sql_message'] = $exception->getMessage();
        }

        // Log based on exception severity
        if ($exception instanceof \Illuminate\Database\QueryException) {
            Log::channel('single')->error("WIZARD SQL ERROR: {$context}", $logData);
        } elseif ($exception instanceof \Illuminate\Validation\ValidationException) {
            Log::channel('single')->warning("WIZARD VALIDATION ERROR: {$context}", $logData);
        } else {
            Log::channel('single')->error("WIZARD ERROR: {$context}", $logData);
        }

        return $logData;
    }

    /**
     * Return success JSON response with optional data
     */
    protected function successResponse(string $message, array $data = [], int $status = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'debug' => config('app.debug') ? [
                'timestamp' => now()->toIso8601String(),
                'server_time' => date('Y-m-d H:i:s'),
            ] : null,
        ], $status);
    }

    /**
     * Return error JSON response with detailed debug info when APP_DEBUG=true
     */
    protected function errorResponse(
        string $message,
        int $status = 400,
        array $errors = [],
        ?\Throwable $exception = null,
        ?Request $request = null
    ): \Illuminate\Http\JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        // Add validation errors if provided
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        // Include debug information only when APP_DEBUG is true
        if (config('app.debug')) {
            $debug = [
                'timestamp' => now()->toIso8601String(),
                'server_time' => date('Y-m-d H:i:s'),
                'php_version' => PHP_VERSION,
            ];

            if ($exception) {
                $debug['exception'] = [
                    'class' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ];

                // Add SQL info for QueryException
                if ($exception instanceof \Illuminate\Database\QueryException) {
                    $debug['sql'] = [
                        'query' => $exception->getSql(),
                        'bindings' => $exception->getBindings(),
                        '完整_message' => $exception->getMessage(),
                    ];
                }

                // Add validation errors for ValidationException
                if ($exception instanceof \Illuminate\Validation\ValidationException) {
                    $debug['validation_errors'] = $exception->errors();
                }
            }

            $response['debug'] = $debug;
        }

        return response()->json($response, $status);
    }

    /**
     * Handle AJAX endpoint with comprehensive error handling
     */
    protected function handleAjax(string $context, Request $request, callable $callback): \Illuminate\Http\JsonResponse
    {
        // Log incoming request
        $this->logRequest("START: {$context}", $request);

        try {
            $result = $callback();
            $this->logRequest("SUCCESS: {$context}", $request, ['result' => $result]);
            return $result;
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->logException($context, $e, $request);
            return $this->errorResponse(
                'Validasi gagal',
                422,
                $e->errors(),
                $e,
                $request
            );
        } catch (\Illuminate\Database\QueryException $e) {
            $this->logException($context, $e, $request);
            return $this->errorResponse(
                'Error database: ' . $e->getMessage(),
                500,
                [],
                $e,
                $request
            );
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            $this->logException($context, $e, $request);
            return $this->errorResponse(
                'Session expired. Silakan login ulang.',
                401,
                [],
                $e,
                $request
            );
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            $this->logException($context, $e, $request);
            return $this->errorResponse(
                'Resource tidak ditemukan',
                404,
                [],
                $e,
                $request
            );
        } catch (\Throwable $e) {
            $this->logException($context, $e, $request);
            return $this->errorResponse(
                config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan pada server',
                500,
                [],
                $e,
                $request
            );
        }
    }

    /**
     * Get input data with debug logging
     */
    protected function getValidatedData(Request $request, array $rules): array
    {
        $data = $request->all();
        $validator = \Illuminate\Support\Facades\Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $data;
    }
}
