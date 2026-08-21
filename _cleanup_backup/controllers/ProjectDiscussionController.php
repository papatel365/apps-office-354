<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Project;
use App\Services\CRM\ProjectActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectDiscussionController extends Controller
{
    /**
     * Display listing of project discussions.
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $query = Comment::where('commentable_type', Project::class)
            ->where('commentable_id', $project->id)
            ->whereNull('parent_id') // Only root comments (discussions)
            ->with(['user', 'attachments', 'replies.user', 'replies.replies.user', 'replies.attachments'])
            ->withCount('replies')
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

        // Search in content
        if ($request->filled('search')) {
            $query->where('content', 'like', '%' . $request->search . '%');
        }

        $discussions = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'discussions' => $discussions->items(),
            'pagination' => [
                'current_page' => $discussions->currentPage(),
                'last_page' => $discussions->lastPage(),
                'per_page' => $discussions->perPage(),
                'total' => $discussions->total(),
            ],
        ]);
    }

    /**
     * Store new discussion.
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        try {
            $request->validate([
                'title' => 'nullable|string|max:255',
                'content' => 'required|string|max:10000',
                'tags' => 'nullable|string|max:500',
                'attachments' => 'nullable|array',
                'attachments.*' => 'file|max:10240', // 10MB per attachment
            ]);

            $user = auth()->user();
            $attachmentCount = 0;

            // Create discussion (comment)
            $discussion = DB::transaction(function () use ($request, $project, $user, &$attachmentCount) {
                $comment = new Comment();
                $comment->uuid = Str::uuid();
                $comment->tenant_id = $project->tenant_id;
                $comment->commentable_type = Project::class;
                $comment->commentable_id = $project->id;
                $comment->user_id = $user->id;
                $comment->content = $request->content;
                $comment->save();

                // Handle attachments if any
                if ($request->hasFile('attachments')) {
                    foreach ($request->file('attachments') as $file) {
                        $originalName = $file->getClientOriginalName();
                        $extension = $file->getClientOriginalExtension();
                        $fileName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '_' . time() . '_' . Str::random(5) . '.' . $extension;

                        $path = $file->storeAs(
                            'projects/' . $project->id . '/discussions',
                            $fileName,
                            'public'
                        );

                        $comment->attachments()->create([
                            'uuid' => Str::uuid(),
                            'tenant_id' => $project->tenant_id,
                            'file_name' => $originalName,
                            'file_path' => $path,
                            'file_type' => $file->getMimeType(),
                            'file_size' => $file->getSize(),
                            'disk' => 'public',
                            'uploaded_by' => $user->id,
                        ]);
                        $attachmentCount++;
                    }
                }

                return $comment;
            });

            // Log activity
            ProjectActivityService::discussionCreated($project);

            return response()->json([
                'success' => true,
                'message' => 'Diskusi berhasil dibuat',
                'discussion' => $discussion->load(['user', 'attachments']),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create discussion', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat diskusi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display specific discussion with replies.
     */
    public function show(Project $project, Comment $discussion): JsonResponse
    {
        // Verify discussion belongs to this project
        if ($discussion->commentable_id !== $project->id || $discussion->commentable_type !== Project::class) {
            return response()->json([
                'success' => false,
                'message' => 'Diskusi tidak ditemukan',
            ], 404);
        }

        $discussion->load(['user', 'attachments', 'replies.user', 'replies.attachments']);

        return response()->json([
            'success' => true,
            'discussion' => $discussion,
        ]);
    }

    /**
     * Update discussion.
     */
    public function update(Request $request, Project $project, Comment $discussion): JsonResponse
    {
        // Verify discussion belongs to this project
        if ($discussion->commentable_id !== $project->id || $discussion->commentable_type !== Project::class) {
            return response()->json([
                'success' => false,
                'message' => 'Diskusi tidak ditemukan',
            ], 404);
        }

        // Only author can edit
        if ($discussion->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengedit diskusi ini',
            ], 403);
        }

        $request->validate([
            'content' => 'required|string|max:10000',
        ]);

        $discussion->update([
            'content' => $request->content,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Diskusi berhasil diperbarui',
            'discussion' => $discussion->fresh('user'),
        ]);
    }

    /**
     * Delete discussion.
     */
    public function destroy(Project $project, Comment $discussion): JsonResponse
    {
        // Verify discussion belongs to this project
        if ($discussion->commentable_id !== $project->id || $discussion->commentable_type !== Project::class) {
            return response()->json([
                'success' => false,
                'message' => 'Diskusi tidak ditemukan',
            ], 404);
        }

        // Only author can delete
        if ($discussion->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk menghapus diskusi ini',
            ], 403);
        }

        // Soft delete discussion and all replies
        $discussion->delete(); // This cascades to replies if using soft deletes

        return response()->json([
            'success' => true,
            'message' => 'Diskusi berhasil dihapus',
        ]);
    }

    /**
     * Reply to discussion.
     */
    public function reply(Request $request, Project $project, Comment $discussion): JsonResponse
    {
        // Verify discussion belongs to this project
        if ($discussion->commentable_id !== $project->id || $discussion->commentable_type !== Project::class) {
            return response()->json([
                'success' => false,
                'message' => 'Diskusi tidak ditemukan',
            ], 404);
        }

        try {
            $request->validate([
                'content' => 'required|string|max:10000',
                'attachments' => 'nullable|array',
                'attachments.*' => 'file|max:10240',
            ]);

            $user = auth()->user();

            $reply = DB::transaction(function () use ($request, $project, $discussion, $user) {
                $comment = new Comment();
                $comment->uuid = Str::uuid();
                $comment->tenant_id = $project->tenant_id;
                $comment->commentable_type = Project::class;
                $comment->commentable_id = $project->id;
                $comment->user_id = $user->id;
                $comment->parent_id = $discussion->id;
                $comment->content = $request->content;
                $comment->save();

                // Handle attachments if any
                if ($request->hasFile('attachments')) {
                    foreach ($request->file('attachments') as $file) {
                        $originalName = $file->getClientOriginalName();
                        $extension = $file->getClientOriginalExtension();
                        $fileName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '_' . time() . '_' . Str::random(5) . '.' . $extension;

                        $path = $file->storeAs(
                            'projects/' . $project->id . '/discussions',
                            $fileName,
                            'public'
                        );

                        $comment->attachments()->create([
                            'uuid' => Str::uuid(),
                            'tenant_id' => $project->tenant_id,
                            'file_name' => $originalName,
                            'file_path' => $path,
                            'file_type' => $file->getMimeType(),
                            'file_size' => $file->getSize(),
                            'disk' => 'public',
                            'uploaded_by' => $user->id,
                        ]);
                    }
                }

                return $comment;
            });

            // Log activity
            ProjectActivityService::discussionReplied($project);

            return response()->json([
                'success' => true,
                'message' => 'Balasan berhasil dikirim',
                'reply' => $reply->load(['user', 'attachments']),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create reply', [
                'discussion_id' => $discussion->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim balasan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download discussion attachment.
     */
    public function downloadAttachment(Project $project, Attachment $attachment)
    {
        // Verify attachment belongs to a comment on this project
        if ($attachment->attachable_type !== Comment::class) {
            abort(404, 'File tidak ditemukan');
        }

        $comment = Comment::find($attachment->attachable_id);
        if (!$comment || $comment->commentable_type !== Project::class || $comment->commentable_id !== $project->id) {
            abort(404, 'File tidak ditemukan');
        }

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'File tidak ditemukan di storage');
        }

        return Storage::disk('public')->download(
            $attachment->file_path,
            $attachment->file_name
        );
    }
}
