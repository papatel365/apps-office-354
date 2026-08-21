<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Return success response.
     */
    protected function success(mixed $data = null, string $message = null, int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message ?? 'Operation successful',
            'data' => $data,
        ], $code);
    }

    /**
     * Return error response.
     */
    protected function error(string $message = 'Operation failed', int $code = 400, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    /**
     * Return paginated response.
     */
    protected function paginated($paginator, string $message = 'Data retrieved successfully'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Redirect back with success message.
     */
    protected function redirectWithSuccess(string $message, string $route = null, $params = []): RedirectResponse
    {
        $redirect = redirect()->back();

        if ($route) {
            $redirect = redirect()->route($route, $params);
        }

        return $redirect->with('success', $message);
    }

    /**
     * Redirect back with error message.
     */
    protected function redirectWithError(string $message): RedirectResponse
    {
        return redirect()->back()->with('error', $message)->withInput();
    }

    /**
     * Get authenticated user or abort.
     */
    protected function user()
    {
        return auth()->user();
    }

    /**
     * Get current tenant ID.
     */
    protected function tenantId(): ?int
    {
        return $this->user()?->tenant_id;
    }

    /**
     * Get current company ID.
     */
    protected function companyId(): ?int
    {
        return $this->user()?->company_id;
    }
}
