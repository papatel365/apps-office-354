<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $projects = Project::query()
            ->with('client', 'manager')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->priority, fn($q) => $q->byPriority($request->priority))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return $this->paginated($projects);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'client_id' => 'nullable|exists:clients,id',
            'user_id' => 'nullable|exists:users,id',
            'status' => 'nullable|in:not_started,in_progress,on_hold,cancelled,completed',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'start_date' => 'nullable|date',
            'deadline' => 'nullable|date',
            'billing_type' => 'nullable|in:fixed,hourly,task_based',
            'budget' => 'nullable|numeric|min:0',
        ]);

        $project = Project::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $this->tenantId(),
            'project_number' => Project::generateNumber(),
            'created_by' => $this->user()->id,
            ...$request->validated(),
        ]);

        return $this->success($project, 'Project created', 201);
    }

    public function show(Project $project): JsonResponse
    {
        $project->load('client', 'manager', 'members.user', 'tasks');
        return $this->success($project);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $project->update($request->validated());
        return $this->success($project, 'Project updated');
    }

    public function destroy(Project $project): JsonResponse
    {
        $project->delete();
        return $this->success(null, 'Project deleted');
    }

    public function complete(Project $project): JsonResponse
    {
        $project->complete();
        return $this->success($project, 'Project completed');
    }
}
