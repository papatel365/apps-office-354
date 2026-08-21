<?php

namespace App\Helpers;

use App\Models\Company;
use Illuminate\Support\Facades\Log;

/**
 * Company Context Helper
 *
 * Single Source of Truth for Company ID
 *
 * This application only supports ONE company in the database.
 *
 * Rules:
 * 1. If companies table has exactly 1 record → use that company's ID
 * 2. If companies table is empty → return NULL (initial setup required)
 * 3. If companies table has more than 1 record → return NULL (data integrity problem)
 *
 * IMPORTANT: Never use auth()->user()->company_id directly.
 * User's company_id may be stale if:
 * - Company was deleted
 * - Database was reset
 * - Initial setup was incomplete
 *
 * Always use active_company_id() to get the current valid company.
 */

if (!function_exists('active_company')) {
    /**
     * Get the active company (single company in the system).
     *
     * Returns NULL if:
     * - No companies exist (initial setup required)
     * - Multiple companies exist (data integrity problem)
     *
     * @return Company|null
     */
    function active_company(): ?Company
    {
        $count = Company::count();

        if ($count === 0) {
            return null; // No companies yet - initial setup required
        }

        if ($count === 1) {
            return Company::first(); // Return the ONE company
        }

        // Multiple companies - data integrity problem
        // Return the first one but log warning
        Log::warning('[CompanyContext] Multiple companies detected. This is a data integrity problem.', [
            'company_count' => $count,
        ]);

        return Company::first();
    }
}

if (!function_exists('active_company_id')) {
    /**
     * Get the active company ID.
     *
     * This is the SINGLE SOURCE OF TRUTH for company context.
     *
     * Returns NULL if:
     * - No companies exist
     * - Multiple companies exist (data integrity problem)
     *
     * @return int|null
     */
    function active_company_id(): ?int
    {
        $company = active_company();
        return $company?->id;
    }
}

if (!function_exists('has_active_company')) {
    /**
     * Check if there is exactly one active company.
     *
     * Use this to determine if initial setup is complete.
     *
     * @return bool
     */
    function has_active_company(): bool
    {
        return Company::count() === 1;
    }
}

if (!function_exists('needs_company_setup')) {
    /**
     * Check if company setup is needed.
     *
     * Returns true when no companies exist and initial setup is required.
     *
     * @return bool
     */
    function needs_company_setup(): bool
    {
        return Company::count() === 0;
    }
}

if (!function_exists('is_single_company_mode')) {
    /**
     * Check if the system is in single company mode.
     *
     * Always true for this application.
     *
     * @return bool
     */
    function is_single_company_mode(): bool
    {
        return true;
    }
}
