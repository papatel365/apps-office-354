<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Division;
use Illuminate\Http\Request;

class DivisionController extends Controller
{
    /**
     * Check if user can manage divisions in this company
     */
    protected function canManageDivisions(Company $company): bool
    {
        $user = auth()->user();

        // Developer and Admin Pusat can manage all
        if ($user->is_developer || $user->is_pusat_admin) {
            return true;
        }

        // Director can manage own company divisions
        if ($user->is_director && $user->company_id === $company->id) {
            return true;
        }

        // Owner and Admin of specific company
        if ($user->company_id === $company->id) {
            return in_array($user->company_role, [\App\Modules\System\Models\User::ROLE_OWNER, \App\Modules\System\Models\User::ROLE_ADMIN]);
        }

        return false;
    }

    /**
     * List divisions for company
     */
    public function index(Company $company)
    {
        if (!$this->canManageDivisions($company)) {
            abort(403, 'Anda tidak memiliki akses untuk mengelola divisi.');
        }

        $divisions = $company->divisions()->with('department')->latest()->get();

        return view('crm.companies.divisions.index', compact('company', 'divisions'));
    }

    /**
     * Show create division form
     */
    public function create(Company $company)
    {
        if (!$this->canManageDivisions($company)) {
            abort(403, 'Anda tidak memiliki akses untuk mengelola divisi.');
        }

        $departments = $company->departments()->active()->orderBy('name')->get();

        return view('crm.companies.divisions.create', compact('company', 'departments'));
    }

    /**
     * Store new division
     */
    public function store(Request $request, Company $company)
    {
        if (!$this->canManageDivisions($company)) {
            abort(403, 'Anda tidak memiliki akses untuk mengelola divisi.');
        }

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'sidebar_permissions' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $division = $company->divisions()->create([
            'department_id' => $validated['department_id'],
            'name' => $validated['name'],
            'slug' => \Illuminate\Support\Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'sidebar_permissions' => $validated['sidebar_permissions'] ?? Division::getDefaultSidebarPermissions(),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('companies.divisions.edit', [$company, $division])
            ->with('success', "Divisi {$validated['name']} berhasil dibuat. Konfigurasi permission division di halaman ini.");
    }

    /**
     * Show edit division form
     */
    public function edit(Company $company, Division $division)
    {
        if (!$this->canManageDivisions($company)) {
            abort(403, 'Anda tidak memiliki akses untuk mengelola divisi.');
        }

        abort_if($division->company_id !== $company->id, 403);

        $departments = $company->departments()->active()->orderBy('name')->get();

        return view('crm.companies.divisions.edit', compact('company', 'division', 'departments'));
    }

    /**
     * Update division
     */
    public function update(Request $request, Company $company, Division $division)
    {
        if (!$this->canManageDivisions($company)) {
            abort(403, 'Anda tidak memiliki akses untuk mengelola divisi.');
        }

        abort_if($division->company_id !== $company->id, 403);

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'sidebar_permissions' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        // Update division
        $update = [
            'department_id' => $validated['department_id'],
            'name' => $validated['name'],
            'slug' => \Illuminate\Support\Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
        ];

        // Update sidebar_permissions only if provided
        if (isset($validated['sidebar_permissions'])) {
            $update['sidebar_permissions'] = $validated['sidebar_permissions'];
        }

        // Update is_active only if provided
        if (isset($validated['is_active'])) {
            $update['is_active'] = $validated['is_active'];
        }

        $division->update($update);

        return redirect()
            ->route('companies.divisions.edit', [$company, $division])
            ->with('success', "Divisi {$validated['name']} berhasil diperbarui.");
    }

    /**
     * Delete division
     */
    public function destroy(Company $company, Division $division)
    {
        if (!$this->canManageDivisions($company)) {
            abort(403, 'Tidak ada akses untuk menghapus divisi ini.');
        }

        abort_if($division->company_id !== $company->id, 403);

        // Check members
        if ($division->users()->exists()) {
            return back()->with('error', 'Divisi ini memiliki anggota. Pindahkan anggota terlebih dahulu.');
        }

        $name = $division->name;
        $division->delete();

        return redirect()
            ->route('companies.divisions.index', $company)
            ->with('success', "Divisi {$name} berhasil dihapus.");
    }
}
