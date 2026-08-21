<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Modules\System\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Display CRM settings.
     */
    public function index(): View
    {
        $tenant = $this->getTenant();

        if (!$tenant) {
            $tenant = new Tenant([
                'name' => '',
                'email' => '',
                'phone' => '',
                'address' => '',
            ]);
        }

        // Get CRM-specific settings from tenant
        $crmSettings = [
            'company_name' => $tenant->name ?? '',
            'short_name' => $tenant->short_name ?? '',
            'company_email' => $tenant->email ?? '',
            'company_phone' => $tenant->phone ?? '',
            'website' => $tenant->website ?? '',
            'company_address' => $tenant->address ?? '',
            'company_logo' => $tenant->logo_url ?? '',
            'tax_id' => $tenant->getSetting('crm.tax_id', ''),
            'invoice_prefix' => $tenant->getSetting('crm.invoice_prefix', 'INV'),
            'invoice_footer' => $tenant->getSetting('crm.invoice_footer', ''),
            'default_payment_terms' => $tenant->getSetting('crm.default_payment_terms', 30),
            'currency' => $tenant->currency_code ?? 'IDR',
            'footer_text' => $tenant->getSetting('crm.footer_text', '© ' . date('Y') . ' Office 354. All rights reserved.'),
        ];

        // Check if initial setup is required
        // Initial setup is shown when there are NO companies in the system
        // This is independent of user's company_id (which may be stale)
        $user = auth()->user();
        $companyCount = Company::count();
        $isInitialSetup = ($companyCount === 0);

        return view('crm.settings.index', compact('tenant', 'crmSettings', 'isInitialSetup'));
    }

    /**
     * Update CRM settings.
     * Handles both regular updates and initial company setup.
     */
    public function update(Request $request): JsonResponse|RedirectResponse|Response
    {
        $user = $request->user();

        // Check if this is initial company setup
        // Initial setup is triggered when there are NO companies
        // This is independent of user's company_id (which may be stale)
        $companyCount = Company::count();
        $isInitialSetup = ($companyCount === 0);

        if ($isInitialSetup) {
            return $this->handleInitialCompanySetup($request, $user);
        }

        // Regular update (company exists)
        $tenant = $this->getTenantForUser($user);

        // If no tenant found, check if we should create one from the active company
        if (!$tenant) {
            $activeCompany = active_company();
            if ($activeCompany) {
                // Create tenant from active company
                $tenant = $this->createTenantFromCompany($activeCompany);
            }
        }

        if (!$tenant) {
            return $this->error('Tenant not found', 404);
        }

        return $this->processTenantUpdate($request, $tenant);
    }

    /**
     * Create a tenant from an existing company.
     */
    protected function createTenantFromCompany(Company $company): Tenant
    {
        return Tenant::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'name' => $company->name,
            'slug' => $company->slug,
            'email' => $company->email,
            'phone' => $company->phone,
            'address' => $company->address,
            'status' => 'active',
            'is_default' => true,
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id',
            'currency_code' => 'IDR',
            'country_code' => 'ID',
        ]);
    }

    /**
     * Handle initial company setup.
     * Creates the first company and assigns it to the user.
     *
     * IMPORTANT: Tenant must be created FIRST, then Company, then User assignment.
     * This ensures AuditLog can use a valid tenant_id.
     *
     * Initial setup ONLY creates:
     *   1. Tenant (created first)
     *   2. Company
     *   3. User assignment (with correct tenant_id from new Tenant)
     *
     * After successful setup, redirects to the intended URL (stored by RequireCompanyContext middleware)
     * or falls back to dashboard.
     *
     * Invoice settings, footer, and other configurations are managed
     * from the regular settings page after initial setup is complete.
     */
    protected function handleInitialCompanySetup(Request $request, $user): JsonResponse|RedirectResponse|Response
    {
        try {
            $validated = $request->validate([
                'company_name' => 'required|string|max:255',
                'short_name' => 'nullable|string|max:100',
                'company_email' => 'nullable|email|max:255',
                'company_phone' => 'nullable|string|max:50',
                'website' => 'nullable|url|max:255',
                'company_address' => 'nullable|string|max:500',
                'tax_id' => 'nullable|string|max:50',
                // Note: invoice_prefix, invoice_footer, default_payment_terms, footer_text
                // are NOT part of initial setup - they are managed from regular settings page
            ]);

            DB::beginTransaction();

            // STEP 1: Create TENANT first (must be before user update to ensure valid tenant_id)
            // Note: tenants table does NOT have created_by/updated_by columns
            $tenant = Tenant::create([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'name' => $validated['company_name'],
                'slug' => \Illuminate\Support\Str::slug($validated['company_name']),
                'email' => $validated['company_email'] ?? null,
                'phone' => $validated['company_phone'] ?? null,
                'address' => $validated['company_address'] ?? null,
                'status' => 'active',
                'is_default' => true,
                'timezone' => 'Asia/Jakarta',
                'locale' => 'id',
                'currency_code' => 'IDR',
                'country_code' => 'ID',
            ]);

            Log::info('[InitialSetup] Tenant created', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'user_id' => $user->id,
            ]);

            // STEP 2: Create the company
            $company = Company::create([
                'name' => $validated['company_name'],
                'slug' => \Illuminate\Support\Str::slug($validated['company_name']),
                'short_name' => $validated['short_name'] ?: $validated['company_name'],
                'email' => $validated['company_email'] ?? null,
                'phone' => $validated['company_phone'] ?? null,
                'website' => $validated['website'] ?? null,
                'address' => $validated['company_address'] ?? null,
                'npwp' => $validated['tax_id'] ?? null,
                'is_active' => true,
            ]);

            Log::info('[InitialSetup] Company created', [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'user_id' => $user->id,
            ]);

            // STEP 3: Update user with BOTH company_id AND tenant_id
            // Use tenant->id (NOT company->id) for tenant_id
            $user->update([
                'company_id' => $company->id,
                'tenant_id' => $tenant->id, // CORRECT: use tenant.id, not company.id
            ]);

            Log::info('[InitialSetup] User assigned to company and tenant', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'tenant_id' => $tenant->id,
            ]);

            // Note: Invoice settings, payment terms, footer text are NOT set here.
            // They will use default values and can be configured from the regular settings page.
            // Invoice prefix: 'INV' (default)
            // Payment terms: 30 days (default)
            // Footer text: Set by system defaults

            DB::commit();

            Log::info('[InitialSetup] Company setup completed successfully', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'tenant_id' => $tenant->id,
            ]);

            // Redirect to intended URL (stored by RequireCompanyContext middleware)
            // Fallback to dashboard if no intended URL
            $intendedUrl = session()->pull('url.intended');

            return redirect()->to($intendedUrl ?: route('dashboard'))
                ->with('success', 'Perusahaan berhasil dibuat! Selamat datang di ' . $company->name);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            // For validation errors during initial setup, redirect back with errors
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[InitialSetup] Failed to create company', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            // For other errors, redirect back with error message
            return redirect()->back()
                ->with('error', 'Gagal membuat perusahaan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Process regular tenant update (after company exists).
     */
    protected function processTenantUpdate(Request $request, $tenant): JsonResponse
    {
        try {
            $validated = $request->validate([
                'company_name' => 'required|string|max:255',
                'short_name' => 'nullable|string|max:100',
                'company_email' => 'nullable|email|max:255',
                'company_phone' => 'nullable|string|max:50',
                'website' => 'nullable|url|max:255',
                'company_address' => 'nullable|string|max:500',
                'tax_id' => 'nullable|string|max:50',
                'invoice_prefix' => 'nullable|string|max:10',
                'invoice_footer' => 'nullable|string|max:1000',
                'default_payment_terms' => 'nullable|integer|min:1|max:365',
                'footer_text' => 'nullable|string|max:500',
            ]);

            // Update tenant basic info
            $tenant->update([
                'name' => $validated['company_name'],
                'email' => $validated['company_email'],
                'phone' => $validated['company_phone'],
                'address' => $validated['company_address'],
            ]);

            // Update CRM settings
            $tenant->setSetting('crm.tax_id', $validated['tax_id']);
            $tenant->setSetting('crm.invoice_prefix', $validated['invoice_prefix']);
            $tenant->setSetting('crm.invoice_footer', $validated['invoice_footer']);
            $tenant->setSetting('crm.default_payment_terms', $validated['default_payment_terms']);
            $tenant->setSetting('crm.footer_text', $validated['footer_text']);
            $tenant->save();

            // Clear footer cache
            if ($tenant->id) {
                Cache::forget("tenant_footer_{$tenant->id}");
            }

            return response()->json([
                'success' => true,
                'message' => 'Pengaturan berhasil diperbarui',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[Settings] Failed to update settings', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current tenant.
     */
    protected function getTenant(): ?Tenant
    {
        $user = $this->user();
        if ($user && $user->tenant_id) {
            return Tenant::find($user->tenant_id);
        }
        return Tenant::getDefault();
    }

    /**
     * Get current tenant from a specific user.
     */
    protected function getTenantForUser($user): ?Tenant
    {
        if ($user && $user->tenant_id) {
            return Tenant::find($user->tenant_id);
        }
        return Tenant::getDefault();
    }
}
