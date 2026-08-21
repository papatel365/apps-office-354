<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\WorkUpdate;
use App\Models\TaskActivity;
use App\Models\TaskPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WorkUpdateController extends Controller
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
     * Check if user can create work update for task.
     * Assignees and Director/Admin can create work updates.
     */
    protected function canCreateWorkUpdate(Task $task): bool
    {
        $user = $this->user();

        // Director/Admin can always create work updates
        if ($this->user()->isDirectorOrAdmin()) {
            return true;
        }

        // Check if user is an assignee
        return $task->assignees()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Store a new work update.
     */
    public function store(Request $request, Task $task): JsonResponse
    {
        if (!$this->canCreateWorkUpdate($task)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk membuat update pekerjaan.',
            ], 403);
        }

        $request->validate([
            'completed_work' => 'nullable|string|max:5000',
            'in_progress_work' => 'nullable|string|max:5000',
            'notes' => 'nullable|string|max:2000',
            'progress' => 'nullable|integer|min:0|max:100',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240', // Max 10MB per file
        ], [
            'completed_work.max' => 'Deskripsi pekerjaan yang sudah selesai maksimal 5000 karakter.',
            'in_progress_work.max' => 'Deskripsi pekerjaan yang sedang diproses maksimal 5000 karakter.',
            'notes.max' => 'Catatan maksimal 2000 karakter.',
            'photos.*.max' => 'File foto maksimal 10MB.',
            'photos.*.image' => 'File harus berupa gambar.',
            'photos.*.mimes' => 'Format foto harus jpeg, png, jpg, gif, atau webp.',
        ]);

        // Check require_photo constraint
        if ($task->require_photo) {
            $existingPhotos = $task->photos()->count();
            $newPhotos = $request->hasFile('photos') ? count($request->file('photos')) : 0;
            $totalPhotos = $existingPhotos + $newPhotos;

            if ($totalPhotos === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task ini memerlukan bukti foto. Silakan upload minimal 1 foto.',
                ], 422);
            }
        }

        $uploadedPhotos = [];
        $workUpdateId = null;

        // Create work update
        $workUpdate = WorkUpdate::create([
            'uuid' => Str::uuid(),
            'tenant_id' => $task->tenant_id,
            'task_id' => $task->id,
            'user_id' => $this->user()->id,
            'completed_work' => $request->completed_work,
            'in_progress_work' => $request->in_progress_work,
            'notes' => $request->notes,
            'progress' => $request->progress ?? $task->progress,
            'progress_manual' => $request->has('progress'),
            'photo_count' => $request->hasFile('photos') ? count($request->file('photos')) : 0,
        ]);

        $workUpdateId = $workUpdate->id;

        // Process photo uploads
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                $photo = $this->processPhotoUpload($task, $file, $workUpdate);
                $uploadedPhotos[] = $photo;
            }

            // Update photo count in work update
            $workUpdate->update(['photo_count' => count($uploadedPhotos)]);
        }

        // Update task progress if provided
        if ($request->has('progress') && is_numeric($request->progress)) {
            $oldProgress = $task->progress;
            $newProgress = (int) $request->progress;

            if ($newProgress !== $oldProgress) {
                $task->update(['progress' => $newProgress]);

                // Log activity for progress change
                TaskActivity::logFieldChanged(
                    $task,
                    $this->user(),
                    'progress',
                    $oldProgress,
                    $newProgress
                );
            }
        }

        // Log activity for work update creation
        $updateSummary = [];
        if ($request->completed_work) {
            $updateSummary[] = 'pekerjaan selesai';
        }
        if ($request->in_progress_work) {
            $updateSummary[] = 'pekerjaan sedang diproses';
        }
        if ($request->notes) {
            $updateSummary[] = 'catatan';
        }
        if (count($uploadedPhotos) > 0) {
            $updateSummary[] = count($uploadedPhotos) . ' foto';
        }

        TaskActivity::logFieldChanged(
            $task,
            $this->user(),
            'work_update',
            null,
            implode(', ', $updateSummary) ?: 'update progress'
        );

        return response()->json([
            'success' => true,
            'message' => 'Update pekerjaan berhasil disimpan.',
            'work_update' => $workUpdate->details,
            'photos' => $uploadedPhotos,
        ]);
    }

    /**
     * Get all work updates for a task.
     */
    public function index(Task $task): JsonResponse
    {
        $workUpdates = $task->workUpdates()
            ->with('user')
            ->latestFirst()
            ->get()
            ->map(fn($update) => $update->details);

        return response()->json([
            'success' => true,
            'work_updates' => $workUpdates,
            'count' => $workUpdates->count(),
        ]);
    }

    /**
     * Delete a work update.
     */
    public function destroy(Request $request, Task $task, WorkUpdate $workUpdate): JsonResponse
    {
        if ($workUpdate->task_id !== $task->id) {
            return response()->json([
                'success' => false,
                'message' => 'Work update tidak ditemukan.',
            ], 404);
        }

        // Check permission: only the creator or Director/Admin can delete
        $user = $this->user();
        if ($workUpdate->user_id !== $user->id && !$this->user()->isDirectorOrAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk menghapus update ini.',
            ], 403);
        }

        // Soft delete photos associated with this work update
        $workUpdate->photos()->delete();

        // Delete the work update
        $workUpdate->delete();

        // Log activity
        TaskActivity::logFieldChanged(
            $task,
            $this->user(),
            'work_update_deleted',
            $workUpdate->id,
            null
        );

        return response()->json([
            'success' => true,
            'message' => 'Update pekerjaan berhasil dihapus.',
        ]);
    }

    /**
     * Process photo upload for work update.
     */
    protected function processPhotoUpload(Task $task, $file, WorkUpdate $workUpdate): array
    {
        $uuid = Str::uuid();
        $extension = $file->getClientOriginalExtension();
        $filename = "{$uuid}.{$extension}";
        $thumbnailFilename = "thumb_{$uuid}.{$extension}";

        $path = "task-evidence/{$task->id}/work-update/" . now()->format('Y/m/d');
        $fullPath = "{$path}/{$filename}";
        $thumbnailPath = "{$path}/{$thumbnailFilename}";

        // Store original and thumbnail ()
        try {
            Storage::disk('public')->putFileAs($path, $file, $filename);
            // Store thumbnail as copy of original
            Storage::disk('public')->putFileAs($path, $file, $thumbnailFilename);
            Log::info('[WorkUpdate] Stored photo: ' . $fullPath);
            Log::info('[WorkUpdate] Stored thumbnail: ' . $thumbnailPath);
        } catch (\Exception $e) {
            Log::warning('[WorkUpdate] Storage failed: ' . $e->getMessage());
        }

        // Get next sort order
        $sortOrder = $task->photos()->max('sort_order') + 1;

        // Create database record with work_update_id
        $photo = TaskPhoto::create([
            'uuid' => $uuid,
            'tenant_id' => $task->tenant_id,
            'task_id' => $task->id,
            'work_update_id' => $workUpdate->id,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $fullPath,
            'thumbnail_path' => Storage::disk('public')->exists($thumbnailPath) ? $thumbnailPath : null,
            'disk' => 'public',
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
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
            'formatted_date' => $photo->created_at->format('d M Y, H:i'),
        ];
    }
}
