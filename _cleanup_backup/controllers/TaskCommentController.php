<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaskCommentController extends Controller
{
    /**
     * Store a newly created task comment.
     */
    public function store(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'content' => 'required|string',
            'is_internal' => 'nullable|boolean',
        ]);

        $comment = TaskComment::create([
            'uuid' => Str::uuid(),
            'tenant_id' => $this->tenantId(),
            'task_id' => $task->id,
            'user_id' => $this->user()->id,
            'content' => $request->content,
            'is_internal' => $request->is_internal ?? true,
        ]);

        return $this->success($comment, 'Comment added successfully', 201);
    }

    /**
     * Update the specified task comment.
     */
    public function update(Request $request, Task $task, TaskComment $comment): JsonResponse
    {
        if ($comment->user_id !== $this->user()->id) {
            return $this->error('You can only edit your own comments', 403);
        }

        $request->validate([
            'content' => 'required|string',
        ]);

        $comment->update(['content' => $request->content]);

        return $this->success($comment, 'Comment updated successfully');
    }

    /**
     * Remove the specified task comment.
     */
    public function destroy(Task $task, TaskComment $comment): JsonResponse
    {
        if ($comment->user_id !== $this->user()->id && !$this->user()->isSuperAdmin()) {
            return $this->error('You can only delete your own comments', 403);
        }

        $comment->delete();

        return $this->success(null, 'Comment deleted successfully');
    }
}
