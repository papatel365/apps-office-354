<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Modules\System\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class ProjectMilestoneController extends Controller
{
    /**
     * Display milestones for a project.
     */
    public function index(Project $project): View|JsonResponse
    {
        $milestones = $project->milestones()
            ->with(['tasks.assignees.user', 'creator'])
            ->get();

        // Get tasks without milestone
        $ungroupedTasks = $project->tasks()
            ->whereNull('milestone_id')
            ->with(['assignees.user', 'creator'])
            ->orderBy('milestone_sort_order')
            ->get();

        // Get project members for visibility settings
        $projectMembers = $project->members()
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter();

        // Get divisions for visibility settings
        $divisions = Division::where('tenant_id', $this->tenantId())
            ->orderBy('name')
            ->get();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'milestones' => $milestones->map(fn($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'status' => $m->status,
                    'progress' => $m->progress,
                    'start_date' => $m->start_date?->toDateString(),
                    'target_date' => $m->target_date?->toDateString(),
                    'task_count' => $m->tasks->count(),
                    'completed_task_count' => $m->tasks->where('status', 'completed')->count(),
                ]),
            ]);
        }

        return view('crm.projects.milestones.index', compact('project', 'milestones', 'ungroupedTasks', 'projectMembers', 'divisions'));
    }

    /**
     * Get milestones data as JSON for AJAX refresh.
     */
    public function data(Project $project): JsonResponse
    {
        $milestones = $project->milestones()
            ->with(['tasks.assignees.user', 'creator'])
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'milestone_number' => $m->milestone_number,
                'status' => $m->status,
                'progress' => $m->progress,
                'total_tasks' => $m->total_tasks ?? 0,
                'completed_tasks' => $m->completed_tasks ?? 0,
                'start_date' => $m->start_date?->toDateString(),
                'target_date' => $m->target_date?->toDateString(),
                'description' => $m->description,
                'is_overdue' => $m->isOverdue(),
                'days_remaining' => $m->days_remaining,
                'color' => $m->color ?? 'blue',
                'color_hex' => $m->color_hex ?? '#3b82f6',
                'pic_id' => $m->pic_id,
                'tasks' => $m->tasks->map(fn($t) => [
                    'id' => $t->id,
                    'title' => $t->name,
                    'name' => $t->name,
                    'status' => $t->status,
                    'priority' => $t->priority,
                    'due_date' => $t->due_date?->toDateString(),
                    'milestone_id' => $t->milestone_id,
                    'assignees' => $t->assignees->map(fn($a) => $a->user ? [
                        'id' => $a->user->id,
                        'name' => $a->user->name,
                    ] : null)->filter()->values()->toArray(),
                ])->toArray(),
            ])->toArray();

        $ungroupedTasks = $project->tasks()
            ->whereNull('milestone_id')
            ->with(['assignees.user'])
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'title' => $t->name,
                'name' => $t->name,
                'status' => $t->status,
                'priority' => $t->priority,
                'due_date' => $t->due_date?->toDateString(),
                'milestone_id' => null,
                'assignees' => $t->assignees->map(fn($a) => $a->user ? [
                    'id' => $a->user->id,
                    'name' => $a->user->name,
                ] : null)->filter()->values()->toArray(),
            ])->toArray();

        return response()->json([
            'success' => true,
            'milestones' => $milestones,
            'ungroupedTasks' => $ungroupedTasks,
        ]);
    }

    /**
     * Get available tasks for adding to milestone.
     */
    public function availableTasks(Project $project, ProjectMilestone $milestone): JsonResponse
    {
        // Get tasks that can be added:
        // 1. Tasks without milestone
        // 2. Tasks from other milestones in the same project (for moving)
        $availableTasks = $project->tasks()
            ->where(function ($query) use ($milestone) {
                $query->whereNull('milestone_id')
                    ->orWhere('milestone_id', '!=', $milestone->id);
            })
            ->whereNull('deleted_at')
            ->with(['assignees.user', 'creator'])
            ->orderBy('name')
            ->get()
            ->map(fn($task) => [
                'id' => $task->id,
                'name' => $task->name,
                'task_number' => $task->task_number,
                'status' => $task->status,
                'priority' => $task->priority,
                'due_date' => $task->due_date?->toDateString(),
                'assignees' => $task->assignees->map(fn($a) => $a->user->name)->toArray(),
                'is_in_milestone' => $task->milestone_id !== null,
                'current_milestone' => $task->milestone?->name,
            ]);

        return response()->json([
            'success' => true,
            'tasks' => $availableTasks,
        ]);
    }

    /**
     * Add existing tasks to milestone.
     */
    public function addTasks(Request $request, Project $project, ProjectMilestone $milestone): JsonResponse
    {
        $request->validate([
            'task_ids' => 'required|array|min:1',
            'task_ids.*' => 'integer|exists:tasks,id',
        ]);

        // Verify permission
        if (!auth()->user()->hasRole(['director', 'admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk menambahkan task ke milestone.',
            ], 403);
        }

        $tasks = Task::whereIn('id', $request->task_ids)
            ->where('project_id', $project->id)
            ->get();

        $addedCount = 0;
        foreach ($tasks as $task) {
            $oldMilestoneId = $task->milestone_id;

            // Skip if already in this milestone
            if ($oldMilestoneId === $milestone->id) {
                continue;
            }

            $task->update([
                'milestone_id' => $milestone->id,
                'updated_by' => $this->user()->id,
            ]);

            TaskActivity::logMilestoneChanged($task, $this->user(), $oldMilestoneId, $milestone->id);
            $addedCount++;

            // Update old milestone progress if exists
            if ($oldMilestoneId) {
                $oldMilestone = ProjectMilestone::find($oldMilestoneId);
                if ($oldMilestone) {
                    $oldMilestone->calculateAutoProgress();
                }
            }
        }

        // Update this milestone and project progress
        $milestone->calculateAutoProgress();
        $project->updateProgress();

        return response()->json([
            'success' => true,
            'message' => "{$addedCount} task berhasil ditambahkan ke milestone.",
            'added_count' => $addedCount,
        ]);
    }

    /**
     * Show the form for creating a new milestone.
     */
    public function create(Project $project): View
    {
        $projectMembers = $project->members()
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter();

        $divisions = Division::where('tenant_id', $this->tenantId())
            ->orderBy('name')
            ->get();

        return view('crm.projects.milestones.create', compact('project', 'projectMembers', 'divisions'));
    }

    /**
     * Show the form for editing a milestone.
     */
    public function edit(Project $project, ProjectMilestone $milestone): View
    {
        $projectMembers = $project->members()
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter();

        $divisions = Division::where('tenant_id', $this->tenantId())
            ->orderBy('name')
            ->get();

        return view('crm.projects.milestones.edit', compact('project', 'milestone', 'projectMembers', 'divisions'));
    }

    /**
     * Store a new milestone.
     */
    public function store(Request $request, Project $project): JsonResponse|RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'target_date' => 'nullable|date|after_or_equal:start_date',
            'progress' => 'nullable|integer|min:0|max:100',
            'color' => 'nullable|string|in:blue,green,purple,red,orange,yellow,pink,cyan',
            'pic_id' => 'nullable|integer|exists:users,id',
        ]);

        $data = [
            'uuid' => \Str::uuid(),
            'tenant_id' => $project->tenant_id,
            'company_id' => $project->company_id,
            'project_id' => $project->id,
            'milestone_number' => ProjectMilestone::generateNumber(),
            'name' => $request->name,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'target_date' => $request->target_date,
            'color' => $request->color ?? 'blue',
            'pic_id' => $request->pic_id,
            'manual_progress' => $request->progress ?? 0,
            'auto_progress' => 0,
            'total_tasks' => 0,
            'completed_tasks' => 0,
            'status' => ProjectMilestone::STATUS_NOT_STARTED,
            'visibility' => ProjectMilestone::VISIBILITY_PROJECT,
            'sort_order' => ProjectMilestone::getNextSortOrder($project->id),
            'created_by' => $this->user()->id,
        ];

        $milestone = ProjectMilestone::create($data);

        // Update project progress
        $project->updateProgress();

        if ($request->expectsJson()) {
            return $this->success($milestone, 'Milestone berhasil dibuat', 201);
        }

        return redirect()->route('projects.milestones.index', $project)->with('success', 'Milestone berhasil dibuat');
    }

    /**
     * Update a milestone.
     */
    public function update(Request $request, Project $project, ProjectMilestone $milestone): JsonResponse|RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'target_date' => 'nullable|date',
            'progress' => 'nullable|integer|min:0|max:100',
            'visibility' => 'nullable|in:project,selected,private',
            'color' => 'nullable|string|in:blue,green,purple,red,orange,yellow,pink,cyan',
            'pic_id' => 'nullable|integer|exists:users,id',
            'visible_users' => 'nullable|array',
            'visible_users.*' => 'integer',
            'visible_divisions' => 'nullable|array',
            'visible_divisions.*' => 'integer',
        ]);

        $updateData = [
            'name' => $request->name,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'target_date' => $request->target_date,
            'color' => $request->color ?? $milestone->color,
            'pic_id' => $request->pic_id,
            'visibility' => $request->visibility ?? $milestone->visibility,
            'visible_to_users' => $request->visibility === 'selected' ? ($request->visible_users ?? []) : [],
            'visible_to_divisions' => $request->visibility === 'selected' ? ($request->visible_divisions ?? []) : [],
            'updated_by' => $this->user()->id,
        ];

        // Only update progress manually if specified
        if ($request->has('progress')) {
            $updateData['manual_progress'] = $request->progress;
        }

        $milestone->update($updateData);

        // Recalculate auto progress from tasks
        $milestone->calculateAutoProgress();

        // Update project progress
        $project->updateProgress();

        if ($request->expectsJson()) {
            return $this->success($milestone, 'Milestone berhasil diperbarui');
        }

        return redirect()->back()->with('success', 'Milestone berhasil diperbarui');
    }

    /**
     * Quick update status (for Kanban board).
     */
    public function quickStatus(Request $request, Project $project, ProjectMilestone $milestone): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:not_started,in_progress,completed,cancelled',
        ]);

        $milestone->update(['status' => $request->status]);

        // If completed, set completion date
        if ($request->status === ProjectMilestone::STATUS_COMPLETED) {
            $milestone->update(['completed_date' => now()]);
        } elseif ($request->status === ProjectMilestone::STATUS_NOT_STARTED) {
            $milestone->update(['completed_date' => null]);
        }

        // Update project progress
        $project->updateProgress();

        return $this->success($milestone, 'Status milestone berhasil diperbarui');
    }

    /**
     * Update milestone progress.
     */
    public function updateProgress(Request $request, Project $project, ProjectMilestone $milestone): JsonResponse
    {
        $request->validate([
            'progress' => 'required|integer|min:0|max:100',
        ]);

        $milestone->updateProgress($request->progress);

        // Update project progress
        $project->updateProgress();

        return $this->success($milestone, 'Progress berhasil diperbarui');
    }

    /**
     * Mark milestone as in progress.
     */
    public function start(Project $project, ProjectMilestone $milestone): JsonResponse
    {
        $milestone->start();

        return $this->success($milestone, 'Milestone dimulai');
    }

    /**
     * Complete a milestone.
     */
    public function complete(Project $project, ProjectMilestone $milestone): JsonResponse
    {
        $milestone->complete();

        // Update project progress
        $project->updateProgress();

        return $this->success($milestone, 'Milestone selesai');
    }

    /**
     * Cancel a milestone.
     */
    public function cancel(Project $project, ProjectMilestone $milestone): JsonResponse
    {
        $milestone->cancel();

        // Update project progress
        $project->updateProgress();

        return $this->success($milestone, 'Milestone dibatalkan');
    }

    /**
     * Restart a milestone (mark as not started).
     */
    public function restart(Project $project, ProjectMilestone $milestone): JsonResponse
    {
        $milestone->quickRestart();

        // Update project progress
        $project->updateProgress();

        return $this->success($milestone, 'Milestone dikembalikan ke belum dimulai');
    }

    /**
     * Set milestone visibility.
     */
    public function setVisibility(Request $request, Project $project, ProjectMilestone $milestone): JsonResponse
    {
        $request->validate([
            'visibility' => 'required|in:project,selected,private',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'division_ids' => 'nullable|array',
        ]);

        $milestone->setVisibility(
            $request->visibility,
            $request->user_ids,
            $request->division_ids
        );

        return $this->success($milestone, 'Visibility berhasil diperbarui');
    }

    /**
     * Reorder milestones.
     */
    public function reorder(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'milestones' => 'required|array',
            'milestones.*' => 'exists:project_milestones,id',
        ]);

        foreach ($request->milestones as $index => $milestoneId) {
            ProjectMilestone::where('id', $milestoneId)->update(['sort_order' => $index]);
        }

        return $this->success(null, 'Urutan milestone berhasil diperbarui');
    }

    /**
     * Add documentation to a milestone.
     */
    public function addDocumentation(Request $request, Project $project, ProjectMilestone $milestone): JsonResponse
    {
        $request->validate([
            'photos.*' => 'nullable|image|max:2048',
            'notes' => 'nullable|string',
        ]);

        $photoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $filename = 'milestones/' . $project->project_number . '_' . $milestone->milestone_number . '_' . time() . '_' . $photo->getClientOriginalName();
                $path = $photo->storeAs('milestones', basename($filename), 'public');
                $photoPaths[] = $path;
            }
        }

        $milestone->addDocumentation($photoPaths, $request->notes);

        return $this->success($milestone, 'Dokumentasi berhasil ditambahkan');
    }

    /**
     * Delete a milestone.
     */
    public function destroy(Request $request, Project $project, ProjectMilestone $milestone): JsonResponse|RedirectResponse
    {
        // Check if milestone has tasks
        if ($milestone->tasks()->count() > 0) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Milestone tidak dapat dihapus karena masih memiliki task. Pindahkan atau hapus task terlebih dahulu.',
                ], 422);
            }
            return redirect()->back()->with('error', 'Milestone tidak dapat dihapus karena masih memiliki task.');
        }

        // Delete documentation photos
        if ($milestone->documentation && isset($milestone->documentation['photos'])) {
            foreach ($milestone->documentation['photos'] as $photo) {
                if (Storage::disk('public')->exists($photo)) {
                    Storage::disk('public')->delete($photo);
                }
            }
        }

        $milestone->delete();

        // Update project progress
        $project->updateProgress();

        if ($request->expectsJson()) {
            return $this->success(null, 'Milestone berhasil dihapus');
        }

        return redirect()->back()->with('success', 'Milestone berhasil dihapus');
    }

    /**
     * Get milestone details.
     */
    public function show(Project $project, ProjectMilestone $milestone): JsonResponse
    {
        $milestone->load(['tasks.assignees.user', 'creator', 'updater']);

        return response()->json([
            'success' => true,
            'milestone' => [
                'id' => $milestone->id,
                'uuid' => $milestone->uuid,
                'milestone_number' => $milestone->milestone_number,
                'name' => $milestone->name,
                'description' => $milestone->description,
                'status' => $milestone->status,
                'visibility' => $milestone->visibility,
                'start_date' => $milestone->start_date?->toDateString(),
                'target_date' => $milestone->target_date?->toDateString(),
                'completed_date' => $milestone->completed_date?->toDateString(),
                'progress' => $milestone->progress,
                'manual_progress' => $milestone->manual_progress,
                'auto_progress' => $milestone->auto_progress,
                'total_tasks' => $milestone->total_tasks,
                'completed_tasks' => $milestone->completed_tasks,
                'is_overdue' => $milestone->is_overdue,
                'days_remaining' => $milestone->days_remaining,
                'tasks' => $milestone->tasks->map(fn($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'status' => $t->status,
                    'priority' => $t->priority,
                    'due_date' => $t->due_date?->toDateString(),
                    'assignees' => $t->assignees->map(fn($a) => [
                        'id' => $a->user->id,
                        'name' => $a->user->name,
                    ]),
                ]),
                'creator' => [
                    'id' => $milestone->creator->id,
                    'name' => $milestone->creator->name,
                ],
            ],
        ]);
    }
}
