<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QRScannerController extends Controller
{
    /**
     * Display the QR scanner page.
     */
    public function index(): View
    {
        $testAsset = Asset::first();
        return view('crm.assets.scanner', compact('testAsset'));
    }

    /**
     * Look up QR code data.
     */
    public function lookup(Request $request): JsonResponse
    {
        $identifier = $request->get('qr');

        if (!$identifier) {
            return $this->error('QR identifier is required', 422);
        }

        // Check if it's a direct URL (new format)
        if (filter_var($identifier, FILTER_VALIDATE_URL)) {
            // Extract asset ID from URL like /assets/7/edit
            if (preg_match('/\/assets\/(\d+)\/edit/', $identifier, $matches)) {
                $asset = Asset::find($matches[1]);
                if ($asset) {
                    return response()->json([
                        'type' => 'asset',
                        'data' => $asset,
                        'redirect_url' => $identifier,
                        'is_direct' => true,
                    ]);
                }
            }
            // Extract category ID from URL
            if (preg_match('/\/asset-categories\/(\d+)/', $identifier, $matches)) {
                $category = AssetCategory::find($matches[1]);
                if ($category) {
                    return response()->json([
                        'type' => 'category',
                        'data' => $category,
                        'redirect_url' => $identifier,
                        'is_direct' => true,
                    ]);
                }
            }
        }

        // Check if it's an asset QR identifier (old format)
        if (str_starts_with($identifier, 'AST-')) {
            $asset = Asset::where('qr_identifier', $identifier)
                ->orWhere('uuid', substr($identifier, 4))
                ->first();

            if ($asset) {
                $asset->load('category');
                return response()->json([
                    'type' => 'asset',
                    'data' => $asset,
                    'redirect_url' => route('assets.edit', $asset),
                ]);
            }
        }

        // Check if it's a category QR
        if (str_starts_with($identifier, 'CAT-')) {
            $category = AssetCategory::where('uuid', substr($identifier, 4))->first();

            if ($category) {
                $assets = $category->getAllAssets();
                return response()->json([
                    'type' => 'category',
                    'data' => $category,
                    'assets_count' => $assets->count(),
                    'assets' => $assets->take(20)->map(function ($asset) {
                        return [
                            'id' => $asset->id,
                            'name' => $asset->name,
                            'status' => $asset->status,
                            'status_label' => $asset->status_label,
                            'location' => $asset->location,
                            'qr_identifier' => $asset->qr_identifier,
                            'edit_url' => route('assets.edit', $asset), // Langsung ke edit
                            'url' => route('assets.show', $asset),
                        ];
                    }),
                    'redirect_url' => route('asset-categories.show', $category),
                ]);
            }
        }

        return $this->error('QR code not found', 404);
    }

    /**
     * Update asset status via QR scan.
     * NOTE: In the new multi-status system, this is for backward compatibility only.
     * For activities, use the activity API endpoints instead.
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'qr' => 'required|string',
            'action' => 'required|in:complete-maintenance,complete-allocation,complete-reservation',
        ]);

        $asset = Asset::where('qr_identifier', $request->qr)
            ->orWhere('uuid', substr($request->qr, 4))
            ->first();

        if (!$asset) {
            return $this->error('QR code not found', 404);
        }

        try {
            switch ($request->action) {
                case 'complete-maintenance':
                    if (!$asset->current_maintenance_id) {
                        return $this->error('Asset tidak sedang dalam maintenance', 422);
                    }
                    $asset->completeMaintenance();
                    $message = 'Maintenance selesai. Status Maintenance dihapus.';
                    break;

                case 'complete-allocation':
                    if (!$asset->current_allocation_id) {
                        return $this->error('Asset tidak sedang dialokasikan', 422);
                    }
                    $asset->completeAllocation();
                    $message = 'Alokasi selesai. Status Dialokasikan dihapus.';
                    break;

                case 'complete-reservation':
                    if (!$asset->current_reservation_id) {
                        return $this->error('Asset tidak sedang direservasi', 422);
                    }
                    $asset->completeReservation();
                    $message = 'Reservasi selesai. Status Direservasi dihapus.';
                    break;

                default:
                    return $this->error('Action tidak valid', 422);
            }

            return $this->success([
                'asset' => $asset->fresh(),
                'statuses' => $asset->fresh()->statuses,
                'redirect_url' => route('assets.show', $asset),
            ], $message);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
