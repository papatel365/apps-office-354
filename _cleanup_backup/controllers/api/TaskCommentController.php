<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaskComment;
use Illuminate\Http\Request;

class TaskCommentController extends Controller
{
    public function index(Request $request, $taskId)
    {
        $comments = TaskComment::where('task_id', $taskId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $comments]);
    }

    public function store(Request $request, $taskId)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $comment = TaskComment::create([
            'task_id' => $taskId,
            'user_id' => auth()->id(),
            'content' => $request->content,
        ]);

        return response()->json(['data' => $comment->load('user'), 'message' => 'Comment added successfully'], 201);
    }

    public function update(Request $request, $taskId, $id)
    {
        $comment = TaskComment::where('task_id', $taskId)->findOrFail($id);

        if ($comment->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate(['content' => 'required|string']);
        $comment->update(['content' => $request->content]);

        return response()->json(['data' => $comment, 'message' => 'Comment updated successfully']);
    }

    public function destroy($taskId, $id)
    {
        $comment = TaskComment::where('task_id', $taskId)->findOrFail($id);

        if ($comment->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully']);
    }
}
