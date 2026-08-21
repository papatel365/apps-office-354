<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AssetPhotoController extends Controller
{
    /**
     * Upload photos for an asset.
     * Supports multiple file uploads.
     */
    public function upload(Request $request, Asset $asset): JsonResponse
    {
        $request->validate([
            'photos' => 'required|array|min:1|max:10',
            'photos.*' => 'image|mimes:jpg,jpeg,png,gif,webp|max:10240', // 10MB max per file
        ]);

        $uploadedPhotos = [];

        foreach ($request->file('photos') as $index => $file) {
            $photo = $this->processUpload($file, $asset, $index === 0);
            $uploadedPhotos[] = $photo;
        }

        return $this->success([
            'photos' => $uploadedPhotos,
            'count' => count($uploadedPhotos),
        ], 'Photos uploaded successfully', 201);
    }

    /**
     * Process a single photo upload.
     */
    protected function processUpload($file, Asset $asset, bool $isFirst = false): AssetPhoto
    {
        $uuid = \Str::uuid()->toString();
        $extension = $file->getClientOriginalExtension();
        $filename = $uuid . '.' . $extension;
        $path = 'assets/' . $asset->uuid . '/photos/';

        // Store the image
        Storage::disk('public')->putFileAs($path, $file, $filename);

        // Generate thumbnail
        $thumbnailPath = null;
        try {
            $thumbnailFilename = 'thumb_' . $filename;
            $thumbnailFullPath = $path . $thumbnailFilename;

            // For simplicity, copy the original as thumbnail
            // In production, use resize to resize
            Storage::disk('public')->putFileAs($path, $file, $thumbnailFilename);
            $thumbnailPath = $path . $thumbnailFilename;
        } catch (\Exception $e) {
            Log::warning('Failed to create thumbnail: ' . $e->getMessage());
        }

        // Check if this should be cover (first photo or if no cover exists)
        $isCover = $isFirst && !$asset->has_photos;

        // Create photo record
        $photo = AssetPhoto::create([
            'uuid' => $uuid,
            'asset_id' => $asset->id,
            'file_path' => $path . $filename,
            'thumbnail_path' => $thumbnailPath,
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'is_cover' => $isCover,
            'sort_order' => AssetPhoto::getNextSortOrder($asset->id),
            'uploaded_by' => auth()->id(),
        ]);

        // Log activity
        Log::info('[Asset Photo Upload]', [
            'asset_id' => $asset->id,
            'photo_id' => $photo->id,
            'filename' => $photo->original_filename,
            'user_id' => auth()->id(),
        ]);

        return $photo;
    }

    /**
     * Delete a photo.
     */
    public function destroy(Asset $asset, AssetPhoto $photo): JsonResponse
    {
        // Verify photo belongs to this asset
        if ($photo->asset_id !== $asset->id) {
            return $this->error('Photo not found for this asset', 404);
        }

        $photoId = $photo->id;
        $wasCover = $photo->is_cover;

        // Delete the photo
        $photo->deleteFiles();
        $photo->delete();

        // If this was the cover, set another photo as cover
        if ($wasCover) {
            $newCover = AssetPhoto::where('asset_id', $asset->id)
                ->orderBy('sort_order')
                ->first();

            if ($newCover) {
                $newCover->update(['is_cover' => true]);
            }
        }

        Log::info('[Asset Photo Delete]', [
            'asset_id' => $asset->id,
            'photo_id' => $photoId,
            'user_id' => auth()->id(),
        ]);

        return $this->success(null, 'Photo deleted successfully');
    }

    /**
     * Set a photo as cover.
     */
    public function setCover(Asset $asset, AssetPhoto $photo): JsonResponse
    {
        // Verify photo belongs to this asset
        if ($photo->asset_id !== $asset->id) {
            return $this->error('Photo not found for this asset', 404);
        }

        // Unset all other covers
        AssetPhoto::where('asset_id', $asset->id)
            ->where('id', '!=', $photo->id)
            ->update(['is_cover' => false]);

        // Set this as cover
        $photo->update(['is_cover' => true]);

        Log::info('[Asset Photo Set Cover]', [
            'asset_id' => $asset->id,
            'photo_id' => $photo->id,
            'user_id' => auth()->id(),
        ]);

        return $this->success($photo, 'Photo set as cover successfully');
    }

    /**
     * Reorder photos.
     */
    public function reorder(Request $request, Asset $asset): JsonResponse
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:asset_photos,id',
        ]);

        foreach ($request->order as $index => $photoId) {
            AssetPhoto::where('id', $photoId)
                ->where('asset_id', $asset->id)
                ->update(['sort_order' => $index]);
        }

        return $this->success(null, 'Photos reordered successfully');
    }

    /**
     * Get all photos for an asset.
     */
    public function index(Asset $asset): JsonResponse
    {
        $photos = $asset->photos()->with('uploader')->ordered()->get();

        return $this->success([
            'photos' => $photos,
            'cover' => $asset->coverPhoto,
        ]);
    }
}
