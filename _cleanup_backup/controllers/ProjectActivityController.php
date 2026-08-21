<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Modules\System\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectActivityController extends Controller
{
    /**
     * Display project activities.
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $limit = $request->get('limit', 50);

        $activities = ActivityLog::where(function ($query) use ($project) {
            // Project level activities
            $query->where(function ($q) use ($project) {
                $q->where('subject_type', Project::class)
                  ->where('subject_id', $project->id);
            });

            // Also get task-level activities for tasks in this project
            $taskIds = $project->tasks()->pluck('id')->toArray();
            if (!empty($taskIds)) {
                $query->orWhere(function ($q) use ($taskIds) {
                    $q->where('subject_type', \App\Models\Task::class)
                      ->whereIn('subject_id', $taskIds);
                });
            }
        })
        ->where('log_name', ActivityLog::LOG_PROJECT)
        ->with('user:id,name,email')
        ->orderBy('created_at', 'desc')
        ->limit($limit)
        ->get()
        ->map(function ($activity) {
            return [
                'id' => $activity->id,
                'uuid' => $activity->uuid,
                'description' => $activity->description,
                'event' => $activity->event,
                'properties' => $activity->properties,
                'created_at' => $activity->created_at,
                'user' => $activity->user ? [
                    'id' => $activity->user->id,
                    'name' => $activity->user->name,
                    'email' => $activity->user->email,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'activities' => $activities,
        ]);
    }
}
