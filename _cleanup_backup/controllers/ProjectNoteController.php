<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Services\CRM\ProjectActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProjectNoteController extends Controller
{
    /**
     * Display listing of project notes.
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $query = ProjectNote::where('project_id', $project->id)
            ->with('creator')
            ->orderBy('created_at', 'desc');

        // Filter by creator
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search in title and content
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('content', 'like', '%' . $search . '%');
            });
        }

        $notes = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'notes' => $notes->items(),
            'pagination' => [
                'current_page' => $notes->currentPage(),
                'last_page' => $notes->lastPage(),
                'per_page' => $notes->perPage(),
                'total' => $notes->total(),
            ],
        ]);
    }

    /**
     * Store new note.
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        try {
            $request->validate([
                'title' => 'nullable|string|max:255',
                'content' => 'required|string|max:50000',
                'color' => 'nullable|string|max:20',
                'tags' => 'nullable|string|max:500',
            ]);

            $note = DB::transaction(function () use ($request, $project) {
                $user = auth()->user();

                // Parse tags from comma-separated string
                $tags = null;
                if ($request->filled('tags')) {
                    $tags = array_filter(array_map('trim', explode(',', $request->tags)));
                }

                $note = ProjectNote::create([
                    'uuid' => Str::uuid(),
                    'tenant_id' => $project->tenant_id,
                    'company_id' => $project->company_id,
                    'project_id' => $project->id,
                    'user_id' => $user->id,
                    'title' => $request->title,
                    'content' => $request->content,
                    'color' => $request->color ?? '#3B82F6',
                    'tags' => $tags,
                ]);

                // Log activity
                ProjectActivityService::noteCreated($project, $request->title);

                return $note;
            });

            return response()->json([
                'success' => true,
                'message' => 'Catatan berhasil dibuat',
                'note' => $note->load('creator'),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create note', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat catatan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display specific note.
     */
    public function show(Project $project, ProjectNote $note): JsonResponse
    {
        // Verify note belongs to this project
        if ($note->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Catatan tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'note' => $note->load('creator'),
        ]);
    }

    /**
     * Update note.
     */
    public function update(Request $request, Project $project, ProjectNote $note): JsonResponse
    {
        // Verify note belongs to this project
        if ($note->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Catatan tidak ditemukan',
            ], 404);
        }

        // Only author can edit
        if ($note->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengedit catatan ini',
            ], 403);
        }

        try {
            $request->validate([
                'title' => 'nullable|string|max:255',
                'content' => 'nullable|string|max:50000',
                'color' => 'nullable|string|max:20',
                'tags' => 'nullable|string|max:500',
            ]);

            $oldTitle = $note->title;

            // Parse tags from comma-separated string
            $tags = null;
            if ($request->has('tags')) {
                if (empty($request->tags)) {
                    $tags = null;
                } else {
                    $tags = array_filter(array_map('trim', explode(',', $request->tags)));
                }
            }

            $note->update([
                'title' => $request->title ?? $note->title,
                'content' => $request->content ?? $note->content,
                'color' => $request->color ?? $note->color,
                'tags' => $tags ?? $note->tags,
            ]);

            // Log activity
            ProjectActivityService::log(
                $project,
                $note->title ? "memperbarui catatan: {$note->title}" : 'memperbarui catatan',
                'note_updated',
                null,
                ['note_id' => $note->id, 'old_title' => $oldTitle, 'new_title' => $note->title]
            );

            return response()->json([
                'success' => true,
                'message' => 'Catatan berhasil diperbarui',
                'note' => $note->fresh('creator'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update note', [
                'note_id' => $note->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui catatan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete note.
     */
    public function destroy(Project $project, ProjectNote $note): JsonResponse
    {
        // Verify note belongs to this project
        if ($note->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Catatan tidak ditemukan',
            ], 404);
        }

        // Only author can delete
        if ($note->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk menghapus catatan ini',
            ], 403);
        }

        try {
            $noteTitle = $note->title;

            $note->delete();

            // Log activity
            ProjectActivityService::noteDeleted($project, $noteTitle);

            return response()->json([
                'success' => true,
                'message' => 'Catatan berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete note', [
                'note_id' => $note->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus catatan: ' . $e->getMessage(),
            ], 500);
        }
    }
}
