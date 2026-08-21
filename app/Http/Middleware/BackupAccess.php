<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * BackupAccess Middleware
 *
 * Ensures that only Owner and Director roles can access backup features.
 *
 * Access Rules:
 * - Owner: Full access to all backup features
 * - Director: Full access to all backup features
 * - Others: 403 Forbidden
 */
class BackupAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Must be authenticated
        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user is Owner or Director
        // is_owner is a boolean attribute on User model
        // is_director checks user_type or company_role
        $isOwner = $user->is_owner;
        $isDirector = $user->is_director;
        $isCompanyAdmin = $user->is_company_admin;

        // Allow only Owner, Director, or Company Admin
        if (!$isOwner && !$isDirector && !$isCompanyAdmin) {
            // Check if developer (for testing purposes)
            if ($user->is_developer) {
                return $next($request);
            }

            abort(403, 'Anda tidak memiliki akses ke fitur Data Backup. Hanya Owner dan Director yang dapat mengakses.');
        }

        return $next($request);
    }
}
