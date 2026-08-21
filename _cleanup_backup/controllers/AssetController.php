<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Http\Requests\AssetRequest;
use App\Services\CRM\AssetStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AssetController extends Controller
{
    protected AssetStatusService $statusService;

    public function __construct(AssetStatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Display a listing of assets.
     * Uses centralized AssetStatusService for consistency.
     */
    public function index(Request $request): View
    {
        // Multi-status stats using centralized service
        $stats = $this->statusService->getStatistics();

        $assets = Asset::query()
            ->with(['category', 'creator', 'activities', 'photos'])
            ->when($request->category_id, fn($q) => $q->byCategory($request->category_id))
            ->when($request->status, fn($q) => $q->byStatus($request->status))
            ->when($request->location, fn($q) => $q->byLocation($request->location))
            ->when($request->search, function($q) use ($request) {
                $q->where(function($q2) use ($request) {
                    $q2->where('name', 'like', '%' . $request->search . '%')
                       ->orWhere('asset_number', 'like', '%' . $request->search . '%')
                       ->orWhere('serial_number', 'like', '%' . $request->search . '%');
                });
            })
            ->orderBy('name')
            ->paginate(20);

        // Add multi-status badges to each asset
        $assets->getCollection()->transform(function ($asset) {
            $asset->multi_status_badges = $this->statusService->getAssetBadges($asset);
            return $asset;
        });

        // Get categories with asset count using centralized service
        $categories = AssetCategory::query()
            ->withCount([
                'assets as total_assets',
                'assets as allocated_assets' => fn($q) => $q->whereNotNull('current_allocation_id'),
                'assets as maintenance_assets' => fn($q) => $q->whereNotNull('current_maintenance_id'),
                'assets as reserved_assets' => fn($q) => $q->whereNotNull('current_reservation_id'),
            ])
            ->get();

        return view('crm.assets.index', compact('assets', 'stats', 'categories'));
    }

    /**
     * Show the form for creating a new asset.
     */
    public function create(): View
    {
        $categories = AssetCategory::where('tenant_id', $this->tenantId())->orderBy('name')->get();
        return view('crm.assets.create', compact('categories'));
    }

    /**
     * Store a newly created asset.
     * WITH COMPREHENSIVE DEBUGGING - DO NOT HIDE ERRORS
     */
    public function store(AssetRequest $request): RedirectResponse|JsonResponse
    {
        // Log every step for debugging
        Log::info('========== ASSET STORE START ==========');
        Log::info('REQUEST METHOD: ' . $request->method());
        Log::info('REQUEST ALL DATA: ' . json_encode($request->all()));
        Log::info('USER ID: ' . ($this->user()->id ?? 'null'));
        Log::info('TENANT ID: ' . ($this->tenantId() ?? 'null'));
        Log::info('COMPANY ID: ' . ($this->user()->company_id ?? 'null'));

        try {
            // =====================================================
            // STEP 1: VALIDATION
            // =====================================================
            Log::info('[STEP 1] Getting validated data...');

            $data = $request->validated();

            Log::info('[STEP 1] Validation PASSED');
            Log::info('[STEP 1] Validated data keys: ' . implode(', ', array_keys($data)));

            // =====================================================
            // STEP 2: SET SYSTEM FIELDS
            // =====================================================
            Log::info('[STEP 2] Setting system fields...');

            $data['uuid'] = \Str::uuid();
            $data['tenant_id'] = $this->tenantId();
            $data['created_by'] = $this->user()->id;
            $data['company_id'] = $this->user()->company_id;
            $data['asset_number'] = Asset::generateNumber();
            $data['qr_identifier'] = 'AST-' . $data['uuid'];

            Log::info('[STEP 2] System fields set: uuid=' . $data['uuid'] . ', asset_number=' . $data['asset_number']);

            // =====================================================
            // STEP 3: GET CATEGORY
            // =====================================================
            Log::info('[STEP 3] Getting category...');
            Log::info('[STEP 3] category_id = ' . ($data['category_id'] ?? 'null'));

            if (empty($data['category_id'])) {
                throw new \Exception('Category ID is required but empty after validation.');
            }

            $category = AssetCategory::find($data['category_id']);

            if (!$category) {
                throw new \Exception('Category not found with ID: ' . $data['category_id']);
            }

            Log::info('[STEP 3] Category found: ' . ($category->name ?? 'unnamed') . ', type: ' . ($category->category_type ?? 'null'));

            // =====================================================
            // STEP 4: GENERATE AUTO NAME
            // =====================================================
            Log::info('[STEP 4] Generating auto name...');
            Log::info('[STEP 4] Access type from request: ' . ($data['access_type'] ?? 'null'));

            $data['name'] = Asset::generateAutoName(
                companyId: $data['company_id'],
                tenantId: $data['tenant_id'],
                categoryType: $category->category_type,
                accessType: $data['access_type'] ?? null,
                data: $data
            );

            Log::info('[STEP 4] Generated name: ' . $data['name']);

            // =====================================================
            // STEP 5: CALCULATE CURRENT VALUE
            // =====================================================
            Log::info('[STEP 5] Calculating current value...');

            if (!isset($data['current_value'])) {
                $data['current_value'] = $data['purchase_cost'] ?? 0;
            }

            Log::info('[STEP 5] current_value set to: ' . $data['current_value']);

            // =====================================================
            // STEP 6: CALCULATE MAINTENANCE END DATE
            // =====================================================
            Log::info('[STEP 6] Calculating maintenance end date...');

            if (!empty($data['maintenance_start_date']) && !empty($data['maintenance_duration_days'])) {
                $startDate = \Carbon\Carbon::parse($data['maintenance_start_date']);
                $duration = (int) $data['maintenance_duration_days'];
                $data['maintenance_duration_days'] = $duration;
                $data['maintenance_end_date'] = $startDate->addDays($duration)->toDateString();
                Log::info('[STEP 6] maintenance_end_date set to: ' . $data['maintenance_end_date']);
            } else {
                Log::info('[STEP 6] No maintenance date calculation needed');
            }

            // =====================================================
            // STEP 7: CHECK FILLABLE FIELDS
            // =====================================================
            Log::info('[STEP 7] Checking fillable fields...');
            Log::info('[STEP 7] Data to be inserted: ' . json_encode(array_keys($data)));

            // Check if any field is NOT in fillable
            $model = new Asset();
            $nonFillable = [];
            foreach (array_keys($data) as $key) {
                if (!in_array($key, $model->getFillable())) {
                    $nonFillable[] = $key;
                }
            }

            if (!empty($nonFillable)) {
                Log::warning('[STEP 7] WARNING - Fields not in fillable: ' . implode(', ', $nonFillable));
            } else {
                Log::info('[STEP 7] All fields are in fillable list');
            }

            // =====================================================
            // STEP 8: CREATE ASSET
            // =====================================================
            Log::info('[STEP 8] Creating asset...');
            Log::info('[STEP 8] DATA TO INSERT: ' . json_encode($data, JSON_PRETTY_PRINT));

            try {
                $asset = Asset::create($data);
                Log::info('[STEP 8] SUCCESS - Asset created with ID: ' . $asset->id);
                Log::info('[STEP 8] Asset UUID: ' . $asset->uuid);
            } catch (\Illuminate\Database\QueryException $e) {
                Log::error('[STEP 8] DATABASE ERROR during Asset::create()');
                Log::error('[STEP 8] SQL STATE: ' . ($e->errorInfo[0] ?? 'unknown'));
                Log::error('[STEP 8] SQL ERROR CODE: ' . ($e->errorInfo[1] ?? 'unknown'));
                Log::error('[STEP 8] SQL ERROR MESSAGE: ' . ($e->errorInfo[2] ?? $e->getMessage()));
                Log::error('[STEP 8] EXCEPTION FILE: ' . $e->getFile());
                Log::error('[STEP 8] EXCEPTION LINE: ' . $e->getLine());
                throw $e;
            }

            // =====================================================
            // STEP 9: HANDLE MULTI-PHOTO UPLOADS
            // =====================================================
            Log::info('[STEP 9] Checking for multi-photo uploads...');
            Log::info('[STEP 9] hasFile(photos): ' . ($request->hasFile('photos') ? 'YES' : 'NO'));

            if ($request->hasFile('photos')) {
                $files = $request->file('photos');
                Log::info('[STEP 9] Number of photos: ' . count($files));

                foreach ($files as $index => $file) {
                    Log::info('[STEP 9] Photo ' . $index . ': ' . $file->getClientOriginalName() . ' (' . $file->getSize() . ' bytes)');
                }

                $this->processMultiplePhotoUploads($request->file('photos'), $asset);
                Log::info('[STEP 9] Multi-photo upload completed');
            } else {
                Log::info('[STEP 9] No multi-photo uploads');
            }

            // =====================================================
            // STEP 10: HANDLE SINGLE PHOTO (LEGACY)
            // =====================================================
            Log::info('[STEP 10] Checking for single photo upload...');
            Log::info('[STEP 10] hasFile(photo): ' . ($request->hasFile('photo') ? 'YES' : 'NO'));

            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                Log::info('[STEP 10] Single photo: ' . $file->getClientOriginalName() . ' (' . $file->getSize() . ' bytes)');
                $this->processPhotoUpload($request->file('photo'), $asset);
                Log::info('[STEP 10] Single photo upload completed');
            } else {
                Log::info('[STEP 10] No single photo upload');
            }

            // =====================================================
            // STEP 11: HANDLE WARRANTY CARD UPLOAD
            // =====================================================
            Log::info('[STEP 11] Checking for warranty card upload...');
            Log::info('[STEP 11] hasFile(warranty_card): ' . ($request->hasFile('warranty_card') ? 'YES' : 'NO'));

            if ($request->hasFile('warranty_card')) {
                $file = $request->file('warranty_card');
                Log::info('[STEP 11] Warranty card: ' . $file->getClientOriginalName() . ' (' . $file->getSize() . ' bytes)');
                $this->processWarrantyCardUpload($request->file('warranty_card'), $asset);
                Log::info('[STEP 11] Warranty card upload completed');
            } else {
                Log::info('[STEP 11] No warranty card upload');
            }

            // =====================================================
            // STEP 12: LOG ACTIVITY
            // =====================================================
            Log::info('[STEP 12] Logging asset creation activity...');

            try {
                $asset->logActivity('Asset dibuat', 'asset_create');
                Log::info('[STEP 12] Activity logged successfully');
            } catch (\Exception $e) {
                Log::warning('[STEP 12] Failed to log activity: ' . $e->getMessage());
                // Don't throw - this is not critical
            }

            // =====================================================
            // SUCCESS - RETURN REDIRECT
            // =====================================================
            Log::info('========== ASSET STORE SUCCESS ==========');
            Log::info('Asset ID: ' . $asset->id);
            Log::info('Asset Name: ' . $asset->name);
            Log::info('Redirecting to: ' . route('assets.show', $asset));

            return redirect()
                ->route('assets.show', $asset)
                ->with('success', 'Asset berhasil dibuat!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            // =====================================================
            // VALIDATION EXCEPTION
            // =====================================================
            Log::error('========== ASSET STORE VALIDATION ERROR ==========');
            Log::error('VALIDATION ERRORS: ' . json_encode($e->errors(), JSON_PRETTY_PRINT));
            Log::error('REQUEST DATA: ' . json_encode($request->all()));
            Log::error('==============================================');

            throw $e; // Re-throw to let Laravel handle it normally

        } catch (\Illuminate\Database\QueryException $e) {
            // =====================================================
            // DATABASE EXCEPTION
            // =====================================================
            Log::error('========== ASSET STORE DATABASE ERROR ==========');
            Log::error('EXCEPTION MESSAGE: ' . $e->getMessage());
            Log::error('SQL STATE: ' . ($e->errorInfo[0] ?? 'unknown'));
            Log::error('SQL ERROR CODE: ' . ($e->errorInfo[1] ?? 'unknown'));
            Log::error('SQL ERROR MESSAGE: ' . ($e->errorInfo[2] ?? 'N/A'));
            Log::error('FILE: ' . $e->getFile());
            Log::error('LINE: ' . $e->getLine());
            Log::error('BINDINGS: ' . json_encode($e->getBindings()));
            Log::error('==============================================');

            return redirect()
                ->back()
                ->withInput($request->all())
                ->with('error', 'Gagal menyimpan asset. Error: ' . $e->getMessage() . ' (SQL: ' . ($e->errorInfo[2] ?? 'N/A') . ')');

        } catch (\Exception $e) {
            // =====================================================
            // GENERAL EXCEPTION
            // =====================================================
            Log::error('========== ASSET STORE GENERAL ERROR ==========');
            Log::error('EXCEPTION CLASS: ' . get_class($e));
            Log::error('EXCEPTION MESSAGE: ' . $e->getMessage());
            Log::error('FILE: ' . $e->getFile());
            Log::error('LINE: ' . $e->getLine());
            Log::error('TRACE: ' . $e->getTraceAsString());
            Log::error('==============================================');

            return redirect()
                ->back()
                ->withInput($request->all())
                ->with('error', 'Gagal menyimpan asset. Error: ' . $e->getMessage());
        }
    }

    /**
     * Process multiple photo uploads without hardcoded limit.
     * WITH DEBUGGING
     *
     * @param array $files
     * @param Asset $asset
     */
    protected function processMultiplePhotoUploads(array $files, Asset $asset): void
    {
        Log::info('[PHOTO MULTI-UPLOAD] Starting...');
        Log::info('[PHOTO MULTI-UPLOAD] Number of files: ' . count($files));

        $user = $this->user();

        foreach ($files as $index => $file) {
            Log::info('[PHOTO MULTI-UPLOAD] Processing file index: ' . $index);
            Log::info('[PHOTO MULTI-UPLOAD] Original name: ' . $file->getClientOriginalName());
            Log::info('[PHOTO MULTI-UPLOAD] Size: ' . $file->getSize() . ' bytes');
            Log::info('[PHOTO MULTI-UPLOAD] MIME type: ' . $file->getMimeType());
            Log::info('[PHOTO MULTI-UPLOAD] Extension: ' . $file->getClientOriginalExtension());

            try {
                $uuid = \Str::uuid()->toString();
                $extension = $file->getClientOriginalExtension();
                $filename = $uuid . '.' . $extension;
                $path = 'assets/' . $filename;

                Log::info('[PHOTO MULTI-UPLOAD] Generated filename: ' . $filename);
                Log::info('[PHOTO MULTI-UPLOAD] Storage path: ' . $path);

                // Store the image
                $result = \Storage::disk('public')->putFileAs('assets', $file, $filename);
                Log::info('[PHOTO MULTI-UPLOAD] Storage putFileAs result: ' . ($result ? 'SUCCESS' : 'FAILED'));

                // Verify file exists
                $exists = \Storage::disk('public')->exists($path);
                Log::info('[PHOTO MULTI-UPLOAD] File exists after save: ' . ($exists ? 'YES' : 'NO'));

                if (!$exists) {
                    Log::error('[PHOTO MULTI-UPLOAD] File NOT found after save! Path: ' . $path);
                }

                // Create photo record
                $photo = $asset->photos()->create([
                    'uuid' => $uuid,
                    'file_path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'is_cover' => $index === 0 && !$asset->photos()->exists(), // First photo is cover
                    'sort_order' => $index,
                    'uploaded_by' => $user->id,
                ]);

                Log::info('[PHOTO MULTI-UPLOAD] Photo record created with ID: ' . $photo->id);

                // Create thumbnail (simple copy, no image processing needed)
                try {
                    $thumbnailFilename = 'thumb_' . $filename;
                    $thumbnailPath = 'assets/' . $thumbnailFilename;
                    $thumbResult = \Storage::disk('public')->putFileAs('assets', $file, $thumbnailFilename);
                    Log::info('[PHOTO MULTI-UPLOAD] Thumbnail save result: ' . ($thumbResult ? 'SUCCESS' : 'FAILED'));
                    $photo->update(['thumbnail_path' => $thumbnailPath]);
                    Log::info('[PHOTO MULTI-UPLOAD] Thumbnail path updated: ' . $thumbnailPath);
                } catch (\Exception $e) {
                    Log::warning('[PHOTO MULTI-UPLOAD] Failed to create thumbnail: ' . $e->getMessage());
                }

                Log::info("[PHOTO MULTI-UPLOAD] Success - asset_uuid:{$asset->uuid}, photo_uuid:{$uuid}, filename:{$file->getClientOriginalName()}");

            } catch (\Exception $e) {
                Log::error("[PHOTO MULTI-UPLOAD] FAILED - asset_uuid:{$asset->uuid}, filename:{$file->getClientOriginalName()}");
                Log::error("[PHOTO MULTI-UPLOAD] Error message: " . $e->getMessage());
                Log::error("[PHOTO MULTI-UPLOAD] Error file: " . $e->getFile() . ':' . $e->getLine());
                // Continue with other photos, don't fail the whole process
            }
        }

        Log::info('[PHOTO MULTI-UPLOAD] Completed. Total photos: ' . $asset->photos()->count());
    }

    /**
     * Process photo upload for legacy single photo.
     * WITH DEBUGGING
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param Asset|null $asset
     * @return array
     */
    protected function processPhotoUpload($file, $asset = null): array
    {
        Log::info('[PHOTO SINGLE UPLOAD] Starting...');
        Log::info('[PHOTO SINGLE UPLOAD] Original name: ' . $file->getClientOriginalName());
        Log::info('[PHOTO SINGLE UPLOAD] Size: ' . $file->getSize() . ' bytes');
        Log::info('[PHOTO SINGLE UPLOAD] MIME type: ' . $file->getMimeType());
        Log::info('[PHOTO SINGLE UPLOAD] Asset UUID: ' . ($asset?->uuid ?? 'null'));

        $uuid = \Str::uuid()->toString();
        $extension = $file->getClientOriginalExtension();
        $filename = $uuid . '.' . $extension;
        $path = 'assets/' . $filename;

        Log::info('[PHOTO SINGLE UPLOAD] Generated filename: ' . $filename);
        Log::info('[PHOTO SINGLE UPLOAD] Storage path: ' . $path);

        // Store the main image
        $result = \Storage::disk('public')->putFileAs('assets', $file, $filename);
        Log::info('[PHOTO SINGLE UPLOAD] Storage putFileAs result: ' . ($result ? 'SUCCESS' : 'FAILED'));

        // Verify file exists
        $exists = \Storage::disk('public')->exists($path);
        Log::info('[PHOTO SINGLE UPLOAD] File exists after save: ' . ($exists ? 'YES' : 'NO'));

        $metadata = [
            'image_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];

        Log::info('[PHOTO SINGLE UPLOAD] Metadata: ' . json_encode($metadata));

        // Create thumbnail as copy (no resize)
        try {
            $thumbnailFilename = 'thumb_' . $filename;
            $thumbnailPath = 'assets/' . $thumbnailFilename;
            $thumbResult = \Storage::disk('public')->putFileAs('assets', $file, $thumbnailFilename);
            Log::info('[PHOTO SINGLE UPLOAD] Thumbnail save result: ' . ($thumbResult ? 'SUCCESS' : 'FAILED'));
            $metadata['thumbnail_path'] = $thumbnailPath;
        } catch (\Exception $e) {
            Log::warning('[PHOTO SINGLE UPLOAD] Failed to create thumbnail: ' . $e->getMessage());
        }

        Log::info('[PHOTO SINGLE UPLOAD] Completed successfully');

        return $metadata;
    }

    /**
     * Process warranty card upload (optional).
     * WITH DEBUGGING
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param Asset $asset
     */
    protected function processWarrantyCardUpload($file, Asset $asset): void
    {
        Log::info('[WARRANTY CARD UPLOAD] Starting...');
        Log::info('[WARRANTY CARD UPLOAD] Original name: ' . $file->getClientOriginalName());
        Log::info('[WARRANTY CARD UPLOAD] Size: ' . $file->getSize() . ' bytes');
        Log::info('[WARRANTY CARD UPLOAD] MIME type: ' . $file->getMimeType());
        Log::info('[WARRANTY CARD UPLOAD] Asset UUID: ' . $asset->uuid);

        try {
            $uuid = \Str::uuid()->toString();
            $extension = $file->getClientOriginalExtension();
            $filename = 'warranty_' . $uuid . '.' . $extension;
            $path = 'assets/warranty/' . $filename;

            Log::info('[WARRANTY CARD UPLOAD] Generated filename: ' . $filename);
            Log::info('[WARRANTY CARD UPLOAD] Storage path: ' . $path);

            // Create warranty directory if not exists
            if (!\Storage::disk('public')->exists('assets/warranty')) {
                Log::info('[WARRANTY CARD UPLOAD] Creating warranty directory...');
                \Storage::disk('public')->makeDirectory('assets/warranty');
                Log::info('[WARRANTY CARD UPLOAD] Directory created');
            } else {
                Log::info('[WARRANTY CARD UPLOAD] Directory already exists');
            }

            // Store the image
            $result = \Storage::disk('public')->putFileAs('assets/warranty', $file, $filename);
            Log::info('[WARRANTY CARD UPLOAD] Storage putFileAs result: ' . ($result ? 'SUCCESS' : 'FAILED'));

            // Verify file exists
            $exists = \Storage::disk('public')->exists($path);
            Log::info('[WARRANTY CARD UPLOAD] File exists after save: ' . ($exists ? 'YES' : 'NO'));

            // Update asset with warranty card info
            $asset->update([
                'warranty_card_path' => $path,
                'warranty_card_original_name' => $file->getClientOriginalName(),
            ]);

            Log::info("[WARRANTY CARD UPLOAD] Success - asset_uuid:{$asset->uuid}, filename:{$file->getClientOriginalName()}, path:{$path}");

        } catch (\Exception $e) {
            Log::error("[WARRANTY CARD UPLOAD] FAILED - asset_uuid:{$asset->uuid}, filename:{$file->getClientOriginalName()}");
            Log::error("[WARRANTY CARD UPLOAD] Error message: " . $e->getMessage());
            Log::error("[WARRANTY CARD UPLOAD] Error file: " . $e->getFile() . ':' . $e->getLine());
            // Don't throw - warranty card is optional
        }
    }

    /**
     * Delete a specific photo from an asset (JSON API).
     */
    public function deletePhotoJson(Request $request, Asset $asset): JsonResponse
    {
        $photoId = $request->input('photo_id');

        if (!$photoId) {
            return response()->json(['success' => false, 'message' => 'Photo ID is required.']);
        }

        $photo = $asset->photos()->find($photoId);

        if (!$photo) {
            return response()->json(['success' => false, 'message' => 'Photo not found.']);
        }

        // Delete file from storage
        $photo->deleteFiles();
        $photo->delete();

        // If this was the cover photo, set another as cover
        if ($photo->is_cover) {
            $firstPhoto = $asset->photos()->first();
            if ($firstPhoto) {
                $firstPhoto->update(['is_cover' => true]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Photo deleted successfully.'
        ]);
    }

    /**
     * Delete warranty card from an asset.
     */
    public function deleteWarrantyCard(Asset $asset): JsonResponse
    {
        if ($asset->warranty_card_path) {
            // Delete file from storage
            if (\Storage::disk('public')->exists($asset->warranty_card_path)) {
                \Storage::disk('public')->delete($asset->warranty_card_path);
            }

            // Update asset
            $asset->update([
                'warranty_card_path' => null,
                'warranty_card_original_name' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Warranty card deleted successfully.'
        ]);
    }

    /**
     * Log photo action for audit trail.
     *
     * @param string $action uploaded|updated|deleted
     * @param string $assetUuid
     * @param string|null $filename
     */
    protected function logPhotoAction(string $action, string $assetUuid, ?string $filename = null): void
    {
        $user = $this->user();

        Log::info("[Asset Photo {$action}]", [
            'asset_uuid' => $assetUuid,
            'filename' => $filename,
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'ip' => request()->ip(),
            'action' => $action,
            'timestamp' => now()->toIso8601String(),
        ]);

        // Also log to activity
        $asset = Asset::where('uuid', $assetUuid)->first();
        if ($asset) {
            $asset->logActivity("Photo {$action}", 'asset_photo', $filename);
        }
    }

    /**
     * Display the specified asset.
     */
    public function show(Asset $asset): View
    {
        $asset->load('category', 'creator', 'allocations.user', 'maintenanceRecords', 'activities');
        return view('crm.assets.show', compact('asset'));
    }

    /**
     * Delete asset photo.
     */
    public function deletePhoto(Asset $asset): RedirectResponse
    {
        if (!$asset->image_path) {
            return redirect()->back()->with('error', 'Tidak ada foto untuk dihapus.');
        }

        // Store info before deletion for logging
        $filename = $asset->original_filename;
        $uuid = $asset->uuid;

        // Delete files
        $this->deletePhotoFiles($asset);

        // Update database
        $asset->update([
            'image_path' => null,
            'thumbnail_path' => null,
            'original_filename' => null,
            'file_size' => null,
            'mime_type' => null,
        ]);

        // Log deletion
        $this->logPhotoAction('deleted', $uuid, $filename);

        return redirect()->back()->with('success', 'Foto berhasil dihapus.');
    }

    /**
     * Show the form for editing the asset.
     */
    public function edit(Asset $asset): View
    {
        $categories = AssetCategory::where('tenant_id', $this->tenantId())->orderBy('name')->get();
        return view('crm.assets.edit', compact('asset', 'categories'));
    }

    /**
     * Update the specified asset.
     */
    public function update(AssetRequest $request, Asset $asset): RedirectResponse
    {
        $data = $request->validated();
        $data['updated_by'] = $this->user()->id;

        // Calculate maintenance end date if maintenance duration is set
        if (!empty($data['maintenance_start_date']) && !empty($data['maintenance_duration_days'])) {
            $startDate = \Carbon\Carbon::parse($data['maintenance_start_date']);
            $duration = (int) $data['maintenance_duration_days'];
            $data['maintenance_duration_days'] = $duration;
            $data['maintenance_end_date'] = $startDate->addDays($duration)->toDateString();
        }

        // Handle multi-photo uploads (no hardcoded limit)
        if ($request->hasFile('photos')) {
            $this->processMultiplePhotoUploads($request->file('photos'), $asset);
        }

        // Handle single photo (legacy support)
        if ($request->hasFile('photo')) {
            // Delete old photos first
            foreach ($asset->photos as $photo) {
                $photo->deleteFiles();
                $photo->delete();
            }

            // Process new single photo
            $photoData = $this->processPhotoUpload($request->file('photo'), $asset);
            $data = array_merge($data, $photoData);
        }

        // Handle legacy photo removal
        if ($request->boolean('remove_photo') && $asset->image_path) {
            $this->deletePhotoFiles($asset);
            $data['image_path'] = null;
            $data['thumbnail_path'] = null;
            $data['original_filename'] = null;
            $data['file_size'] = null;
            $data['mime_type'] = null;
        }

        // Handle multiple photo removals
        if ($request->has('remove_photos')) {
            $removePhotos = json_decode($request->input('remove_photos'), true) ?? [];
            foreach ($removePhotos as $photoId) {
                $photo = $asset->photos()->find($photoId);
                if ($photo) {
                    $wasCover = $photo->is_cover;
                    $photo->deleteFiles();
                    $photo->delete();

                    // If this was the cover photo, set another as cover
                    if ($wasCover) {
                        $firstPhoto = $asset->photos()->first();
                        if ($firstPhoto) {
                            $firstPhoto->update(['is_cover' => true]);
                        }
                    }
                }
            }
        }

        // Handle warranty card upload (optional)
        if ($request->hasFile('warranty_card')) {
            // Delete old warranty card first
            if ($asset->warranty_card_path) {
                if (\Storage::disk('public')->exists($asset->warranty_card_path)) {
                    \Storage::disk('public')->delete($asset->warranty_card_path);
                }
            }
            $this->processWarrantyCardUpload($request->file('warranty_card'), $asset);
        }

        // Handle warranty card removal
        if ($request->boolean('remove_warranty_card') && $asset->warranty_card_path) {
            if (\Storage::disk('public')->exists($asset->warranty_card_path)) {
                \Storage::disk('public')->delete($asset->warranty_card_path);
            }
            $data['warranty_card_path'] = null;
            $data['warranty_card_original_name'] = null;
        }

        $asset->update($data);

        return redirect()
            ->route('assets.show', $asset)
            ->with('success', 'Asset berhasil diperbarui!');
    }

    /**
     * Delete photo files from storage.
     *
     * @param Asset $asset
     */
    protected function deletePhotoFiles(Asset $asset): void
    {
        // Delete main image
        if ($asset->image_path && \Storage::disk('public')->exists($asset->image_path)) {
            \Storage::disk('public')->delete($asset->image_path);
        }

        // Delete thumbnail
        if ($asset->thumbnail_path && \Storage::disk('public')->exists($asset->thumbnail_path)) {
            \Storage::disk('public')->delete($asset->thumbnail_path);
        }
    }

    /**
     * Remove the specified asset.
     */
    public function destroy(Asset $asset): RedirectResponse
    {
        if ($asset->status !== Asset::STATUS_AVAILABLE && $asset->status !== Asset::STATUS_RETIRED) {
            return redirect()->back()->with('error', 'Asset harus tersedia atau retired untuk dihapus.');
        }

        // Delete photo files
        $this->deletePhotoFiles($asset);

        $asset->delete();

        return redirect()
            ->route('assets.index')
            ->with('success', 'Asset berhasil dihapus.');
    }

    /**
     * Assign asset to user.
     */
    public function assign(Request $request, Asset $asset): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $user = \App\Modules\System\Models\User::findOrFail($request->user_id);
        $asset->assignTo($user, $request->notes);

        return $this->success($asset, 'Asset assigned successfully');
    }

    /**
     * Unassign asset from user.
     */
    public function unassign(Asset $asset): JsonResponse
    {
        $asset->unassign();

        return $this->success($asset, 'Asset unassigned successfully');
    }

    /**
     * Mark asset as under maintenance.
     */
    public function markAsMaintenance(Asset $asset): JsonResponse
    {
        $asset->markAsMaintenance();

        return $this->success($asset, 'Asset marked as under maintenance');
    }

    /**
     * Mark asset as available.
     */
    public function markAsAvailable(Asset $asset): JsonResponse
    {
        $asset->markAsAvailable();

        return $this->success($asset, 'Asset marked as available');
    }

    /**
     * Mark asset as lost.
     * @deprecated This feature has been removed from the UI
     */
    public function markAsLost(Asset $asset): JsonResponse
    {
        return $this->error('Fitur "Tandai Hilang" telah dihapus dari sistem.', 410);
    }

    /**
     * Mark asset as stolen.
     * @deprecated This feature has been removed from the UI
     */
    public function markAsStolen(Asset $asset): JsonResponse
    {
        return $this->error('Fitur "Tandai Hilang" telah dihapus dari sistem.', 410);
    }

    /**
     * Update asset status via quick action.
     * NOTE: For activities (allocation, reservation, maintenance), use the Activity API.
     * This method is deprecated for status changes - kept for backward compatibility only.
     *
     * @deprecated Use activity endpoints instead: /assets/{asset}/activities/allocation, reservation, maintenance
     */
    public function updateStatus(Request $request, Asset $asset): JsonResponse
    {
        // DEPRECATED: These endpoints are being phased out
        // Lost and Retired features are removed from UI
        // Only allow 'available' and 'maintenance' for backward compatibility
        $request->validate([
            'status' => 'required|in:available,maintenance',
        ]);

        $status = $request->status;

        // Note: We no longer change base status for maintenance
        // Maintenance now adds a status rather than replacing

        // If changing from maintenance to available, just clear the reference
        if ($status === 'available' && $asset->current_maintenance_id) {
            // Complete the maintenance activity
            $asset->completeMaintenance();
        }

        // Return redirect URL if specified
        if ($request->redirect_to === 'show') {
            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => $asset->fresh(),
                'redirect' => route('assets.show', $asset)
            ]);
        }

        return $this->success($asset->fresh(), 'Asset status updated successfully');
    }
}
