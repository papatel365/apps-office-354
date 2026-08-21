<?php

use App\Core\Middleware\ResolveTenant;
use App\Http\Middleware\BackupAccess;
use App\Http\Middleware\CheckModuleAccess;
use App\Http\Middleware\CheckSidebarPermission;
use App\Http\Middleware\CompanyContext;
use App\Http\Middleware\DeveloperAccess;
use App\Http\Middleware\PreventIntendedRedirectLoop;
use App\Http\Middleware\RequireCompanyContext;
use App\Http\Middleware\SidebarPermissionMiddleware;
use App\Http\Middleware\TenantDirectorAccess;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

/**
 * Check if a path is an API or AJAX endpoint that should not be stored as intended URL.
 */
function isApiOrAjaxEndpoint(string $path): bool
{
    // Patterns that indicate API/AJAX endpoints
    $patterns = [
        'notifications/unread-count',
        'notifications/dropdown',
        'api/',
        'ajax/',
    ];

    foreach ($patterns as $pattern) {
        if (str_contains($path, $pattern)) {
            return true;
        }
    }

    return false;
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => ResolveTenant::class,
            'module.access' => CheckModuleAccess::class,
            'developer' => DeveloperAccess::class,
            'sidebar' => SidebarPermissionMiddleware::class,
            'sidebar.permission' => CheckSidebarPermission::class,
            'tenant.auth' => TenantMiddleware::class,
            'tenant.director' => TenantDirectorAccess::class,
            'company.context' => RequireCompanyContext::class,
            'backup.access' => BackupAccess::class,
            'guest' => \Illuminate\Auth\Middleware\RedirectIfAuthenticated::class,
        ]);

        // Add PreventIntendedRedirectLoop to web middleware stack
        // This ensures API/AJAX routes are never stored as intended redirect URLs
        $middleware->api(prepend: [
            PreventIntendedRedirectLoop::class,
        ]);

        $middleware->web(prepend: [
            PreventIntendedRedirectLoop::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Custom handling for AuthenticationException
        // This prevents API/AJAX endpoints from being stored as intended redirect URLs
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            // If request expects JSON, return 401 JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error' => 'Unauthenticated'
                ], 401);
            }

            // For non-JSON requests, redirect to login with proper intended URL handling
            $redirectTo = $e->redirectTo($request) ?? route('login');

            // IMPORTANT: Only store valid HTML page URLs as intended, not API/AJAX endpoints
            // This is the key fix for the notification polling redirect bug
            $currentPath = $request->path();
            $isApiOrAjax = isApiOrAjaxEndpoint($currentPath);

            if (!$isApiOrAjax) {
                // Only store as intended if it's a valid HTML page
                return Redirect::guest($redirectTo);
            }

            // For API/AJAX endpoints, redirect to login without storing intended URL
            // This prevents polling requests from corrupting the intended redirect
            return Redirect::to(route('login'));
        });
    })->create();
