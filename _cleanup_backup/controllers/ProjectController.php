<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\CrmModulePermission;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectTag;
use App\Models\Task;
use App\Http\Requests\ProjectRequest;
use App\Services\CRM\ProjectDashboardService;
use App\Traits\CrmPermissionQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProjectController extends Controller
{
    use CrmPermissionQueries;

    /**
     * Display a listing of projects with filters.
     */
    public function index(Request $request): View
    {
        // Check if user can view ANY data (scope_own OR scope_global)
        if (!$this->canViewAnyData('projects')) {
            abort(403, 'Anda tidak memiliki akses ke halaman Project.');
        }

        $query = Project::query()
            ->with('manager', 'members.user', 'tags');

        // Apply permission-based filtering
        // scope_global = all projects, scope_own = own projects only
        $query = $this->applyProjectPermissionFilter($query);

        // Status filter (can be multiple statuses as comma-separated)
        if ($request->filled('status')) {
            $statuses = is_array($request->status) ? $request->status : explode(',', $request->status);
            $query->whereIn('status', $statuses);
        }

        // Priority filter
        $query->when($request->priority, fn($q) => $q->byPriority($request->priority));

        // Member filter
        if ($request->filled('member_id')) {
            $query->whereHas('members', function ($q) use ($request) {
                $memberIds = is_array($request->member_id) ? $request->member_id : explode(',', $request->member_id);
                $q->whereIn('user_id', $memberIds);
            });
        }

        // Date range filter
        if ($request->filled('date_filter')) {
            switch ($request->date_filter) {
                case 'today':
                    $query->where(function($q) {
                        $q->whereDate('start_date', now()->toDateString())
                          ->orWhereDate('deadline', now()->toDateString());
                    });
                    break;
                case 'this_week':
                    $query->where(function($q) {
                        $q->whereBetween('start_date', [now()->startOfWeek(), now()->endOfWeek()])
                          ->orWhereBetween('deadline', [now()->startOfWeek(), now()->endOfWeek()]);
                    });
                    break;
                case 'this_month':
                    $query->where(function($q) {
                        $q->whereBetween('start_date', [now()->startOfMonth(), now()->endOfMonth()])
                          ->orWhereBetween('deadline', [now()->startOfMonth(), now()->endOfMonth()]);
                    });
                    break;
                case 'this_year':
                    $query->where(function($q) {
                        $q->whereYear('start_date', now()->year)
                          ->orWhereYear('deadline', now()->year);
                    });
                    break;
            }
        }

        $query->orderBy('created_at', 'desc');

        $projects = $query->paginate(20);

        // Get stats
        $stats = $this->getProjectStats();

        // Get trash count
        $trashCount = Project::onlyTrashed()->where('company_id', $this->companyId())->count();

        // Get all users for member filter
        $users = \App\Modules\System\Models\User::where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('crm.projects.index', compact('projects', 'stats', 'users', 'trashCount'));
    }

    /**
     * Check if user can view a specific project.
     * Returns true if user is:
     * - Superadmin
     * - Creator of the project
     * - Member of the project
     * - Has global scope for projects
     */
    protected function canViewProject(Project $project): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        // Superadmin sees all
        if ($this->isSuperadmin()) {
            return true;
        }

        // Creator can always see their own project
        if ($project->created_by === $user->id) {
            return true;
        }

        // Member of the project can see
        if ($project->members()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Global scope for projects
        if ($this->permissionService()->isGlobalScope('projects')) {
            return true;
        }

        return false;
    }

    /**
     * Get project statistics.
     * Uses permission filter to count accessible projects only.
     */
    protected function getProjectStats(): array
    {
        $baseQuery = Project::query();

        // Apply permission-based filtering
        $baseQuery = $this->applyProjectPermissionFilter($baseQuery);

        return [
            'total' => (clone $baseQuery)->count(),
            'not_started' => (clone $baseQuery)->where('status', Project::STATUS_NOT_STARTED)->count(),
            'in_progress' => (clone $baseQuery)->where('status', Project::STATUS_IN_PROGRESS)->count(),
            'on_hold' => (clone $baseQuery)->where('status', Project::STATUS_ON_HOLD)->count(),
            'completed' => (clone $baseQuery)->where('status', Project::STATUS_COMPLETED)->count(),
            'cancelled' => (clone $baseQuery)->where('status', Project::STATUS_CANCELLED)->count(),
        ];
    }

    /**
     * Show the form for creating a new project.
     */
    public function create(Request $request): View
    {
        // Check can_create permission
        if (!$this->canCreate('projects')) {
            abort(403, 'Anda tidak memiliki izin untuk membuat Project.');
        }

        // Get all active users for member selection
        $users = \App\Modules\System\Models\User::where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('crm.projects.create', compact('users'));
    }

    /**
     * Store a newly created project.
     */
    public function store(ProjectRequest $request): RedirectResponse|JsonResponse
    {
        // Check can_create permission
        if (!$this->canCreate('projects')) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin untuk membuat Project.'], 403);
            }
            abort(403, 'Anda tidak memiliki izin untuk membuat Project.');
        }

        $data = $request->validated();
        $data['uuid'] = \Str::uuid();
        // Ensure tenant_id is set: prefer user's tenant_id, fallback to company_id
        $data['tenant_id'] = $this->tenantId() ?? $this->companyId() ?? auth()->user()?->tenant_id ?? auth()->user()?->company_id;
        $data['created_by'] = $this->user()->id;
        $data['project_number'] = Project::generateNumber();

        // Handle tags
        $tags = $request->input('tags', '');
        unset($data['tags'], $data['member_ids']);

        $project = Project::create($data);

        // Add members from form field member_ids[] - use input() to properly retrieve array values
        $memberIds = $request->input('member_ids', []);
        if (!empty($memberIds) && is_array($memberIds)) {
            foreach ($memberIds as $userId) {
                if (is_numeric($userId) && $userId > 0) {
                    ProjectMember::create([
                        'tenant_id' => $project->tenant_id,
                        'project_id' => $project->id,
                        'user_id' => (int) $userId,
                        'added_at' => now(),
                    ]);
                }
            }
        }

        // Add tags
        if (!empty($tags)) {
            $this->saveProjectTags($project, $tags);
        }

        // For API request, return JSON
        if ($request->expectsJson()) {
            return $this->success($project, 'Project created successfully', 201);
        }

        return redirect()->route('projects.index')
            ->with('success', 'Proyek berhasil dibuat');
    }

    /**
     * Save project tags using query builder to bypass BelongsToTenant trait.
     */
    protected function saveProjectTags(Project $project, string $tagsString): void
    {
        // Parse and save new tags
        $tagNames = array_map('trim', explode(',', $tagsString));
        $tagNames = array_filter($tagNames);

        if (empty($tagNames)) {
            return;
        }

        // Get tenant_id: prefer project's tenant_id, fallback to current user's tenant_id/company_id
        $tenantId = $project->tenant_id ?? $this->tenantId() ?? $this->companyId();

        $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'];

        // Use query builder to bypass BelongsToTenant trait auto-fill
        foreach ($tagNames as $index => $name) {
            \DB::table('project_tags')->insert([
                'tenant_id' => $tenantId,
                'project_id' => $project->id,
                'name' => $name,
                'color' => $colors[$index % count($colors)],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Display the specified project.
     * User can view if they are:
     * - Superadmin
     * - Creator of the project
     * - Member of the project
     * - Has global scope for projects
     */
    public function show(Project $project): View
    {
        // Check if user can view this project
        if (!$this->canViewProject($project)) {
            abort(403, 'Anda tidak memiliki akses ke project ini.');
        }

        $project->load('manager', 'members.user', 'tasks', 'tags', 'attachments', 'milestones', 'notes.creator', 'comments.user', 'comments.replies.user');
        return view('crm.projects.show', compact('project'));
    }

    /**
     * Display the project dashboard.
     * User can view if they are:
     * - Superadmin
     * - Creator of the project
     * - Member of the project
     * - Has global scope for projects
     */
    public function dashboard(Project $project): View
    {
        // Check if user can view this project
        if (!$this->canViewProject($project)) {
            abort(403, 'Anda tidak memiliki akses ke project ini.');
        }

        $dashboardService = new ProjectDashboardService($project);
        $dashboard = $dashboardService->getDashboard();

        // Get activities separately for the view
        $activities = $dashboardService->getRecentActivity(50);

        return view('crm.projects.dashboard', compact('project', 'dashboard', 'activities'));
    }

    /**
     * Get dashboard data as JSON (API).
     * User can view if they are:
     * - Superadmin
     * - Creator of the project
     * - Member of the project
     * - Has global scope for projects
     */
    public function dashboardData(Project $project): JsonResponse
    {
        // Check if user can view this project
        if (!$this->canViewProject($project)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke project ini.',
            ], 403);
        }

        $dashboardService = new ProjectDashboardService($project);

        return response()->json([
            'success' => true,
            'data' => $dashboardService->getDashboard(),
        ]);
    }

    /**
     * Show the form for editing the project.
     */
    public function edit(Project $project): View
    {
        // Check can_update permission
        if (!$this->canUpdate('projects')) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit Project.');
        }

        // Get all active users for member selection
        $users = \App\Modules\System\Models\User::where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('crm.projects.edit', compact('project', 'users'));
    }

    /**
     * Update the specified project.
     */
    public function update(ProjectRequest $request, Project $project): RedirectResponse|JsonResponse
    {
        // Check can_update permission
        if (!$this->canUpdate('projects')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk mengubah Project.',
                ], 403);
            }
            abort(403, 'Anda tidak memiliki izin untuk mengubah Project.');
        }

        $data = $request->validated();
        $data['updated_by'] = $this->user()->id;

        // Handle tags
        $tags = $request->input('tags', '');
        unset($data['tags'], $data['member_ids']);

        $project->update($data);

        // Update members from form field member_ids[] - use input() to properly retrieve array values
        $memberIds = $request->input('member_ids', []);
        // Remove existing members
        $project->members()->delete();
        // Add new members
        if (!empty($memberIds) && is_array($memberIds)) {
            foreach ($memberIds as $userId) {
                if (is_numeric($userId) && $userId > 0) {
                    ProjectMember::create([
                        'tenant_id' => $project->tenant_id,
                        'project_id' => $project->id,
                        'user_id' => (int) $userId,
                        'added_at' => now(),
                    ]);
                }
            }
        }

        // Update tags
        if ($request->has('tags')) {
            $this->saveProjectTags($project, $tags);
        }

        // For API request, return JSON
        if ($request->expectsJson()) {
            return $this->success($project, 'Project updated successfully');
        }

        return redirect()->route('projects.index')
            ->with('success', 'Proyek berhasil diperbarui');
    }

    /**
     * Remove the specified project (move to trash).
     */
    public function destroy(Request $request, Project $project): RedirectResponse|JsonResponse
    {
        // Check can_delete permission
        if (!$this->canDelete('projects')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk menghapus Project.',
                ], 403);
            }
            abort(403, 'Anda tidak memiliki izin untuk menghapus Project.');
        }

        $projectName = $project->name;
        $project->delete(); // Soft delete

        // For API request, return JSON
        if ($request->expectsJson()) {
            return $this->success(null, 'Project moved to trash');
        }

        return redirect()->route('projects.index')
            ->with('success', "Proyek \"{$projectName}\" berhasil dipindahkan ke trash");
    }

    /**
     * Display trashed (soft deleted) projects.
     */
    public function trash(Request $request): View
    {
        // Check can_delete permission to access trash
        if (!$this->canDelete('projects')) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses trash Project.');
        }

        $query = Project::onlyTrashed()
            ->with('creator')
            ->where('company_id', $this->companyId());

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('project_number', 'like', "%{$search}%");
            });
        }

        $projects = $query->orderBy('deleted_at', 'desc')->paginate(20);

        // Get stats
        $trashCount = Project::onlyTrashed()->where('company_id', $this->companyId())->count();

        return view('crm.projects.trash', compact('projects', 'trashCount'));
    }

    /**
     * Restore a trashed project.
     */
    public function restore(Request $request, int $id): RedirectResponse|JsonResponse
    {
        // Check can_delete permission to restore
        if (!$this->canDelete('projects')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk memulihkan Project.',
                ], 403);
            }
            abort(403, 'Anda tidak memiliki izin untuk memulihkan Project.');
        }

        $project = Project::onlyTrashed()->findOrFail($id);

        // Verify project belongs to same company
        if ($project->company_id !== $this->companyId()) {
            abort(403, 'Anda tidak memiliki akses ke project ini.');
        }

        $projectName = $project->name;
        $project->restore();

        if ($request->expectsJson()) {
            return $this->success(null, 'Project restored successfully');
        }

        return redirect()->route('projects.trash')
            ->with('success', "Proyek \"{$projectName}\" berhasil dikembalikan");
    }

    /**
     * Permanently delete a trashed project.
     */
    public function forceDelete(Request $request, int $id): RedirectResponse|JsonResponse
    {
        // Check can_delete permission to force delete
        if (!$this->canDelete('projects')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk menghapus permanen Project.',
                ], 403);
            }
            abort(403, 'Anda tidak memiliki izin untuk menghapus permanen Project.');
        }

        $project = Project::onlyTrashed()->findOrFail($id);

        // Verify project belongs to same company
        if ($project->company_id !== $this->companyId()) {
            abort(403, 'Anda tidak memiliki akses ke project ini.');
        }

        $projectName = $project->name;
        $relatedCounts = $project->getRelatedCounts();

        // Perform force delete with all relations
        $project->forceDeleteWithRelations();

        if ($request->expectsJson()) {
            return $this->success(null, 'Project permanently deleted');
        }

        return redirect()->route('projects.trash')
            ->with('success', "Proyek \"{$projectName}\" beserta seluruh datanya berhasil dihapus permanen");
    }

    /**
     * Empty trash - permanently delete all trashed projects.
     */
    public function emptyTrash(Request $request): RedirectResponse|JsonResponse
    {
        // Check can_delete permission to empty trash
        if (!$this->canDelete('projects')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk mengosongkan trash.',
                ], 403);
            }
            abort(403, 'Anda tidak memiliki izin untuk mengosongkan trash.');
        }

        $trashedProjects = Project::onlyTrashed()
            ->where('company_id', $this->companyId())
            ->get();

        $count = $trashedProjects->count();

        foreach ($trashedProjects as $project) {
            $project->forceDeleteWithRelations();
        }

        if ($request->expectsJson()) {
            return $this->success(null, 'Trash emptied successfully');
        }

        return redirect()->route('projects.trash')
            ->with('success', "{$count} proyek berhasil dihapus permanen dari trash");
    }

    /**
     * Complete project.
     */
    public function complete(Request $request, Project $project): RedirectResponse|JsonResponse
    {
        // Check can_update permission to complete
        if (!$this->canUpdate('projects')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk menyelesaikan Project.',
                ], 403);
            }
            abort(403, 'Anda tidak memiliki izin untuk menyelesaikan Project.');
        }

        $project->complete();

        if ($request->expectsJson()) {
            return $this->success($project, 'Project completed');
        }

        return redirect()->back()->with('success', 'Proyek berhasil diselesaikan');
    }

    /**
     * Display Timeline (Gantt Chart) view for all projects.
     * Provides a professional Gantt chart similar to ClickUp/Asana.
     */
    public function timeline(Request $request): View
    {
        // Check if user can view ANY data (scope_own OR scope_global)
        if (!$this->canViewAnyData('projects')) {
            abort(403, 'Anda tidak memiliki akses ke halaman Project.');
        }

        $year = (int) $request->input('year', date('Y'));
        $scale = $request->input('scale', 'month'); // day, week, month, quarter
        $viewMonth = (int) $request->input('month', date('n')); // 1-12

        // Get current user for permissions
        $user = auth()->user();

        // Build projects query with all relationships
        $query = Project::query()
            ->with([
                'manager',
                'members.user',
                'milestones',
                'tasks' => function ($q) {
                    // Order tasks by milestone_sort_order if available, then by created_at
                    // Only show root tasks (no subtasks for cleaner view)
                    $q->whereNull('parent_id')
                      ->orderByRaw("COALESCE(milestone_sort_order, 0) = 0, milestone_sort_order ASC")
                      ->orderBy('created_at', 'asc')
                      ->with(['assignees.user', 'milestone']);
                },
            ]);

        // Apply permission-based filtering
        $query = $this->applyProjectPermissionFilter($query);

        // Filter by status (comma-separated or array)
        if ($request->filled('status')) {
            $statuses = is_array($request->status) ? $request->status : explode(',', $request->status);
            $query->whereIn('status', $statuses);
        }

        // Filter by member
        if ($request->filled('member_id')) {
            $memberIds = is_array($request->member_id) ? $request->member_id : explode(',', $request->member_id);
            $query->whereHas('members', function ($q) use ($memberIds) {
                $q->whereIn('user_id', $memberIds);
            });
        }

        // Filter by project
        if ($request->filled('project_id')) {
            $query->where('id', $request->project_id);
        }

        // Order by start date
        $query->orderBy('start_date', 'asc');

        $projects = $query->get();

        // Calculate statistics
        // Get all tasks from all projects
        $allTasks = $projects->pluck('tasks')->flatten();

        $stats = [
            'total_projects' => $projects->count(),
            'total_tasks' => $allTasks->count(),
            'tasks_in_progress' => $allTasks->where('status', 'in_progress')->count(),
            'tasks_overdue' => $allTasks->filter(fn($t) => $t->is_overdue)->count(),
            'tasks_completed' => $allTasks->where('status', 'completed')->count(),
        ];

        // Calculate overall progress
        $totalTasks = $allTasks->count();
        $completedTasks = $allTasks->where('status', 'completed')->count();
        $stats['overall_progress'] = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        // Get all users for member filter
        $users = \App\Modules\System\Models\User::where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get available years (from existing projects)
        $availableYears = Project::where('tenant_id', $this->tenantId())
            ->whereNotNull('start_date')
            ->selectRaw('YEAR(start_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Add current year if not in list
        if (!in_array(date('Y'), $availableYears)) {
            array_unshift($availableYears, date('Y'));
        }
        sort($availableYears);

        // Project statuses for filter
        $projectStatuses = Project::getStatuses();

        // Build gantt data structure
        $ganttData = $projects->map(function ($project) {
            // Get all tasks with dates (root tasks only - no subtasks for cleaner view)
            $tasks = $project->tasks->filter(function ($task) {
                return $task->parent_id === null && ($task->start_date || $task->due_date);
            })->values();

            // Get milestones
            $milestones = $project->milestones->filter(function ($m) {
                return $m->target_date;
            })->values();

            // Get project members with avatars
            $members = $project->members->map(function ($m) {
                return [
                    'id' => $m->user?->id,
                    'name' => $m->user?->name,
                    'avatar' => $m->user?->avatar_url ?? null,
                    'initials' => $m->user ? substr($m->user->name, 0, 1) : '?',
                ];
            })->filter(fn($m) => $m['id'])->values();

            return [
                'id' => $project->id,
                'uuid' => $project->uuid,
                'name' => $project->name,
                'project_number' => $project->project_number,
                'status' => $project->status,
                'priority' => $project->priority,
                'start_date' => $project->start_date?->toDateString(),
                'deadline' => $project->deadline?->toDateString(),
                'progress' => $project->progress_percent ?? 0,
                'manager' => $project->manager ? [
                    'id' => $project->manager->id,
                    'name' => $project->manager->name,
                    'avatar' => $project->manager->avatar_url ?? null,
                ] : null,
                'members' => $members,
                'tasks' => $tasks->map(function ($task) {
                    $assignees = $task->assignees->map(function ($a) {
                        return [
                            'id' => $a->user?->id,
                            'name' => $a->user?->name,
                            'avatar' => $a->user?->avatar_url ?? null,
                            'initials' => $a->user ? substr($a->user->name, 0, 1) : '?',
                        ];
                    })->filter(fn($a) => $a['id'])->values();

                    return [
                        'id' => $task->id,
                        'uuid' => $task->uuid,
                        'name' => $task->name,
                        'status' => $task->status,
                        'priority' => $task->priority,
                        'start_date' => $task->start_date?->toDateString(),
                        'due_date' => $task->due_date?->toDateString(),
                        'progress' => $task->progress ?? 0,
                        'parent_id' => $task->parent_id,
                        'milestone_id' => $task->milestone_id,
                        'milestone_name' => $task->milestone?->name,
                        'assignees' => $assignees,
                    ];
                })->values(),
                'milestones' => $milestones->map(function ($m) {
                    return [
                        'id' => $m->id,
                        'uuid' => $m->uuid,
                        'name' => $m->name,
                        'target_date' => $m->target_date?->toDateString(),
                        'status' => $m->status,
                        'progress' => $m->progress,
                        'color' => $m->color ?? 'purple',
                    ];
                })->values(),
            ];
        })->values();

        return view('crm.projects.timeline', compact(
            'projects',
            'ganttData',
            'users',
            'year',
            'viewMonth',
            'scale',
            'availableYears',
            'projectStatuses',
            'stats'
        ));
    }

    /**
     * Update task dates via AJAX (for drag-drop).
     */
    public function updateTaskDates(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $task->update([
            'start_date' => $request->start_date,
            'due_date' => $request->due_date,
        ]);

        // Update parent project dates if needed
        if ($task->project) {
            $task->project->updateProgress();
        }

        return response()->json([
            'success' => true,
            'message' => 'Tanggal berhasil diperbarui',
            'data' => [
                'start_date' => $task->start_date?->toDateString(),
                'due_date' => $task->due_date?->toDateString(),
            ],
        ]);
    }
}