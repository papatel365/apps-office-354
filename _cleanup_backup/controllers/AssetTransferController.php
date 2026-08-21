<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetTransfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetTransferController extends Controller
{
    /**
     * Store a newly created transfer.
     */
    public function store(Request $request, Asset $asset): JsonResponse
    {
        $request->validate([
            'transfer_type' => 'required|in:location,department,user',
            'to_location' => 'nullable|string|max:255',
            'to_department' => 'nullable|string|max:100',
            'to_user_id' => 'nullable|exists:users,id',
            'transfer_date' => 'required|date',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'description' => 'nullable|string',
            'photos.*' => 'nullable|image|max:2048', // Max 2MB per photo
        ]);

        $photoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $filename = 'transfers/' . $asset->asset_number . '_' . time() . '_' . $photo->getClientOriginalName();
                $path = $photo->storeAs('transfers', basename($filename), 'public');
                $photoPaths[] = $path;
            }
        }

        $transfer = AssetTransfer::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $this->tenantId(),
            'asset_id' => $asset->id,
            'transfer_number' => AssetTransfer::generateNumber(),
            'transfer_type' => $request->transfer_type,
            'from_location' => $asset->location,
            'to_location' => $request->to_location,
            'from_department' => $request->from_department,
            'to_department' => $request->to_department,
            'from_user_id' => $request->from_user_id,
            'to_user_id' => $request->to_user_id,
            'transfer_date' => $request->transfer_date,
            'reason' => $request->reason,
            'notes' => $request->notes,
            'description' => $request->description,
            'photos' => $photoPaths ?: null,
            'initiated_by' => $this->user()->id,
            'status' => AssetTransfer::STATUS_PENDING,
        ]);

        return $this->success($transfer, 'Transfer created successfully', 201);
    }

    /**
     * Approve transfer.
     */
    public function approve(Asset $asset, AssetTransfer $transfer): JsonResponse
    {
        $transfer->approve();

        return $this->success($transfer, 'Transfer approved');
    }

    /**
     * Start transfer in transit.
     */
    public function startTransit(Asset $asset, AssetTransfer $transfer): JsonResponse
    {
        $transfer->startTransit();

        return $this->success($transfer, 'Transfer in transit');
    }

    /**
     * Complete transfer.
     */
    public function complete(Asset $asset, AssetTransfer $transfer): JsonResponse
    {
        $transfer->complete();

        return $this->success($transfer, 'Transfer completed');
    }

    /**
     * Cancel transfer.
     */
    public function destroy(Asset $asset, AssetTransfer $transfer): JsonResponse
    {
        // Delete photos from storage
        if ($transfer->photos && is_array($transfer->photos)) {
            foreach ($transfer->photos as $photo) {
                if (Storage::disk('public')->exists($photo)) {
                    Storage::disk('public')->delete($photo);
                }
            }
        }

        $transfer->cancel();

        return $this->success(null, 'Transfer cancelled');
    }

    /**
     * Add photos to an existing transfer.
     */
    public function addPhotos(Request $request, Asset $asset, AssetTransfer $transfer): JsonResponse
    {
        $request->validate([
            'photos.*' => 'required|image|max:2048',
        ]);

        $currentPhotos = $transfer->photos ?? [];
        $newPhotos = [];

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $filename = 'transfers/' . $asset->asset_number . '_' . time() . '_' . $photo->getClientOriginalName();
                $path = $photo->storeAs('transfers', basename($filename), 'public');
                $newPhotos[] = $path;
            }
        }

        $allPhotos = array_merge($currentPhotos, $newPhotos);
        $transfer->update(['photos' => $allPhotos]);

        return $this->success($transfer, 'Photos added successfully');
    }

    /**
     * Remove a photo from a transfer.
     */
    public function removePhoto(Request $request, Asset $asset, AssetTransfer $transfer): JsonResponse
    {
        $request->validate([
            'photo_path' => 'required|string',
        ]);

        $photos = $transfer->photos ?? [];

        if (($key = array_search($request->photo_path, $photos)) !== false) {
            // Delete file from storage
            if (Storage::disk('public')->exists($request->photo_path)) {
                Storage::disk('public')->delete($request->photo_path);
            }

            unset($photos[$key]);
            $transfer->update(['photos' => array_values($photos)]);
        }

        return $this->success($transfer, 'Photo removed successfully');
    }
}
