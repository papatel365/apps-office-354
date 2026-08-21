<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequireCompanyContext
{
    /**
     * Handle an incoming request.
     *
     * Ensures company context is valid before accessing company-specific modules.
     *
     * Single Company Rules:
     * 1. If companies table has 0 records → redirect to initial setup
     * 2. If companies table has 1 record → allow through (use that company)
     * 3. If companies table has > 1 records → allow through (integrity problem, log warning)
     *
     * Note: This ignores user's company_id to handle stale data gracefully.
     * If user has stale company_id but valid companies exist, the valid company is used.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Must be authenticated
        if (!$user) {
            return redirect()->route('login');
        }

        // Get company count - this is the SINGLE SOURCE OF TRUTH for company context
        // Ignore user's company_id to handle stale data gracefully
        $companyCount = \App\Models\Company::count();

        // CASE 0: No companies exist → redirect to initial setup
        if ($companyCount === 0) {
            Log::debug('[RequireCompanyContext] No companies exist, redirecting to settings for initial setup', [
                'user_id' => $user->id,
                'email' => $user->email,
                'user_company_id' => $user->company_id,
                'intended_url' => $request->fullUrl(),
            ]);

            // Store intended URL in session so user returns after setup
            session()->put('url.intended', $request->fullUrl());

            return redirect()->route('settings.index')
                ->with('info', 'Selamat datang! Silakan lengkapi informasi perusahaan terlebih dahulu untuk mulai menggunakan sistem.');
        }

        // CASE > 1: Multiple companies → data integrity problem
        // Allow through but log warning
        if ($companyCount > 1) {
            Log::warning('[RequireCompanyContext] Multiple companies detected. This is a data integrity problem.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'user_company_id' => $user->company_id,
                'company_count' => $companyCount,
            ]);

            // Allow through - will be handled by other authorization checks
            return $next($request);
        }

        // CASE 1: Exactly 1 company exists → this is the valid single company
        // Allow through regardless of user's stale company_id
        $company = \App\Models\Company::first();
        $activeCompanyId = $company ? $company->id : null;

        Log::debug('[RequireCompanyContext] Valid single company found, allowing through', [
            'user_id' => $user->id,
            'email' => $user->email,
            'user_company_id' => $user->company_id,
            'active_company_id' => $activeCompanyId,
        ]);

        return $next($request);
    }
}
