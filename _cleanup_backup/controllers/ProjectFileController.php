<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Project;
use App\Services\CRM\ProjectActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectFileController extends Controller
{
    /**
     * Display listing of project files.
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $user = auth()->user();

        $query = $project->attachments()
            ->with('uploader')
            ->orderBy('created_at', 'desc');

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by uploader
        if ($request->filled('uploader_id')) {
            $query->where('uploaded_by', $request->uploader_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by filename
        if ($request->filled('search')) {
            $query->where('file_name', 'like', '%' . $request->search . '%');
        }

        $files = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'files' => $files->items(),
            'pagination' => [
                'current_page' => $files->currentPage(),
                'last_page' => $files->lastPage(),
                'per_page' => $files->perPage(),
                'total' => $files->total(),
            ],
        ]);
    }

    /**
     * Store new file for project.
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|max:51200', // 50MB max
                'name' => 'nullable|string|max:255',
                'category' => 'nullable|string|max:100',
                'description' => 'nullable|string|max:1000',
                'tags' => 'nullable|string|max:500',
            ]);

            $user = auth()->user();
            $file = $request->file('file');

            // Generate unique filename
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '_' . time() . '.' . $extension;

            // Store file
            $path = $file->storeAs(
                'projects/' . $project->id,
                $fileName,
                'public'
            );

            // Create attachment record
            $attachment = $project->attachments()->create([
                'uuid' => Str::uuid(),
                'tenant_id' => $project->tenant_id ?? null,
                'attachable_type' => Project::class,
                'attachable_id' => $project->id,
                'file_name' => $request->name ?? $originalName,
                'file_path' => $path,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'disk' => 'public',
                'uploaded_by' => $user->id,
            ]);

            // Log activity
            ProjectActivityService::fileUploaded($project, $request->name ?? $originalName);

            return response()->json([
                'success' => true,
                'message' => 'File berhasil diunggah',
                'file' => $attachment->load('uploader'),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('ProjectFileController@store error: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunggah file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display specific file.
     */
    public function show(Project $project, Attachment $file): JsonResponse
    {
        // Verify file belongs to this project
        if ($file->attachable_id !== $project->id || $file->attachable_type !== Project::class) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'file' => $file->load('uploader'),
        ]);
    }

    /**
     * Update file metadata.
     */
    public function update(Request $request, Project $project, Attachment $file): JsonResponse
    {
        // Verify file belongs to this project
        if ($file->attachable_id !== $project->id || $file->attachable_type !== Project::class) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan',
            ], 404);
        }

        $request->validate([
            'name' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'tags' => 'nullable|string|max:500',
        ]);

        $file->update([
            'file_name' => $request->name ?? $file->file_name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'File berhasil diperbarui',
            'file' => $file->fresh('uploader'),
        ]);
    }

    /**
     * Delete file.
     */
    public function destroy(Project $project, Attachment $file): JsonResponse
    {
        // Verify file belongs to this project
        if ($file->attachable_id !== $project->id || $file->attachable_type !== Project::class) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan',
            ], 404);
        }

        $fileName = $file->file_name;

        // Delete physical file
        if (Storage::disk('public')->exists($file->file_path)) {
            Storage::disk('public')->delete($file->file_path);
        }

        $file->delete();

        // Log activity
        ProjectActivityService::fileDeleted($project, $fileName);

        return response()->json([
            'success' => true,
            'message' => 'File berhasil dihapus',
        ]);
    }

    /**
     * Download file.
     */
    public function download(Project $project, Attachment $file)
    {
        // Verify file belongs to this project
        if ($file->attachable_id !== $project->id || $file->attachable_type !== Project::class) {
            abort(404, 'File tidak ditemukan');
        }

        if (!Storage::disk('public')->exists($file->file_path)) {
            abort(404, 'File tidak ditemukan di storage');
        }

        return Storage::disk('public')->download(
            $file->file_path,
            $file->file_name
        );
    }

    /**
     * Preview file.
     */
    public function preview(Project $project, Attachment $file)
    {
        // Verify file belongs to this project
        if ($file->attachable_id !== $project->id || $file->attachable_type !== Project::class) {
            abort(404, 'File tidak ditemukan');
        }

        if (!Storage::disk('public')->exists($file->file_path)) {
            abort(404, 'File tidak ditemukan di storage');
        }

        $path = Storage::disk('public')->path($file->file_path);

        // For images, return inline
        if ($file->is_image) {
            return response()->file($path);
        }

        // For PDFs, return inline
        if ($file->is_pdf) {
            return response()->file($path, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        // For other files, download
        return $this->download($project, $file);
    }
}
