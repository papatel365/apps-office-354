<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskPhoto;
use App\Models\TaskActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TaskPhotoController extends Controller
{
    /**
     * Check if current user is Director or Admin.
     * Uses both Spatie roles AND company_role field for compatibility.
     */
    protected function isDirectorOrAdmin(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        // Check company_role field (used in CRM)
        $companyRole = $user->company_role ?? null;
        $userType = $user->user_type ?? null;

        // Director/Admin if company_role or user_type is director/admin
        if (in_array($companyRole, ['director', 'admin']) || in_array($userType, ['director', 'admin'])) {
            return true;
        }

        // Also check Spatie roles
        return $user->hasRole(['director', 'admin']);
    }

    /**
     * Check if user can upload photos to task.
     */
    protected function canUpload(Task $task): bool
    {
        // Anyone with task work access can upload photos
        return $task->canEditWork($this->user());
    }

    /**
     * Check if user can delete photos from task.
     */
    protected function canDelete(Task $task, TaskPhoto $photo): bool
    {
        // Director/Admin can delete any photo
        if ($this->user()->isDirectorOrAdmin()) {
            return true;
        }

        // Uploader can delete their own photo
        return $photo->uploaded_by === $this->user()->id;
    }

    /**
     * Check if user can edit photo metadata.
     */
    protected function canEdit(Task $task, TaskPhoto $photo): bool
    {
        // Director/Admin can edit any photo
        if ($this->user()->isDirectorOrAdmin()) {
            return true;
        }

        // Uploader can edit their own photo
        return $photo->uploaded_by === $this->user()->id;
    }

    /**
     * Upload photos to task.
     * Multiple file upload with drag & drop support and metadata.
     */
    public function upload(Request $request, Task $task): JsonResponse
    {
        if (!$this->canUpload($task)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengunggah foto.',
            ], 403);
        }

        $request->validate([
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240', // Max 10MB per file
            'caption' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'work_stage' => 'nullable|string|in:persiapan,sebelum,proses,sesudah,testing,serah_terima',
        ]);

        $uploaded = [];

        if ($request->hasFile('photos')) {
            $caption = $request->caption;
            $description = $request->description;
            $workStage = $request->work_stage;

            foreach ($request->file('photos') as $index => $file) {
                // Use individual caption if multiple files, or shared caption
                $individualCaption = $request->has('caption')
                    ? $caption
                    : null;

                $photo = $this->processUpload(
                    $task,
                    $file,
                    $individualCaption,
                    $description,
                    $workStage
                );
                $uploaded[] = $photo;
            }
        }

        // Log activity
        if (count($uploaded) > 0) {
            // Get captions for logging
            $captions = array_filter(array_column($uploaded, 'caption'));

            TaskActivity::logPhotoUploaded(
                $task,
                $this->user(),
                count($uploaded),
                $captions ?: null,
                $request->work_stage
            );
        }

        return response()->json([
            'success' => true,
            'message' => count($uploaded) . ' foto berhasil diunggah.',
            'photos' => $uploaded,
        ]);
    }

    /**
     * Process single photo upload with metadata.
     */
    protected function processUpload(
        Task $task,
        $file,
        ?string $caption = null,
        ?string $description = null,
        ?string $workStage = null
    ): array {
        $uuid = Str::uuid();
        $extension = $file->getClientOriginalExtension();
        $filename = "{$uuid}.{$extension}";
        $thumbnailFilename = "thumb_{$uuid}.{$extension}";

        $path = "task-evidence/{$task->id}/" . now()->format('Y/m/d');
        $fullPath = "{$path}/{$filename}";
        $thumbnailPath = "{$path}/{$thumbnailFilename}";

        // Store original
        Storage::disk('public')->putFileAs($path, $file, $filename);
        Storage::disk('public')->putFileAs($path, $file, $thumbnailFilename);
        Log::info('[TaskPhoto] Stored photo: ' . $fullPath);
        Log::info('[TaskPhoto] Stored thumbnail: ' . $thumbnailPath);

        // Get next sort order
        $sortOrder = $task->photos()->max('sort_order') + 1;

        // Create database record
        $photo = TaskPhoto::create([
            'uuid' => $uuid,
            'tenant_id' => $task->tenant_id,
            'task_id' => $task->id,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $fullPath,
            'thumbnail_path' => Storage::disk('public')->exists($thumbnailPath) ? $thumbnailPath : null,
            'disk' => 'public',
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'caption' => $caption,
            'description' => $description,
            'work_stage' => $workStage,
            'is_evidence' => true,
            'sort_order' => $sortOrder,
            'uploaded_by' => $this->user()->id,
        ]);

        return [
            'id' => $photo->id,
            'uuid' => $photo->uuid,
            'url' => $photo->url,
            'thumbnail_url' => $photo->thumbnail_url,
            'filename' => $photo->original_filename,
            'caption' => $photo->caption,
            'description' => $photo->description,
            'work_stage' => $photo->work_stage,
            'work_stage_label' => $photo->formatted_work_stage,
            'uploader_name' => $this->user()->name,
            'formatted_date' => $photo->created_at->format('d M Y, H:i'),
        ];
    }

    /**
     * Update photo metadata (caption, description, work_stage).
     */
    public function update(Request $request, Task $task, TaskPhoto $photo): JsonResponse
    {
        if ($photo->task_id !== $task->id) {
            return response()->json([
                'success' => false,
                'message' => 'Foto tidak ditemukan untuk task ini.',
            ], 404);
        }

        if (!$this->canEdit($task, $photo)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk mengedit foto ini.',
            ], 403);
        }

        $request->validate([
            'caption' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'work_stage' => 'nullable|string|in:persiapan,sebelum,proses,sesudah,testing,serah_terima',
        ]);

        $oldCaption = $photo->caption;

        $photo->updateMetadata([
            'caption' => $request->caption,
            'description' => $request->description,
            'work_stage' => $request->work_stage,
        ]);

        // Log activity
        TaskActivity::logPhotoUpdated($task, $this->user(), $photo, [
            'old_caption' => $oldCaption,
            'new_caption' => $request->caption,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Metadata foto berhasil diperbarui.',
            'photo' => $photo->details,
        ]);
    }

    /**
     * Delete photo from task.
     * Uses soft delete to preserve history.
     */
    public function destroy(Request $request, Task $task, TaskPhoto $photo): JsonResponse
    {
        if ($photo->task_id !== $task->id) {
            return response()->json([
                'success' => false,
                'message' => 'Foto tidak ditemukan untuk task ini.',
            ], 404);
        }

        if (!$this->canDelete($task, $photo)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk menghapus foto ini.',
            ], 403);
        }

        // Store info before deletion
        $photoName = $photo->display_name;
        $photoCaption = $photo->caption;

        // Soft delete - file will be deleted on force delete
        $photo->delete();

        // Log activity (after soft delete so we can still access the data)
        TaskActivity::logPhotoDeleted($task, $this->user(), $photoName, $photoCaption);

        return response()->json([
            'success' => true,
            'message' => 'Foto berhasil dihapus.',
        ]);
    }

    /**
     * Restore a soft-deleted photo.
     */
    public function restore(Request $request, Task $task, TaskPhoto $photo): JsonResponse
    {
        if ($photo->task_id !== $task->id) {
            return response()->json([
                'success' => false,
                'message' => 'Foto tidak ditemukan untuk task ini.',
            ], 404);
        }

        if (!$this->user()->isDirectorOrAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Director/Admin yang dapat memulihkan foto.',
            ], 403);
        }

        if (!$photo->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'Foto tidak dihapus.',
            ], 400);
        }

        $photo->restore();

        TaskActivity::logPhotoRestored($task, $this->user(), $photo->display_name);

        return response()->json([
            'success' => true,
            'message' => 'Foto berhasil dipulihkan.',
        ]);
    }

    /**
     * Reorder photos.
     */
    public function reorder(Request $request, Task $task): JsonResponse
    {
        if (!$this->user()->isDirectorOrAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Director/Admin yang dapat mengurutkan foto.',
            ], 403);
        }

        $request->validate([
            'photos' => 'required|array',
            'photos.*' => 'exists:task_photos,id',
        ]);

        foreach ($request->photos as $index => $photoId) {
            TaskPhoto::where('id', $photoId)->update(['sort_order' => $index]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Foto berhasil diurutkan.',
        ]);
    }

    /**
     * Get photo download URL.
     */
    public function download(Task $task, TaskPhoto $photo)
    {
        if ($photo->task_id !== $task->id) {
            abort(404);
        }

        if (!Storage::disk($photo->disk)->exists($photo->file_path)) {
            abort(404);
        }

        return Storage::disk($photo->disk)->download($photo->file_path, $photo->original_filename);
    }

    /**
     * Get all photos for a task (API).
     */
    public function index(Task $task): JsonResponse
    {
        $photos = $task->photos()
            ->with('uploader')
            ->ordered()
            ->get()
            ->map(fn($photo) => $photo->details);

        return response()->json([
            'success' => true,
            'photos' => $photos,
            'count' => $photos->count(),
        ]);
    }
}
