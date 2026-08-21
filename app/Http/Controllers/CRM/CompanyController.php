<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Division;
use App\Modules\System\Models\User;
use App\Services\PAPATELAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CompanyController extends Controller
{
    /**
     * Check if user can access companies list
     */
    protected function canAccessCompanies(): bool
    {
        $user = auth()->user();
        return $user->is_developer || $user->is_pusat_admin || $user->is_director;
    }

    /**
     * Check if user can manage a specific company
     */
    protected function canManageCompany(Company $company): bool
    {
        $user = auth()->user();

        // Developer and Admin Pusat can manage all companies
        if ($user->is_developer || $user->is_pusat_admin) {
            return true;
        }

        // Director can manage their own company
        if ($user->is_director && $user->company_id === $company->id) {
            return true;
        }

        // Owner and Admin can only manage their own company
        if ($user->company_id === $company->id) {
            return in_array($user->company_role, [User::ROLE_OWNER, User::ROLE_ADMIN]);
        }

        return false;
    }

    /**
     * Check if user can view a specific company
     */
    protected function canViewCompany(Company $company): bool
    {
        $user = auth()->user();

        // Developer and Admin Pusat can view all companies
        if ($user->is_developer || $user->is_pusat_admin) {
            return true;
        }

        // Director can view their own company
        if ($user->is_director && $user->company_id === $company->id) {
            return true;
        }

        // Owner and Admin can view their own company
        if ($user->company_id === $company->id) {
            return in_array($user->company_role, [User::ROLE_OWNER, User::ROLE_ADMIN, User::ROLE_DIRECTOR]);
        }

        return false;
    }

    /**
     * Get allowed roles that the current user can assign
     * Owners/Directors can only assign roles below them (admin, manager, staff)
     * Admins can only assign staff
     */
    protected function getAllowedRoles(): array
    {
        $user = auth()->user();

        // Developer and Admin Pusat can assign any role
        if ($user->is_developer || $user->is_pusat_admin) {
            return [
                User::ROLE_OWNER,
                User::ROLE_DIRECTOR,
                User::ROLE_ADMIN,
                User::ROLE_MANAGER,
                User::ROLE_STAFF,
            ];
        }

        // Owner and Director can assign roles below them (no owner/director)
        if ($user->is_owner || $user->is_director) {
            return [
                User::ROLE_ADMIN,
                User::ROLE_MANAGER,
                User::ROLE_STAFF,
            ];
        }

        // Admin can only assign staff
        if ($user->is_company_admin) {
            return [
                User::ROLE_STAFF,
            ];
        }

        return [];
    }

    /**
     * Check if user can assign a specific role
     */
    protected function canAssignRole(string $role): bool
    {
        return in_array($role, $this->getAllowedRoles());
    }

    public function index()
    {
        $user = auth()->user();

        // Developer can see all companies
        if ($user->is_developer) {
            $companies = Company::withCount('users')
                ->when(request('search'), function ($query) {
                    $query->where('name', 'like', '%' . request('search') . '%');
                })
                ->when(request('status'), function ($query) {
                    $query->where('is_active', request('status') === 'active');
                })
                ->latest()
                ->paginate(10);

            return view('crm.companies.index', compact('companies'));
        }

        // Admin Pusat can see all companies too
        if ($user->is_pusat_admin) {
            $companies = Company::withCount('users')
                ->when(request('search'), function ($query) {
                    $query->where('name', 'like', '%' . request('search') . '%');
                })
                ->when(request('status'), function ($query) {
                    $query->where('is_active', request('status') === 'active');
                })
                ->latest()
                ->paginate(10);

            return view('crm.companies.index', compact('companies'));
        }

        // Director or Owner of a company - redirect to their own company detail page
        if (($user->is_director || $user->is_owner) && $user->company_id) {
            return redirect()->route('companies.show', $user->company);
        }

        // No access
        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }

    public function create()
    {
        // Only Developer and Pusat Admin can create companies
        $user = auth()->user();
        if (!$user->is_developer && !$user->is_pusat_admin) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return view('crm.companies.create');
    }

    public function store(Request $request)
    {
        // Only Developer and Pusat Admin can create companies
        $user = auth()->user();
        if (!$user->is_developer && !$user->is_pusat_admin) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:companies,slug',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $company = Company::create($validated);

        return redirect()
            ->route('companies.show', $company)
            ->with('success', 'Perusahaan berhasil dibuat!');
    }

    public function show(Company $company)
    {
        if (!$this->canViewCompany($company)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $company->load(['users', 'divisions', 'departments', 'positions']);

        $stats = [
            'total_users' => $company->users()->count(),
            'active_users' => $company->users()->where('is_active', true)->count(),
            'admins' => $company->users()->whereIn('company_role', [User::ROLE_OWNER, User::ROLE_ADMIN])->count(),
            'staff' => $company->users()->where('company_role', User::ROLE_STAFF)->count(),
            'total_divisions' => $company->divisions()->count(),
            'total_departments' => $company->departments()->count(),
            'total_positions' => $company->positions()->count(),
        ];

        // Get available employees for dropdowns
        $employees = \App\Models\HRD\EmployeeProfile::where('company_id', $company->id)
            ->where('is_active', true)
            ->with('user')
            ->get()
            ->map(function ($emp) {
                return [
                    'id' => $emp->id,
                    'name' => $emp->full_name ?? $emp->user->name ?? 'Unknown',
                    'position' => $emp->position ? $emp->position->name : '-',
                ];
            });

        return view('crm.companies.show', compact('company', 'stats', 'employees'));
    }

    /**
     * Check if user can edit company profile (name, address, etc.)
     * Developer, Pusat Admin, and Director can edit company profile
     * Owner/Admin cannot edit company profile (only manage members/divisions)
     */
    protected function canEditCompanyProfile(Company $company): bool
    {
        $user = auth()->user();

        // Developer and Admin Pusat can edit all company profiles
        if ($user->is_developer || $user->is_pusat_admin) {
            return true;
        }

        // Director can edit their own company profile
        if ($user->is_director && $user->company_id === $company->id) {
            return true;
        }

        return false;
    }

    public function edit(Company $company)
    {
        if (!$this->canEditCompanyProfile($company)) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit profil perusahaan.');
        }

        return view('crm.companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        if (!$this->canEditCompanyProfile($company)) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit profil perusahaan.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:companies,slug,' . $company->id,
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $company->update($validated);

        return redirect()
            ->route('companies.show', $company)
            ->with('success', 'Perusahaan berhasil diperbarui!');
    }

    public function destroy(Company $company)
    {
        // Only Developer and Pusat Admin can delete companies
        $user = auth()->user();
        if (!$user->is_developer && !$user->is_pusat_admin) {
            abort(403, 'Hanya Developer dan Admin Pusat yang dapat menghapus perusahaan.');
        }

        if ($company->users()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus perusahaan yang masih memiliki anggota!');
        }

        $company->delete();

        return redirect()
            ->route('companies.index')
            ->with('success', 'Perusahaan berhasil dihapus!');
    }

    // =====================================================
    // User Management within Company
    // =====================================================

    public function createMember(Company $company)
    {
        if (!$this->canManageCompany($company)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $divisions = $company->divisions()->where('is_active', true)->get();
        $departments = $company->departments()->where('is_active', true)->get();
        $allowedRoles = $this->getAllowedRoles();

        // Build role labels
        $availableRoles = [];
        foreach ($allowedRoles as $role) {
            $availableRoles[$role] = User::getRoles()[$role] ?? $role;
        }

        return view('crm.companies.members.create', compact('company', 'divisions', 'departments', 'availableRoles'));
    }

    public function storeMember(Request $request, Company $company)
    {
        if (!$this->canManageCompany($company)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        // Get allowed roles for current user
        $allowedRoles = $this->getAllowedRoles();
        $rolesString = implode(',', $allowedRoles);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
            'phone' => 'nullable|string|max:20',
            'company_role' => 'required|string|in:' . $rolesString,
            'division_id' => 'nullable|exists:divisions,id',
            'sidebar_permissions' => 'nullable|array',
            'sidebar_permissions.*' => 'string',
            'is_active' => 'boolean',

            // HRD Fields
            'nik' => 'nullable|string|max:20',
            'kk_number' => 'nullable|string|max:20',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'religion' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'province' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'village' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'ktp_address' => 'nullable|string',
            'blood_type' => 'nullable|string|max:5',
            'marital_status' => 'nullable|string|max:20',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relation' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:100',
            'bpjs_number' => 'nullable|string|max:20',
            'npwp_number' => 'nullable|string|max:20',
            'father_name' => 'nullable|string|max:100',
            'mother_name' => 'nullable|string|max:100',
            'mother_maiden_name' => 'nullable|string|max:100',
        ]);

        // Validate division belongs to this company
        if (!empty($validated['division_id'])) {
            $division = Division::find($validated['division_id']);
            if ($division->company_id !== $company->id) {
                return back()->withErrors(['division_id' => 'Divisi tidak valid untuk perusahaan ini.'])->withInput();
            }
        }

        // Get default permissions if not provided
        $permissions = $validated['sidebar_permissions'] ?? User::getDefaultSidebarPermissions($validated['company_role']);

        // HRD fields
        $hrdFields = [
            'nik', 'kk_number', 'birth_place', 'birth_date', 'gender', 'religion',
            'address', 'province', 'city', 'district', 'village', 'postal_code',
            'ktp_address', 'blood_type', 'marital_status',
            'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation',
            'bank_name', 'bank_account_number', 'bank_account_name',
            'bpjs_number', 'npwp_number',
            'father_name', 'mother_name', 'mother_maiden_name',
        ];

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone' => $validated['phone'] ?? null,
            'company_role' => $validated['company_role'],
            'division_id' => $validated['division_id'] ?? null,
            'sidebar_permissions' => $permissions,
            'is_active' => $validated['is_active'] ?? true,
        ];

        // Add HRD fields
        foreach ($hrdFields as $field) {
            if (isset($validated[$field])) {
                $userData[$field] = $validated[$field];
            }
        }

        $user = $company->users()->create($userData);

        return redirect()
            ->route('companies.show', $company)
            ->with('success', "Anggota {$user->name} berhasil ditambahkan!");
    }

    public function editMember(Company $company, User $user)
    {
        if (!$this->canManageCompany($company)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        abort_if($user->company_id !== $company->id, 403);

        $divisions = $company->divisions()->where('is_active', true)->get();
        $departments = $company->departments()->where('is_active', true)->get();
        $allowedRoles = $this->getAllowedRoles();

        // Build role labels
        $availableRoles = [];
        foreach ($allowedRoles as $role) {
            $availableRoles[$role] = User::getRoles()[$role] ?? $role;
        }

        // If user is already owner, they should still be able to edit themselves
        if ($user->company_role === User::ROLE_OWNER && !isset($availableRoles[User::ROLE_OWNER])) {
            $availableRoles[User::ROLE_OWNER] = User::getRoles()[User::ROLE_OWNER] ?? User::ROLE_OWNER;
        }

        return view('crm.companies.members.edit', compact('company', 'user', 'divisions', 'departments', 'availableRoles'));
    }

    public function updateMember(Request $request, Company $company, User $user)
    {
        if (!$this->canManageCompany($company)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        abort_if($user->company_id !== $company->id, 403);

        // Get allowed roles for current user
        $allowedRoles = $this->getAllowedRoles();
        $rolesString = implode(',', $allowedRoles);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'company_role' => 'required|string|in:' . $rolesString,
            'division_id' => 'nullable|exists:divisions,id',
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'is_active' => 'boolean',

            // HRD Fields
            'nik' => 'nullable|string|max:20',
            'kk_number' => 'nullable|string|max:20',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'religion' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'province' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'village' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'ktp_address' => 'nullable|string',
            'blood_type' => 'nullable|string|max:5',
            'marital_status' => 'nullable|string|max:20',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relation' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:100',
            'bpjs_number' => 'nullable|string|max:20',
            'npwp_number' => 'nullable|string|max:20',
            'father_name' => 'nullable|string|max:100',
            'mother_name' => 'nullable|string|max:100',
            'mother_maiden_name' => 'nullable|string|max:100',
        ]);

        // Validate division belongs to this company
        if (!empty($validated['division_id'])) {
            $division = Division::find($validated['division_id']);
            if ($division->company_id !== $company->id) {
                return back()->withErrors(['division_id' => 'Divisi tidak valid untuk perusahaan ini.'])->withInput();
            }
        }

        // Prevent owner from demoting themselves
        $currentUser = auth()->user();
        if ($user->id === $currentUser->id && $user->company_role === User::ROLE_OWNER && $validated['company_role'] !== User::ROLE_OWNER) {
            return back()->withErrors(['company_role' => 'Anda tidak dapat mengubah role Anda sendiri sebagai Directeur Utama.'])->withInput();
        }

        // Build user data
        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'company_role' => $validated['company_role'],
        ];

        // Handle division_id
        if (isset($validated['division_id']) && $validated['division_id'] !== '') {
            $userData['division_id'] = $validated['division_id'];
        } else {
            $userData['division_id'] = null;
        }

        // Handle is_active
        $userData['is_active'] = $validated['is_active'] ?? true;

        // Handle password
        if (!empty($validated['password'])) {
            $userData['password'] = $validated['password'];
        }

        // HRD fields
        $hrdFields = [
            'nik', 'kk_number', 'birth_place', 'birth_date', 'gender', 'religion',
            'address', 'province', 'city', 'district', 'village', 'postal_code',
            'ktp_address', 'blood_type', 'marital_status',
            'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation',
            'bank_name', 'bank_account_number', 'bank_account_name',
            'bpjs_number', 'npwp_number',
            'father_name', 'mother_name', 'mother_maiden_name',
        ];

        foreach ($hrdFields as $field) {
            if (isset($validated[$field])) {
                $userData[$field] = $validated[$field];
            } else {
                $userData[$field] = null;
            }
        }

        $user->update($userData);

        return redirect()
            ->route('companies.show', $company)
            ->with('success', "Anggota {$user->name} berhasil diperbarui!");
    }

    public function destroyMember(Company $company, User $user)
    {
        if (!$this->canManageCompany($company)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        abort_if($user->company_id !== $company->id, 403);
        abort_if($user->id === auth()->id(), 400, 'Tidak dapat menghapus akun sendiri!');

        $name = $user->name;
        $userId = $user->id;

        // Delete related records first to avoid foreign key constraint violations
        // These are tables with user_id that don't have cascade/null on delete
        $tablesToClean = [
            'project_members',
            'task_assignees',
            'proposals',
            'payments',
            'subscriptions',
            'estimates',
            'invoices',
            'credit_notes',
            'contracts',
            'task_comments',
            'comments',
            'asset_reservations',
            'asset_checkin_checkout',
            'clients',
            'leads',
            'contacts',
            'items',
            'transactions',
            'activity_logs',
            'audit_logs',
            'sessions',
        ];

        foreach ($tablesToClean as $table) {
            if (\Schema::hasTable($table)) {
                \DB::table($table)->where('user_id', $userId)->delete();
            }
        }

        // Also check for created_by, updated_by references
        $userRefTables = [
            'clients', 'contacts', 'items', 'transactions', 'activity_logs', 'audit_logs',
            'proposals', 'estimates', 'invoices', 'credit_notes', 'payments', 'subscriptions',
            'contracts', 'tasks', 'projects', 'leads', 'assets',
            'estimate_requests', 'attachments', 'asset_transfers', 'asset_maintenance',
            'asset_allocations', 'asset_checkin_checkout', 'asset_reservations',
            'knowledge_base', 'task_comments', 'comments',
            'sales_leads', 'sales_customers', 'sales_prospects', 'sales_deals',
            'sales_followups', 'sales_quotations', 'sales_orders', 'sales_targets',
            'sales_activities', 'sales_commissions',
            'payment_transactions', 'contract_extensions', 'contract_addendums',
            'project_milestones', 'users', // For created_by, updated_by self-reference
        ];

        foreach ($userRefTables as $table) {
            if (\Schema::hasTable($table)) {
                \DB::table($table)->where('created_by', $userId)->delete();
                \DB::table($table)->where('updated_by', $userId)->delete();
            }
        }

        // Now delete the user
        $user->delete();

        return redirect()
            ->route('companies.show', $company)
            ->with('success', "Anggota {$name} berhasil dihapus!");
    }

    // =====================================================
    // API Endpoints for Settings Page
    // =====================================================

    /**
     * Get current company information for the authenticated user.
     * Returns company data if user has a company_id.
     */
    public function current()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        if (!$user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada perusahaan yang terhubung',
            ], 404);
        }

        $company = Company::find($user->company_id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Perusahaan tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'company' => [
                'id' => $company->id,
                'uuid' => $company->uuid,
                'name' => $company->name,
                'short_name' => $company->short_name,
                'alias' => $company->short_name, // Alias = short_name for frontend
                'tagline' => $company->tagline,
                'email' => $company->email,
                'phone' => $company->phone,
                'website' => $company->website,
                'address' => $company->address,
                'npwp' => $company->npwp,
                'slug' => $company->slug,
                'logo_url' => $company->logo ? asset('storage/' . $company->logo) : null,
                'favicon_url' => $company->favicon ? asset('storage/' . $company->favicon) : null,
                'footer_text' => $company->footer_text,
                'is_active' => $company->is_active,
            ],
        ]);
    }

    /**
     * Update company identity information.
     * Updates the company associated with the authenticated user.
     */
    public function updateIdentity(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        if (!$user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada perusahaan yang terhubung',
            ], 400);
        }

        $company = Company::find($user->company_id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Perusahaan tidak ditemukan',
            ], 404);
        }

        // Validate only fields that exist in the companies table
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'alias' => 'nullable|string|max:100', // alias maps to short_name
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string|max:500',
            'npwp' => 'nullable|string|max:32',
            'footer_text' => 'nullable|string|max:500',
        ]);

        // Map alias to short_name
        $updateData = [];
        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
            // Auto-update slug if name changes
            if (empty($validated['alias']) && $company->name !== $validated['name']) {
                $updateData['slug'] = \Illuminate\Support\Str::slug($validated['name']);
            }
        }
        if (array_key_exists('alias', $validated)) {
            $updateData['short_name'] = $validated['alias'];
        }
        if (isset($validated['email'])) {
            $updateData['email'] = $validated['email'];
        }
        if (isset($validated['phone'])) {
            $updateData['phone'] = $validated['phone'];
        }
        if (isset($validated['website'])) {
            $updateData['website'] = $validated['website'];
        }
        if (isset($validated['address'])) {
            $updateData['address'] = $validated['address'];
        }
        if (isset($validated['npwp'])) {
            $updateData['npwp'] = $validated['npwp'];
        }
        if (array_key_exists('footer_text', $validated)) {
            $updateData['footer_text'] = $validated['footer_text'];
        }

        // Only update if there are changes
        if (!empty($updateData)) {
            $company->update($updateData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Informasi perusahaan berhasil disimpan',
        ]);
    }
}
