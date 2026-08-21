<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskAssignee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskAssigneeController extends Controller
{
    /**
     * Store a newly created task assignee.
     */
    public function store(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $assignee = $task->assignTo(\App\Modules\System\Models\User::find($request->user_id));

        return $this->success($assignee, 'Assignee added successfully', 201);
    }

    /**
     * Remove the specified task assignee.
     */
    public function destroy(Task $task, TaskAssignee $assignee): JsonResponse
    {
        $assignee->delete();

        return $this->success(null, 'Assignee removed successfully');
    }
}
