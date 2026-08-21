<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\Placement;
use App\Models\HRD\EmployeeProfile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;

class PlacementController extends Controller
{
    /**
     * Display placements list.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $search = $request->input('search');
        $status = $request->input('status', 'active');

        $query = Placement::where('company_id', $companyId);

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $placements = $query->ordered()->paginate(20);

        // Get stats
        $stats = [
            'total' => Placement::where('company_id', $companyId)->count(),
            'active' => Placement::where('company_id', $companyId)->where('is_active', true)->count(),
            'with_gps' => Placement::where('company_id', $companyId)->whereNotNull('latitude')->count(),
        ];

        return view('crm.hrd.placements.index', compact('placements', 'stats', 'search', 'status'));
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        return view('crm.hrd.placements.create');
    }

    /**
     * Store new placement.
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius_meters' => 'nullable|integer|min:10|max:5000',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $validated['company_id'] = $user->company_id;
        $validated['created_by'] = $user->id;
        $validated['radius_meters'] = $validated['radius_meters'] ?? 100;

        $placement = Placement::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Lokasi penempatan berhasil ditambahkan',
            'data' => $placement,
        ]);
    }

    /**
     * Show placement details.
     */
    public function show(int $id): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $placement = Placement::where('company_id', $companyId)
            ->findOrFail($id);

        $employees = EmployeeProfile::where('company_id', $companyId)
            ->where('placement_id', $id)
            ->where('is_active', true)
            ->with('user')
            ->orderBy('nik')
            ->get();

        return view('crm.hrd.placements.show', compact('placement', 'employees'));
    }

    /**
     * Show edit form.
     */
    public function edit(int $id): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $placement = Placement::where('company_id', $companyId)
            ->findOrFail($id);

        return view('crm.hrd.placements.edit', compact('placement'));
    }

    /**
     * Update placement.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $placement = Placement::where('company_id', $companyId)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius_meters' => 'nullable|integer|min:10|max:5000',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $validated['radius_meters'] = $validated['radius_meters'] ?? 100;

        $placement->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Lokasi penempatan berhasil diperbarui',
            'data' => $placement,
        ]);
    }

    /**
     * Delete placement.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $placement = Placement::where('company_id', $companyId)
            ->findOrFail($id);

        // Check if there are employees assigned
        if ($placement->employees()->where('is_active', true)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus lokasi yang masih memiliki karyawan aktif. Pindahkan karyawan terlebih dahulu.',
            ], 422);
        }

        $placement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lokasi penempatan berhasil dihapus',
        ]);
    }

    /**
     * Get placements for dropdown.
     */
    public function getPlacements(): JsonResponse
    {
        $user = auth()->user();

        $placements = Placement::where('company_id', $user->company_id)
            ->active()
            ->ordered()
            ->get(['id', 'name', 'code', 'radius_meters', 'latitude', 'longitude']);

        return response()->json([
            'success' => true,
            'data' => $placements,
        ]);
    }

    /**
     * Assign employee to placement.
     */
    public function assignEmployee(Request $request): JsonResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $validated = $request->validate([
            'employee_id' => 'required|exists:employee_profiles,id',
            'placement_id' => 'required|exists:employee_placements,id',
            'notes' => 'nullable|string|max:500',
        ]);

        // Verify ownership
        $employee = EmployeeProfile::where('company_id', $companyId)
            ->findOrFail($validated['employee_id']);

        $placement = Placement::where('company_id', $companyId)
            ->findOrFail($validated['placement_id']);

        $oldPlacementId = $employee->placement_id;
        $oldPlacementName = $employee->placement_name;

        // Update employee placement
        $employee->update([
            'placement_id' => $placement->id,
            'placement_name' => $placement->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Karyawan berhasil dipindahkan ke {$placement->name}",
            'data' => [
                'old_placement' => $oldPlacementName,
                'new_placement' => $placement->name,
            ],
        ]);
    }

    /**
     * Get placement history for an employee.
     */
    public function getEmployeeHistory(int $employeeId): JsonResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $employee = EmployeeProfile::where('company_id', $companyId)
            ->findOrFail($employeeId);

        // For now, return current placement only
        // A full history would require a separate history table
        $history = [
            [
                'id' => $employee->placement_id,
                'name' => $employee->placement_name,
                'assigned_at' => $employee->updated_at->toIso8601String(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
