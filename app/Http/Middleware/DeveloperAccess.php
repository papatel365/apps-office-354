<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DeveloperAccess
{
    /**
     * Handle an incoming request.
     * Restricts access to Developer accounts ONLY.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeName = $request->route()?->getName();
        $uri = $request->getRequestUri();

        Log::info('[DeveloperAccess] REQUEST TRACE', [
            'uri' => $uri,
            'route_name' => $routeName,
            'route_uri' => $request->route()?->uri(),
            'middleware_list' => $request->route()?->gatherMiddleware(),
            'user_logged_in' => $user ? 'YES' : 'NO',
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'is_developer' => $user?->is_developer,
            'company_role' => $user?->company_role,
        ]);

        if (!$user) {
            Log::info('[DeveloperAccess] No user found - redirecting to login');
            return redirect()->route('login');
        }

        Log::info('[DeveloperAccess] User found, checking if developer...', [
            'is_developer' => $user->is_developer,
            'company_role' => $user->company_role,
        ]);

        if (!$user->is_developer) {
            Log::info('[DeveloperAccess] USER IS NOT DEVELOPER - ABORTING 403');
            abort(403, 'Access Denied. Developer Center is restricted to Developer accounts only.');
        }

        Log::info('[DeveloperAccess] User IS developer - passing through to controller');
        return $next($request);
    }
}
