<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Company;
use App\Services\CRM\AssetStatusService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetCategoryController extends Controller
{
    protected AssetStatusService $statusService;

    public function __construct(AssetStatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Display a listing of asset categories with filtering support.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        // Build filter parameters
        $filters = $this->buildFilters($request, $user);

        // Get filtered categories with stats
        $query = $this->buildCategoryQuery($filters, $companyId);

        // Get paginated results
        $perPage = min((int) $request->input('per_page', 25), 100);
        $categories = $query->paginate($perPage)->withQueryString();

        // Get filtered assets by category for dropdown
        $assetsQuery = Asset::query()
            ->where('company_id', $companyId)
            ->with(['category', 'photos']);

        // Apply same filters to assets for stats
        $assetsQuery = $this->applyAssetFilters($assetsQuery, $filters);
        $assetsByCategory = $assetsQuery->get()
            ->map(function ($asset) {
                $asset->multi_status_badges = $this->statusService->getAssetBadges($asset);
                return $asset;
            })
            ->groupBy('category_id');

        // Get filtered statistics
        $stats = $this->getFilteredStats($filters, $companyId);

        // Get companies for filter (if user has access to multiple)
        $companies = $this->getCompaniesForFilter($user, $companyId);

        // Get unique category types from database
        $categoryTypes = $this->getUniqueCategoryTypes();

        return view('crm.asset-categories.index', compact(
            'categories',
            'assetsByCategory',
            'stats',
            'filters',
            'companies',
            'categoryTypes'
        ));
    }

    /**
     * Get filtered data as JSON for AJAX requests.
     */
    public function filter(Request $request): JsonResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $filters = $this->buildFilters($request, $user);

        $query = $this->buildCategoryQuery($filters, $companyId);

        $perPage = min((int) $request->input('per_page', 25), 100);
        $categories = $query->paginate($perPage)->withQueryString();

        $stats = $this->getFilteredStats($filters, $companyId);

        return response()->json([
            'success' => true,
            'categories' => $categories->items(),
            'pagination' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'from' => $categories->firstItem(),
                'to' => $categories->lastItem(),
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Export categories to CSV/Excel/PDF.
     */
    public function export(Request $request): StreamedResponse|RedirectResponse
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $filters = $this->buildFilters($request, $user);
        $query = $this->buildCategoryQuery($filters, $companyId);
        $categories = $query->get();

        $format = $request->input('format', 'csv');

        if ($format === 'csv') {
            return $this->exportToCsv($categories);
        } elseif ($format === 'excel') {
            return $this->exportToExcel($categories);
        } elseif ($format === 'pdf') {
            return $this->exportToPdf($categories);
        }

        return redirect()->back()->with('error', 'Format export tidak didukung.');
    }

    /**
     * Build filters array from request.
     */
    protected function buildFilters(Request $request, $user): array
    {
        return [
            'search' => $request->input('search', ''),
            'category_type' => $request->input('category_type', ''),
            'status' => $request->input('status', ''),
            'asset_condition' => $request->input('asset_condition', ''),
            'company_id' => $request->input('company_id', ''),
            'date_range' => $request->input('date_range', ''),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
            'sort_by' => $request->input('sort_by', 'name'),
            'sort_order' => $request->input('sort_order', 'asc'),
            'per_page' => min((int) $request->input('per_page', 25), 100),
        ];
    }

    /**
     * Build main category query with filters.
     */
    protected function buildCategoryQuery(array $filters, int $companyId)
    {
        $query = AssetCategory::query()
            ->where('company_id', $companyId)
            ->with('parent');

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('uuid', 'like', "%{$search}%");
            });
        }

        // Category type filter
        if (!empty($filters['category_type'])) {
            $query->where('category_type', $filters['category_type']);
        }

        // Status filter (Active/Inactive)
        if ($filters['status'] === 'active') {
            $query->where('is_active', true);
        } elseif ($filters['status'] === 'inactive') {
            $query->where('is_active', false);
        }

        // Asset condition filter (Available, Allocated, Reserved, Maintenance)
        if (!empty($filters['asset_condition'])) {
            $condition = $filters['asset_condition'];
            $query->withCount([
                'assets as condition_assets' => function ($q) use ($condition) {
                    switch ($condition) {
                        case 'available':
                            $q->where('status', Asset::STATUS_AVAILABLE);
                            break;
                        case 'allocated':
                            $q->whereNotNull('current_allocation_id');
                            break;
                        case 'reserved':
                            $q->whereNotNull('current_reservation_id');
                            break;
                        case 'maintenance':
                            $q->whereNotNull('current_maintenance_id');
                            break;
                        case 'retired':
                            $q->where('status', Asset::STATUS_RETIRED);
                            break;
                        case 'lost':
                            $q->where('status', Asset::STATUS_LOST);
                            break;
                    }
                }
            ]);
        }

        // Date range filter
        if (!empty($filters['date_range'])) {
            switch ($filters['date_range']) {
                case 'today':
                    $query->whereDate('created_at', Carbon::today());
                    break;
                case 'this_week':
                    $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereMonth('created_at', Carbon::now()->month)
                        ->whereYear('created_at', Carbon::now()->year);
                    break;
                case 'this_year':
                    $query->whereYear('created_at', Carbon::now()->year);
                    break;
                case 'custom':
                    if (!empty($filters['date_from'])) {
                        $query->whereDate('created_at', '>=', Carbon::parse($filters['date_from']));
                    }
                    if (!empty($filters['date_to'])) {
                        $query->whereDate('created_at', '<=', Carbon::parse($filters['date_to']));
                    }
                    break;
            }
        } elseif (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', Carbon::parse($filters['date_from']));
        } elseif (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', Carbon::parse($filters['date_to']));
        }

        // Base counts (always calculated)
        $query->withCount([
            'assets as total_assets',
            'assets as allocated_assets' => function ($q) {
                $q->whereNotNull('current_allocation_id');
            },
            'assets as maintenance_assets' => function ($q) {
                $q->whereNotNull('current_maintenance_id');
            },
            'assets as reserved_assets' => function ($q) {
                $q->whereNotNull('current_reservation_id');
            },
            'assets as available_assets' => function ($q) {
                $q->where('status', Asset::STATUS_AVAILABLE);
            },
        ]);

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';

        // Map sort columns
        $sortMap = [
            'name' => 'name',
            'total' => 'total_assets',
            'allocated' => 'allocated_assets',
            'reserved' => 'reserved_assets',
            'maintenance' => 'maintenance_assets',
            'created' => 'created_at',
        ];

        $sortColumn = $sortMap[$sortBy] ?? 'name';
        $query->orderBy($sortColumn, $sortOrder === 'asc' ? 'asc' : 'desc');

        return $query;
    }

    /**
     * Apply filters to asset query.
     */
    protected function applyAssetFilters($query, array $filters)
    {
        // Search filter on assets
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('asset_number', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        // Category type filter
        if (!empty($filters['category_type'])) {
            $query->whereHas('category', function ($q) use ($filters) {
                $q->where('category_type', $filters['category_type']);
            });
        }

        // Asset condition filter
        if (!empty($filters['asset_condition'])) {
            $condition = $filters['asset_condition'];
            switch ($condition) {
                case 'available':
                    $query->where('status', Asset::STATUS_AVAILABLE);
                    break;
                case 'allocated':
                    $query->whereNotNull('current_allocation_id');
                    break;
                case 'reserved':
                    $query->whereNotNull('current_reservation_id');
                    break;
                case 'maintenance':
                    $query->whereNotNull('current_maintenance_id');
                    break;
                case 'retired':
                    $query->where('status', Asset::STATUS_RETIRED);
                    break;
                case 'lost':
                    $query->where('status', Asset::STATUS_LOST);
                    break;
            }
        }

        return $query;
    }

    /**
     * Get filtered statistics.
     */
    protected function getFilteredStats(array $filters, int $companyId): array
    {
        // Base query for all assets with same filters (except pagination)
        $query = Asset::query()
            ->where('company_id', $companyId);

        $query = $this->applyAssetFilters($query, $filters);

        $totalAssets = (clone $query)->count();
        $availableAssets = (clone $query)->where('status', Asset::STATUS_AVAILABLE)->count();
        $allocatedAssets = (clone $query)->whereNotNull('current_allocation_id')->count();
        $reservedAssets = (clone $query)->whereNotNull('current_reservation_id')->count();
        $maintenanceAssets = (clone $query)->whereNotNull('current_maintenance_id')->count();

        // Category stats (based on filtered assets)
        $query2 = AssetCategory::query()->where('company_id', $companyId);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query2->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['category_type'])) {
            $query2->where('category_type', $filters['category_type']);
        }

        if ($filters['status'] === 'active') {
            $query2->where('is_active', true);
        } elseif ($filters['status'] === 'inactive') {
            $query2->where('is_active', false);
        }

        $totalCategories = (clone $query2)->count();

        return [
            'total_categories' => $totalCategories,
            'total_assets' => $totalAssets,
            'available' => $availableAssets,
            'allocated' => $allocatedAssets,
            'reserved' => $reservedAssets,
            'maintenance' => $maintenanceAssets,
        ];
    }

    /**
     * Get companies for filter dropdown.
     */
    protected function getCompaniesForFilter($user, int $companyId): array
    {
        // Only for users with multi-company access (owner/director)
        if ($user->is_owner || $user->is_director) {
            return Company::where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn($c) => ['id' => $c->id, 'name' => $c->name])
                ->toArray();
        }

        // For others, return only their company
        return [['id' => $companyId, 'name' => $user->company?->name ?? 'Perusahaan']];
    }

    /**
     * Get unique category types from database.
     */
    protected function getUniqueCategoryTypes(): array
    {
        return AssetCategory::query()
            ->select('category_type')
            ->distinct()
            ->whereNotNull('category_type')
            ->pluck('category_type')
            ->mapWithKeys(fn($type) => [$type => ucfirst($type)])
            ->toArray();
    }

    /**
     * Export to CSV.
     */
    protected function exportToCsv($categories): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="kategori-aset-' . date('Y-m-d') . '.csv"',
        ];

        $columns = ['Nama', 'Tipe', 'Deskripsi', 'Total Aset', 'Allocated', 'Reserved', 'Maintenance', 'Status', 'Dibuat'];

        $callback = function () use ($categories, $columns) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $columns);

            foreach ($categories as $category) {
                fputcsv($file, [
                    $category->name,
                    $category->category_type_label,
                    $category->description ?? '',
                    $category->total_assets ?? 0,
                    $category->allocated_assets ?? 0,
                    $category->reserved_assets ?? 0,
                    $category->maintenance_assets ?? 0,
                    $category->is_active ? 'Aktif' : 'Nonaktif',
                    $category->created_at->format('d/m/Y'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to Excel (CSV with .xlsx extension).
     */
    protected function exportToExcel($categories): StreamedResponse
    {
        return $this->exportToCsv($categories);
    }

    /**
     * Export to PDF.
     */
    protected function exportToPdf($categories): StreamedResponse
    {
        // For simplicity, return as CSV (full PDF implementation would require DomPDF or similar)
        return $this->exportToCsv($categories);
    }

    /**
     * Show the form for creating a new category.
     */
    public function create(): View
    {
        return view('crm.asset-categories.create');
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'category_type' => 'required|in:fisik,akses',
            'access_type' => 'nullable|in:lisensi,ipam,upass',
            'description' => 'nullable|string',
            'depreciation_method' => 'nullable|in:none,straight_line,declining_balance',
            'default_lifespan_months' => 'nullable|integer|min:1',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'parent_id' => 'nullable|exists:asset_categories,id',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        // Validate parent category type matches (if parent selected)
        if ($request->parent_id) {
            $parent = AssetCategory::find($request->parent_id);
            if ($parent && $parent->category_type !== $request->category_type) {
                return redirect()->back()->withInput()->with('error', 'Kategori anak harus memiliki tipe yang sama dengan kategori utama.');
            }
        }

        AssetCategory::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $this->tenantId(),
            'company_id' => auth()->user()->company_id,
            'name' => $request->name,
            'category_type' => $request->category_type,
            'access_type' => $request->access_type,
            'description' => $request->description,
            'depreciation_method' => $request->depreciation_method ?? 'none',
            'default_lifespan_months' => $request->default_lifespan_months,
            'color' => $request->color ?? '#6B7280',
            'icon' => $request->icon,
            'parent_id' => $request->parent_id,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->is_active ?? true,
        ]);

        return redirect()->route('asset-categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the category.
     */
    public function edit(AssetCategory $assetCategory): View
    {
        $category = $assetCategory;
        return view('crm.asset-categories.edit', compact('category'));
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, AssetCategory $assetCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'category_type' => 'required|in:fisik,akses',
            'access_type' => 'nullable|in:lisensi,ipam,upass',
            'description' => 'nullable|string',
            'depreciation_method' => 'nullable|in:none,straight_line,declining_balance',
            'default_lifespan_months' => 'nullable|integer|min:1',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'parent_id' => 'nullable|exists:asset_categories,id',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        // Validate parent category type matches (if parent selected)
        if ($request->parent_id) {
            $parent = AssetCategory::find($request->parent_id);
            if ($parent && $parent->category_type !== $request->category_type) {
                return redirect()->back()->withInput()->with('error', 'Kategori anak harus memiliki tipe yang sama dengan kategori utama.');
            }
        }

        // Check for existing assets if changing category type
        $originalType = $assetCategory->category_type;
        $newType = $request->category_type;

        if ($originalType !== $newType) {
            $assetsCount = $assetCategory->assets()->count();
            if ($assetsCount > 0) {
                return redirect()->back()->withInput()->with('error', "Tidak dapat mengubah tipe kategori karena sudah ada {$assetsCount} asset yang menggunakan kategori ini. Pindahkan atau hapus asset terlebih dahulu.");
            }
        }

        $assetCategory->update($request->only([
            'name', 'category_type', 'access_type', 'description', 'depreciation_method',
            'default_lifespan_months', 'color', 'icon', 'parent_id', 'sort_order', 'is_active'
        ]));

        return redirect()->route('asset-categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    /**
     * Remove the specified category.
     */
    public function destroy(AssetCategory $assetCategory): RedirectResponse
    {
        // Move children to parent
        $assetCategory->children()->update(['parent_id' => $assetCategory->parent_id]);

        $assetCategory->delete();

        return redirect()->route('asset-categories.index')->with('success', 'Kategori berhasil dihapus.');
    }
}
