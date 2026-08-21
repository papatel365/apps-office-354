<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaskAssignee;
use Illuminate\Http\Request;

class TaskAssigneeController extends Controller
{
    public function index(Request $request, $taskId)
    {
        $assignees = TaskAssignee::where('task_id', $taskId)->with('user')->get();
        return response()->json(['data' => $assignees]);
    }

    public function store(Request $request, $taskId)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $exists = TaskAssignee::where('task_id', $taskId)
            ->where('user_id', $request->user_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'User already assigned'], 400);
        }

        $assignee = TaskAssignee::create([
            'task_id' => $taskId,
            'user_id' => $request->user_id,
        ]);

        return response()->json(['data' => $assignee->load('user'), 'message' => 'User assigned successfully'], 201);
    }

    public function destroy($taskId, $id)
    {
        $assignee = TaskAssignee::where('task_id', $taskId)->findOrFail($id);
        $assignee->delete();

        return response()->json(['message' => 'Assignee removed successfully']);
    }
}
