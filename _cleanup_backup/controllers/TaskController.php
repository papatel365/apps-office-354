<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\CrmModulePermission;
use App\Models\Task;
use App\Models\TaskAssignee;
use App\Models\TaskComment;
use App\Models\Project;
use App\Modules\System\Models\User;
use App\Models\TaskActivity;
use App\Models\TaskApproval;
use App\Http\Requests\TaskRequest;
use App\Traits\CrmPermissionQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    use CrmPermissionQueries;

    /**
     * Get UserPermissionService instance.
     * OVERRIDE: Use UserPermissionService instead of old CrmPermissionService
     */
    protected function permissionService(): \App\Services\Permission\UserPermissionService
    {
        return \App\Services\Permission\UserPermissionService::forUser($this->user());
    }

    /**
     * Get ALL active users for assignment dropdown.
     * Returns ALL users from the users table (no role filtering).
     * Scoped by company_id and tenant_id for data isolation.
     * Only shows active users (is_active = true).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getAllUsersForAssignment(): \Illuminate\Database\Eloquent\Collection
    {
        $companyId = $this->companyId();

        $query = \App\Modules\System\Models\User::query()
            ->select('users.*')
            ->with('roles');

        if ($companyId) {
            $query->where('company_id', $companyId);
        } else {
            $query->where('tenant_id', $this->tenantId());
        }

        return $query
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Check if current user can see all tasks within their tenant.
     * Uses CrmPermissionService as single source of truth.
     *
     * RULES:
     * - Has tasks.view_global permission → can see ALL tasks
     * - Others → can only see assigned tasks
     */
    protected function canSeeAllTasks(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        return $this->permissionService()->isGlobalScope("tasks");
    }

    /**
     * Check if current user can access a specific project.
     * Uses CrmPermissionService as single source of truth.
     */
    protected function canAccessProject(int $projectId): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        // If user has global view permission, they can access all projects
        if ($this->permissionService()->isGlobalScope("projects")) {
            return true;
        }

        // Check if user is a member of the project
        return Project::where('id', $projectId)
            ->whereHas('members', fn($q) => $q->where('user_id', $user->id))
            ->exists();
    }

    /**
     * Validate that all assignees belong to the same company as the current user.
     * This is a defense-in-depth check to prevent cross-company assignment.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $assignees Array of assignee data with 'user_id' key
     * @return bool True if all assignees are valid, false otherwise
     */
    protected function validateAssigneesForCompany($request, array $assignees): bool
    {
        $user = $this->user();
        $companyId = $user?->company_id;

        // If no company context, skip validation (let FormRequest handle it)
        if (!$companyId) {
            return true;
        }

        // Also validate assignee_ids if present
        if ($request->has('assignee_ids') && is_array($request->assignee_ids)) {
            $assigneeIds = $request->assignee_ids;
            foreach ($assigneeIds as $userId) {
                $assignee = User::find($userId);
                if (!$assignee || $assignee->company_id !== $companyId) {
                    return false;
                }
            }
        }

        // Validate assignee_data
        foreach ($assignees as $assignee) {
            if (isset($assignee['user_id'])) {
                $assigneeUser = User::find($assignee['user_id']);
                if (!$assigneeUser || $assigneeUser->company_id !== $companyId) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get available users for assignment within the active company.
     *
     * SECURITY: Only returns users with Director or Admin roles belonging to the same company.
     * This prevents non-authorized users (Staff, Employee, Client, etc.) from being assigned.
     *
     * ASSIGNEE PERMISSION RULES:
     * - Only Director and Admin can be assigned as task assignees
     * - Staff, Employee, User, Client, Guest CANNOT be assignees
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getAvailableUsers(): \Illuminate\Database\Eloquent\Collection
    {
        $companyId = $this->companyId();

        if (!$companyId) {
            // Fallback to tenant_id if company_id is not set
            // Use Query Builder scope for filtering Director/Admin roles
            return \App\Modules\System\Models\User::query()
                ->where('tenant_id', $this->tenantId())
                ->where('is_active', true)
                ->canBeAssignee() // Use scope to filter Director/Admin only
                ->orderBy('name')
                ->get();
        }

        // Use Query Builder scope for filtering Director/Admin roles
        // This is more efficient than fetching all users and filtering in PHP
        return \App\Modules\System\Models\User::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->canBeAssignee() // Use scope to filter Director/Admin only
            ->orderBy('name')
            ->get();
    }

    /**
     * Get existing assignees for a task (read-only display).
     * Used in edit view to display old assignees.
     *
     * @param \App\Models\Task $task
     * @return \Illuminate\Support\Collection
     */
    protected function getExistingAssigneesForEdit(\App\Models\Task $task): \Illuminate\Support\Collection
    {
        return $task->assignees->map(function ($assignee) {
            return [
                'user_id' => $assignee->user_id,
                'user_name' => $assignee->user?->name ?? 'Unknown User',
                'role' => $assignee->role,
                'job_description' => $assignee->job_description,
            ];
        });
    }

    /**
     * Display a listing of tasks.
     *
     * DATA FILTERING RULES:
     * - Superadmin → see ALL tasks in their company
     * - scope_global = true → see ALL tasks in their company
     * - scope_own = true (with scope_global = false) → tasks they created OR are assigned to
     * - Neither scope → 403 Access Denied
     */
    public function index(Request $request): View|JsonResponse
    {
        // Check if user can view ANY data (scope_own OR scope_global)
        if (!$this->canViewAnyData('tasks')) {
            abort(403, 'Anda tidak memiliki akses ke halaman Task.');
        }

        $query = Task::query()
            ->with('project.milestones', 'assignees.user')
            ->when($request->project_id, fn($q) => $q->byProject($request->project_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->priority, fn($q) => $q->byPriority($request->priority))
            ->when($request->assignee_id, fn($q) => $q->byAssignee($request->assignee_id))
            ->when($request->my_tasks, fn($q) => $q->byAssignee($this->user()->id))
            ->when($request->milestone_id, fn($q) => $q->byMilestone($request->milestone_id));

        // Apply CRM permission filter
        // This respects: view_global, view_own, or no access
        $query = $this->applyTaskPermissionFilter($query);

        // Calendar view - filter by date range with OVERLAP logic
        if ($request->calendar) {
            $startDate = $request->start ?? now()->startOfMonth()->format('Y-m-d');
            $endDate = $request->end ?? now()->endOfMonth()->format('Y-m-d');

            // Use OVERLAP query to properly handle cross-month tasks
            // Task overlaps if: task_start <= visible_end AND task_end >= visible_start
            $query->where(function($q) use ($startDate, $endDate) {
                $q->where(function($q1) use ($startDate, $endDate) {
                    // Tasks with both dates - proper overlap
                    $q1->whereNotNull('start_date')
                       ->whereNotNull('due_date')
                       ->where('start_date', '<=', $endDate)
                       ->where('due_date', '>=', $startDate);
                })->orWhere(function($q2) use ($startDate, $endDate) {
                    // Tasks with only due_date
                    $q2->whereNull('start_date')
                       ->whereNotNull('due_date')
                       ->where('due_date', '>=', $startDate)
                       ->where('due_date', '<=', $endDate);
                })->orWhere(function($q3) use ($startDate, $endDate) {
                    // Tasks with only start_date
                    $q3->whereNotNull('start_date')
                       ->whereNull('due_date')
                       ->where('start_date', '>=', $startDate)
                       ->where('start_date', '<=', $endDate);
                });
            });

            $tasks = $query->orderBy('due_date', 'asc')->get();

            if ($request->expectsJson() || $request->ajax()) {
                // Transform to FullCalendar event format for JSON response
                $events = $tasks->map(function ($task) {
                    $assigneeNames = $task->assignees->pluck('user.name')->filter()->values();
                    $start = $task->start_date?->format('Y-m-d') ?? $task->due_date?->format('Y-m-d') ?? now()->format('Y-m-d');
                    $end = $task->due_date?->format('Y-m-d');

                    // FullCalendar uses exclusive end, so add 1 day
                    if ($end) {
                        $end = \Carbon\Carbon::parse($end)->addDay()->format('Y-m-d');
                    }

                    $isOverdue = $task->due_date &&
                                 $task->due_date->lt(now()) &&
                                 $task->status !== Task::STATUS_COMPLETED &&
                                 $task->status !== Task::STATUS_CANCELLED;

                    return [
                        'id' => $task->id,
                        'title' => $task->name,
                        'start' => $start,
                        'end' => $end,
                        'allDay' => true,
                        'backgroundColor' => $this->getEventColor($task, $isOverdue),
                        'borderColor' => $this->getEventBorderColor($task, $isOverdue),
                        'extendedProps' => [
                            'name' => $task->name,
                            'description' => $task->description,
                            'status' => $task->status,
                            'status_label' => $task->status_label,
                            'priority' => $task->priority,
                            'priority_label' => $task->priority_label,
                            'start_date' => $task->start_date?->format('d M Y'),
                            'due_date' => $task->due_date?->format('d M Y'),
                            'project_name' => $task->project?->name,
                            'assignee_names' => $assigneeNames->toArray(),
                            'is_overdue' => $isOverdue,
                        ],
                    ];
                });

                return response()->json([
                    'success' => true,
                    'calendar_events' => $events,
                    'tasks' => $tasks,
                    'total' => $tasks->count(),
                ]);
            }

            $projects = Project::where('tenant_id', $this->tenantId())->orderBy('name')->get();
            $users = $this->getAvailableUsers();
            $canSeeAll = $this->canSeeAllTasks();
            $canCreate = $this->canCreate('tasks');
            $canUpdate = $this->canUpdate('tasks');
            $canDelete = $this->canDelete('tasks');

            $stats = $this->getTaskStats();

            return view('crm.tasks.calendar', compact('tasks', 'stats', 'projects', 'users', 'canSeeAll', 'canCreate', 'canUpdate', 'canDelete'));
        }

        // Order
        $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
              ->orderBy('due_date', 'asc');

        $tasks = $query->paginate(20);

        $projects = Project::where('tenant_id', $this->tenantId())->orderBy('name')->get();
        $users = $this->getAvailableUsers();
        $canSeeAll = $this->canSeeAllTasks();
        $canCreate = $this->canCreate('tasks');
        $canUpdate = $this->canUpdate('tasks');
        $canDelete = $this->canDelete('tasks');

        $stats = $this->getTaskStats();

        return view('crm.tasks.index', compact('tasks', 'stats', 'projects', 'users', 'canSeeAll', 'canCreate', 'canUpdate', 'canDelete'));
    }

    /**
     * Get task statistics.
     * Uses applyTaskPermissionFilter to ensure consistent permission logic:
     * - Superadmin/Global scope → all tasks in company
     * - Own scope → tasks created by user OR assigned to user
     */
    protected function getTaskStats(): array
    {
        $baseQuery = Task::query();

        // Apply unified permission filter (handles all scope logic)
        $baseQuery = $this->applyTaskPermissionFilter($baseQuery);

        return [
            'total' => (clone $baseQuery)->count(),
            'to_do' => (clone $baseQuery)->where('status', Task::STATUS_TO_DO)->count(),
            'pending' => (clone $baseQuery)->where('status', Task::STATUS_TO_DO)->count(),
            'in_progress' => (clone $baseQuery)->where('status', Task::STATUS_IN_PROGRESS)->count(),
            'waiting_approval' => (clone $baseQuery)->where('status', Task::STATUS_WAITING_APPROVAL)->count(),
            'completed' => (clone $baseQuery)->where('status', Task::STATUS_COMPLETED)->count(),
            'overdue' => (clone $baseQuery)->overdue()->count(),
        ];
    }

    /**
     * Show the form for creating a new task.
     */
    public function create(Request $request): View
    {
        // Check can_create permission
        if (!$this->canCreate('tasks')) {
            abort(403, 'Anda tidak memiliki izin untuk membuat Task.');
        }

        $projectId = $request->project_id;
        $parentId = $request->parent_id;
        $milestoneId = $request->milestone_id;

        // Get projects user has access to
        $projects = $this->getAccessibleProjects();

        $milestones = [];
        if ($projectId) {
            $milestones = Project::find($projectId)?->milestones()->get() ?? [];
        }

        // Get ALL users for assignment dropdown (no role filtering)
        $users = $this->getAllUsersForAssignment();
        $roles = Task::getRoles();
        $recurringTypes = Task::getRecurringTypes();

        return view('crm.tasks.create', compact('projectId', 'parentId', 'milestoneId', 'projects', 'milestones', 'users', 'roles', 'recurringTypes'));
    }

    /**
     * Get projects that the current user can access.
     */
    protected function getAccessibleProjects(): \Illuminate\Database\Eloquent\Collection
    {
        // Get tenant/company ID with fallback to prevent empty results
        $tenantId = $this->tenantId() ?? $this->companyId();

        // Director/Admin can access all projects
        if ($this->user()->isDirectorOrAdmin()) {
            return Project::where('tenant_id', $tenantId)
                ->orWhere('company_id', $this->companyId())
                ->orderBy('name')->get();
        }

        // Other users can only access projects they're members of
        return Project::where('tenant_id', $tenantId)
            ->orWhere('company_id', $this->companyId())
            ->where(function($q) {
                $q->whereHas('members', fn($q) => $q->where('user_id', $this->user()->id));
                // Also include projects without members (public projects)
                $q->orWhereDoesntHave('members');
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Store a newly created task.
     */
    public function store(TaskRequest $request): RedirectResponse|JsonResponse
    {
        // Check can_create permission
        if (!$this->canCreate('tasks')) {
            if ($request->expectsJson()) {
                return $this->error('Anda tidak memiliki izin untuk membuat Task.', 403);
            }
            abort(403, 'Anda tidak memiliki izin untuk membuat Task.');
        }

        $data = $request->validated();

        // Validate project access if project_id is provided
        if (isset($data['project_id']) && $data['project_id']) {
            if (!$this->canAccessProject($data['project_id'])) {
                if ($request->expectsJson()) {
                    return $this->error('Anda tidak memiliki akses ke project ini.', 403);
                }
                return redirect()->back()->with('error', 'Anda tidak memiliki akses ke project ini.');
            }
        }

        // Validate milestone access if milestone_id is provided
        if (isset($data['milestone_id']) && $data['milestone_id']) {
            $milestone = \App\Models\ProjectMilestone::find($data['milestone_id']);
            if ($milestone && !$milestone->isVisibleTo($this->user())) {
                if ($request->expectsJson()) {
                    return $this->error('Anda tidak memiliki akses ke milestone ini.', 403);
                }
                return redirect()->back()->with('error', 'Anda tidak memiliki akses ke milestone ini.');
            }
        }

        // SECURITY: Validate all assignees belong to the same company
        // This is a defense-in-depth check in addition to FormRequest validation
        if (!$this->validateAssigneesForCompany($request, $request->assignee_data ?? [])) {
            if ($request->expectsJson()) {
                return $this->error('Salah satu assignee tidak terdaftar pada perusahaan aktif.', 403);
            }
            return redirect()->back()->with('error', 'Salah satu assignee tidak terdaftar pada perusahaan aktif.')->withInput();
        }

        $data['uuid'] = \Str::uuid();
        // Set tenant_id - try multiple sources in order of priority
        $tenantService = app(\App\Services\TenantService::class);
        $tenantId = $tenantService->getCurrentTenantId()
            ?? $this->user()?->tenant_id
            ?? $this->user()?->company?->tenant_id
            ?? \App\Modules\System\Models\Tenant::first()?->id;

        $data['tenant_id'] = $tenantId;
        $data['created_by'] = $this->user()->id;
        $data['assigned_by'] = $this->user()->id;
        $data['task_number'] = Task::generateNumber();
        $data['status'] = Task::STATUS_TO_DO;

        // Extract follower_ids for later processing
        $followerIds = $request->input('follower_ids', []);
        unset($data['follower_ids']);

        $task = Task::create($data);

        // Log task creation
        TaskActivity::logCreated($task, $this->user());

        // Add assignees with roles and job descriptions
        if ($request->assignee_data) {
            foreach ($request->assignee_data as $assignee) {
                if (isset($assignee['user_id'])) {
                    $task->assignTo(
                        User::find($assignee['user_id']),
                        $assignee['role'] ?? null,
                        $assignee['job_description'] ?? null,
                        $this->user()
                    );
                }
            }
        }

        // Add followers
        if (!empty($followerIds)) {
            $this->saveTaskFollowers($task, $followerIds);
        }

        if ($request->expectsJson()) {
            return $this->success($task, 'Task created successfully', 201);
        }

        return redirect()->route('tasks.index')
            ->with('success', 'Tugas berhasil dibuat');
    }

    /**
     * Save task followers.
     */
    protected function saveTaskFollowers(Task $task, array $followerIds): void
    {
        $task->followers()->delete();

        foreach ($followerIds as $userId) {
            \App\Models\TaskFollower::create([
                'tenant_id' => $task->tenant_id,
                'task_id' => $task->id,
                'user_id' => $userId,
                'followed_at' => now(),
            ]);
        }
    }

    /**
     * Display the specified task.
     */
    public function show(Task $task): View
    {
        // Check permission using Gate
        if (Gate::denies('view', $task)) {
            abort(403, 'Anda tidak memiliki akses ke task ini.');
        }

        $task->load(
            'project.milestones',
            'parent',
            'assignees.user',
            'comments.user',
            'activities',
            'approvals',
            'photos.uploader',
            'workUpdates.user',
            'workUpdates.photos',
            'checklists.user',
            'followers.user'
        );

        // Get available next statuses for current user
        $availableStatuses = $task->getAvailableNextStatuses($this->user());

        // Additional permission flags for views
        $canEditWork = $task->canEditWork($this->user());
        $canEditStructure = $task->canEditStructure($this->user());
        $canAddWorkUpdate = $task->canAddWorkUpdate($this->user());

        return view('crm.tasks.show', compact('task', 'availableStatuses', 'canEditWork', 'canEditStructure', 'canAddWorkUpdate'));
    }

    /**
     * Show the form for editing the task.
     */
    public function edit(Task $task): View
    {
        // Check if user can view the task first
        if (Gate::denies('view', $task)) {
            abort(403, 'Anda tidak memiliki akses ke task ini.');
        }

        // Check if user can edit (structure or work)
        $canEditWork = $task->canEditWork($this->user());
        $canEditStructure = $task->canEditStructure($this->user());

        // If user can't edit at all, redirect to show view
        if (!$canEditWork && !$canEditStructure) {
            return redirect()->route('tasks.show', $task);
        }

        $projects = Project::where('tenant_id', $this->tenantId())->orderBy('name')->get();

        // Get milestones for the selected project
        $milestones = [];
        if ($task->project_id) {
            $milestones = $task->project->milestones()->get();
        }

        // Get ALL users for assignment dropdown (no role filtering)
        $users = $this->getAllUsersForAssignment();
        $roles = Task::getRoles();
        $recurringTypes = Task::getRecurringTypes();

        // Get existing assignees with their role status
        // This includes assignees who may not have Director/Admin roles (legacy data)
        // They will be shown as read-only info, not as selectable options
        $existingAssignees = $this->getExistingAssigneesForEdit($task);

        // Get existing followers
        $existingFollowers = $task->followers->pluck('user_id')->toArray();

        return view('crm.tasks.edit', compact('task', 'projects', 'milestones', 'users', 'roles', 'canEditWork', 'canEditStructure', 'existingAssignees', 'existingFollowers', 'recurringTypes'));
    }

    /**
     * Update the specified task.
     */
    public function update(TaskRequest $request, Task $task): RedirectResponse|JsonResponse
    {
        // Check can_update permission first
        if (!$this->canUpdate('tasks')) {
            if ($request->expectsJson()) {
                return $this->error('Anda tidak memiliki izin untuk mengubah Task.', 403);
            }
            abort(403, 'Anda tidak memiliki izin untuk mengubah Task.');
        }

        // Check view permission first
        if (Gate::denies('view', $task)) {
            if ($request->expectsJson()) {
                return $this->error('Anda tidak memiliki akses ke task ini.', 403);
            }
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke task ini.');
        }

        // Check edit structure permission
        if (!$task->canEditStructure($this->user())) {
            if ($request->expectsJson()) {
                return $this->error('Anda tidak memiliki akses untuk mengubah struktur task.', 403);
            }
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk mengubah struktur task.');
        }

        // SECURITY: Validate all assignees belong to the same company
        // This is a defense-in-depth check in addition to FormRequest validation
        if (!$this->validateAssigneesForCompany($request, $request->assignee_data ?? [])) {
            if ($request->expectsJson()) {
                return $this->error('Salah satu assignee tidak terdaftar pada perusahaan aktif.', 403);
            }
            return redirect()->back()->with('error', 'Salah satu assignee tidak terdaftar pada perusahaan aktif.')->withInput();
        }

        $data = $request->validated();
        $data['updated_by'] = $this->user()->id;

        // Extract follower_ids for later processing
        $followerIds = $request->input('follower_ids', []);
        unset($data['follower_ids']);

        $task->update($data);

        // Update assignees with roles and job descriptions
        if ($request->has('assignee_data')) {
            $task->assignees()->delete();
            foreach ($request->assignee_data as $assignee) {
                if (isset($assignee['user_id'])) {
                    $task->assignTo(
                        User::find($assignee['user_id']),
                        $assignee['role'] ?? null,
                        $assignee['job_description'] ?? null,
                        $this->user()
                    );
                }
            }
        }

        // Update followers
        if ($request->has('follower_ids')) {
            $this->saveTaskFollowers($task, $followerIds);
        }

        // Log field changes
        foreach ($data as $field => $value) {
            if (in_array($field, ['name', 'project_id', 'priority', 'due_date', 'description', 'tags'])) {
                TaskActivity::logFieldChanged($task, $this->user(), $field, $task->getOriginal($field) ?? '', $value);
            }
        }

        if ($request->expectsJson()) {
            return $this->success($task, 'Task updated successfully');
        }

        return redirect()->route('tasks.index')
            ->with('success', 'Tugas berhasil diperbarui');
    }

    /**
     * Remove the specified task (soft delete).
     *
     * PERMISSION: must have can_delete permission
     * TENANT: Task must belong to user's tenant/company
     */
    public function destroy(Request $request, Task $task): RedirectResponse|JsonResponse
    {
        // Check can_delete permission
        if (!$this->canDelete('tasks')) {
            if ($request->expectsJson()) {
                return $this->error('Anda tidak memiliki izin untuk menghapus Task.', 403);
            }
            abort(403, 'Anda tidak memiliki izin untuk menghapus Task.');
        }

        // Tenant isolation: Check if task belongs to user's tenant/company
        $userCompanyId = $this->user()->company_id;
        if ($task->company_id !== $userCompanyId) {
            if ($request->expectsJson()) {
                return $this->error('Anda tidak dapat menghapus task dari tenant/perusahaan lain.', 403);
            }
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus task dari tenant/perusahaan lain.');
        }

        // Log deletion
        TaskActivity::logDeleted($task, $this->user());

        $task->delete();

        if ($request->expectsJson()) {
            return $this->success(null, 'Task deleted successfully');
        }

        return redirect()->route('tasks.index')
            ->with('success', 'Tugas berhasil dihapus');
    }

    /**
     * Restore a soft-deleted task.
     *
     * PERMISSION: must have can_delete permission
     * TENANT: Task must belong to user's tenant/company
     */
    public function restore(Request $request, Task $task): RedirectResponse|JsonResponse
    {
        // Check can_delete permission to restore
        if (!$this->canDelete('tasks')) {
            if ($request->expectsJson()) {
                return $this->error('Anda tidak memiliki izin untuk memulihkan Task.', 403);
            }
            abort(403, 'Anda tidak memiliki izin untuk memulihkan Task.');
        }

        // Tenant isolation: Check if task belongs to user's tenant/company
        $userCompanyId = $this->user()->company_id;
        if ($task->company_id !== $userCompanyId) {
            if ($request->expectsJson()) {
                return $this->error('Anda tidak dapat memulihkan task dari tenant/perusahaan lain.', 403);
            }
            return redirect()->back()->with('error', 'Anda tidak dapat memulihkan task dari tenant/perusahaan lain.');
        }

        $task->restore();
        TaskActivity::logRestored($task, $this->user());

        if ($request->expectsJson()) {
            return $this->success($task, 'Task restored successfully');
        }

        return redirect()->back()->with('success', 'Tugas berhasil dipulihkan');
    }

    /**
     * Start task - change status to In Progress.
     */
    public function start(Request $request, Task $task): RedirectResponse|JsonResponse
    {
        if (!$task->canBeStartedBy($this->user())) {
            if ($request->expectsJson()) {
                return $this->error('Task tidak dapat dimulai dari status saat ini.', 422);
            }
            return redirect()->back()->with('error', 'Task tidak dapat dimulai dari status saat ini.');
        }

        $task->start($this->user());

        if ($request->expectsJson()) {
            return $this->success($task, 'Task started');
        }

        return redirect()->back()->with('success', 'Tugas berhasil dimulai');
    }

    /**
     * Submit task for approval - change status to Waiting Approval.
     * Validates that required photos are present.
     */
    public function submitForApproval(Request $request, Task $task): RedirectResponse|JsonResponse
    {
        if (!$task->canBeSubmittedForApprovalBy($this->user())) {
            if ($request->expectsJson()) {
                return $this->error('Task tidak dapat diajukan untuk approval dari status saat ini.', 422);
            }
            return redirect()->back()->with('error', 'Task tidak dapat diajukan untuk approval dari status saat ini.');
        }

        // Validate required photos
        if ($task->require_photo && $task->photos()->count() === 0) {
            $message = 'Task ini memerlukan bukti foto sebelum dapat diajukan untuk approval. Mohon unggah minimal 1 foto.';

            if ($request->expectsJson()) {
                return $this->error($message, 422);
            }
            return redirect()->back()->with('error', $message)->withInput();
        }

        $task->markAsWaitingApproval($this->user());

        // Add system comment
        $task->comments()->create([
            'tenant_id' => $this->tenantId(),
            'user_id' => $this->user()->id,
            'content' => 'Task diajukan untuk approval.',
            'is_internal' => true,
        ]);

        if ($request->expectsJson()) {
            return $this->success($task, 'Task submitted for approval');
        }

        return redirect()->back()->with('success', 'Tugas berhasil diajukan untuk persetujuan');
    }

    /**
     * Approve task - change status to Completed.
     *
     * PERMISSION: Only Director and Admin can approve tasks.
     */
    public function approve(Request $request, Task $task): RedirectResponse|JsonResponse
    {
        if (!$task->canApprove($this->user())) {
            if ($request->expectsJson()) {
                return $this->error('Anda tidak memiliki izin untuk menyetujui tugas ini. Hanya Director dan Admin yang dapat menyetujui.', 403);
            }
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk menyetujui tugas ini. Hanya Director dan Admin yang dapat menyetujui.');
        }

        $task->approve($this->user());

        // Add approval comment
        $task->comments()->create([
            'tenant_id' => $this->tenantId(),
            'user_id' => $this->user()->id,
            'content' => 'Task disetujui dan ditandai selesai.',
            'is_internal' => true,
        ]);

        // Update project progress
        if ($task->project_id) {
            $task->project->updateProgress();
        }

        if ($request->expectsJson()) {
            return $this->success($task, 'Task approved and completed');
        }

        return redirect()->back()->with('success', 'Tugas berhasil disetujui dan diselesaikan');
    }

    /**
     * Reject task - return to In Progress.
     *
     * PERMISSION: Only Director and Admin can reject tasks.
     * REJECTION REASON IS REQUIRED.
     */
    public function reject(Request $request, Task $task): RedirectResponse|JsonResponse
    {
        if (!$task->canReject($this->user())) {
            if ($request->expectsJson()) {
                return $this->error('Anda tidak memiliki izin untuk menolak tugas ini. Hanya Director dan Admin yang dapat menolak.', 403);
            }
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk menolak tugas ini. Hanya Director dan Admin yang dapat menolak.');
        }

        if ($task->status !== Task::STATUS_WAITING_APPROVAL) {
            if ($request->expectsJson()) {
                return $this->error('Task tidak dapat ditolak dari status saat ini.', 422);
            }
            return redirect()->back()->with('error', 'Task tidak dapat ditolak dari status saat ini.');
        }

        // Validate rejection reason
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $task->reject($this->user(), $request->rejection_reason);

        // Add rejection comment
        $task->comments()->create([
            'tenant_id' => $this->tenantId(),
            'user_id' => $this->user()->id,
            'content' => "Task ditolak. Alasan: {$request->rejection_reason}",
            'is_internal' => false,
        ]);

        if ($request->expectsJson()) {
            return $this->success($task, 'Task rejected and returned to In Progress');
        }

        return redirect()->back()->with('info', 'Tugas ditolak dan dikembalikan ke "Sedang Dikerjakan"');
    }

    /**
     * Complete task directly (bypass approval).
     *
     * PERMISSION: Only Director and Admin can directly complete tasks.
     */
    public function complete(Request $request, Task $task): RedirectResponse|JsonResponse
    {
        if (!$task->canEditStructure($this->user())) {
            if ($request->expectsJson()) {
                return $this->error('Anda tidak memiliki izin untuk menyelesaikan tugas ini langsung. Gunakan approval workflow.', 403);
            }
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk menyelesaikan tugas ini langsung. Gunakan approval workflow.');
        }

        if ($task->status === Task::STATUS_COMPLETED) {
            if ($request->expectsJson()) {
                return $this->error('Task sudah selesai.', 422);
            }
            return redirect()->back()->with('error', 'Task sudah selesai.');
        }

        $task->complete($this->user());

        // Update project progress
        if ($task->project_id) {
            $task->project->updateProgress();
        }

        if ($request->expectsJson()) {
            return $this->success($task, 'Task completed');
        }

        return redirect()->back()->with('success', 'Tugas berhasil diselesaikan');
    }

    /**
     * Cancel task.
     *
     * PERMISSION: Only Director and Admin can cancel tasks.
     */
    public function cancel(Request $request, Task $task): RedirectResponse|JsonResponse
    {
        if (!$this->user()->isDirectorOrAdmin()) {
            if ($request->expectsJson()) {
                return $this->error('Anda tidak memiliki izin untuk membatalkan tugas ini. Hanya Director dan Admin yang dapat membatalkan.', 403);
            }
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk membatalkan tugas ini. Hanya Director dan Admin yang dapat membatalkan.');
        }

        if (in_array($task->status, [Task::STATUS_COMPLETED, Task::STATUS_CANCELLED])) {
            if ($request->expectsJson()) {
                return $this->error('Task tidak dapat dibatalkan.', 422);
            }
            return redirect()->back()->with('error', 'Task tidak dapat dibatalkan.');
        }

        $task->cancel($this->user());

        if ($request->expectsJson()) {
            return $this->success($task, 'Task cancelled');
        }

        return redirect()->back()->with('success', 'Tugas berhasil dibatalkan');
    }

    /**
     * Revert task to To Do status.
     *
     * PERMISSION: Only Director, Admin, and Manager can revert.
     */
    public function revertToDo(Request $request, Task $task): RedirectResponse|JsonResponse
    {
        $user = $this->user();
        if (!$user->hasRole(['director', 'admin', 'manager'])) {
            if ($request->expectsJson()) {
                return $this->error('Anda tidak memiliki izin untuk mengembalikan task.', 403);
            }
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk mengembalikan task.');
        }

        if (!$task->revertToDo($user)) {
            if ($request->expectsJson()) {
                return $this->error('Task tidak dapat dikembalikan ke To Do.', 422);
            }
            return redirect()->back()->with('error', 'Task tidak dapat dikembalikan ke To Do.');
        }

        if ($request->expectsJson()) {
            return $this->success($task, 'Task reverted to To Do');
        }

        return redirect()->back()->with('success', 'Tugas berhasil dikembalikan ke To Do');
    }

    /**
     * Reopen task from Completed to In Progress.
     *
     * PERMISSION: Only Director and Admin can reopen tasks.
     * Completed is FINAL unless explicitly reopened.
     */
    public function reopen(Request $request, Task $task): RedirectResponse|JsonResponse
    {
        if (!$task->canReopen($this->user())) {
            if ($request->expectsJson()) {
                return $this->error('Anda tidak memiliki izin untuk membuka ulang task ini.', 403);
            }
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk membuka ulang task ini.');
        }

        $task->reopen($this->user(), $request->reason);

        if ($request->expectsJson()) {
            return $this->success($task, 'Task reopened to In Progress');
        }

        return redirect()->back()->with('success', 'Task dibuka ulang ke "Sedang Dikerjakan"');
    }

    /**
     * Get calendar view.
     *
     * SECURITY - Uses unified permission filter:
     * - Superadmin/Global scope → see ALL tasks in company
     * - Own scope → see tasks created by user OR assigned to user
     */
    public function calendar(Request $request): View
    {
        $projects = Project::where('tenant_id', $this->tenantId())->orderBy('name')->get();
        $users = $this->getAvailableUsers();
        $canSeeAll = $this->canSeeAllTasks();

        $stats = $this->getTaskStats();

        // Parse date parameters - handle ISO format from FullCalendar (2026-07-01T00:00:00+07:00)
        $startDateRaw = $request->start ?? now()->startOfMonth()->format('Y-m-d');
        $endDateRaw = $request->end ?? now()->endOfMonth()->format('Y-m-d');

        // Extract just the date part if datetime format is passed
        $startDate = substr($startDateRaw, 0, 10);
        $endDate = substr($endDateRaw, 0, 10);

        // Build query with OVERLAP logic for proper date range handling
        // Task overlaps if: task_start <= visible_end AND task_end >= visible_start
        $query = Task::query()
            ->with(['project', 'assignees.user'])
            ->select('tasks.*');

        // Apply unified permission filter (handles all scope logic)
        $query = $this->applyTaskPermissionFilter($query);

        $query->where(function($q) use ($startDate, $endDate) {
            // Handle all date scenarios:
            // 1. Task has both start_date and due_date
            // 2. Task has only start_date (no due_date)
            // 3. Task has only due_date (no start_date)
            // 4. Task has neither (show only if no filters applied)

            $q->where(function($q1) use ($startDate, $endDate) {
                // Tasks with both dates - use overlap logic
                $q1->whereNotNull('start_date')
                   ->whereNotNull('due_date')
                   ->where('start_date', '<=', $endDate)
                   ->where('due_date', '>=', $startDate);
            })->orWhere(function($q2) use ($startDate, $endDate) {
                // Tasks with only due_date
                $q2->whereNull('start_date')
                   ->whereNotNull('due_date')
                   ->where('due_date', '>=', $startDate)
                   ->where('due_date', '<=', $endDate);
            })->orWhere(function($q3) use ($startDate, $endDate) {
                // Tasks with only start_date
                $q3->whereNotNull('start_date')
                   ->whereNull('due_date')
                   ->where('start_date', '>=', $startDate)
                   ->where('start_date', '<=', $endDate);
            });
        });

        $tasks = $query->orderBy('due_date', 'asc')->get();

        return view('crm.tasks.calendar', compact('tasks', 'stats', 'projects', 'users', 'canSeeAll'));
    }

    /**
     * API endpoint for calendar events.
     *
     * SECURITY - Uses unified permission filter:
     * - Superadmin/Global scope → see ALL tasks in company
     * - Own scope → see tasks created by user OR assigned to user
     * - Filters are applied AFTER permission filter
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function calendarEvents(Request $request): JsonResponse
    {
        // Check user authentication
        $user = $this->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $canSeeAll = $this->canSeeAllTasks();

        // Parse date parameters - handle ISO format from FullCalendar (2026-07-01T00:00:00+07:00)
        $startDateRaw = $request->start ?? now()->startOfMonth()->format('Y-m-d');
        $endDateRaw = $request->end ?? now()->endOfMonth()->format('Y-m-d');

        // Extract just the date part if datetime format is passed
        $startDate = substr($startDateRaw, 0, 10);
        $endDate = substr($endDateRaw, 0, 10);

        // Build base query
        $query = Task::query()
            ->with(['project', 'assignees.user'])
            ->select('tasks.*');

        // Apply unified permission filter (handles all scope logic)
        $query = $this->applyTaskPermissionFilter($query);

        // SECURITY: For non-admin users, IGNORE assignee_id filter to prevent bypass
        // Even if staff sends ?assignee_id=999, they should only see their tasks
        $assigneeId = $canSeeAll ? $request->assignee_id : null;

        $query->where(function($q) use ($startDate, $endDate, $assigneeId) {
            // Main overlap conditions
            $q->where(function($q1) use ($startDate, $endDate) {
                // Tasks with both dates - proper overlap
                $q1->whereNotNull('start_date')
                   ->whereNotNull('due_date')
                   ->where('start_date', '<=', $endDate)
                   ->where('due_date', '>=', $startDate);
            })->orWhere(function($q2) use ($startDate, $endDate) {
                // Tasks with only due_date
                $q2->whereNull('start_date')
                   ->whereNotNull('due_date')
                   ->where('due_date', '>=', $startDate)
                   ->where('due_date', '<=', $endDate);
            })->orWhere(function($q3) use ($startDate, $endDate) {
                // Tasks with only start_date
                $q3->whereNotNull('start_date')
                   ->whereNull('due_date')
                   ->where('start_date', '>=', $startDate)
                   ->where('start_date', '<=', $endDate);
            });

            // Apply assignee filter ONLY for admin/director (staff can't filter by others)
            if ($assigneeId) {
                $q->whereHas('assignees', fn($aq) => $aq->where('user_id', $assigneeId));
            }
        });

        // Apply other filters (admin/director only for project filter since it doesn't bypass visibility)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Project filter is safe - doesn't bypass assignee visibility
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $tasks = $query->orderBy('due_date', 'asc')->get();

        // Transform to FullCalendar event format
        // Note: FullCalendar uses exclusive end dates, so we add 1 day if due_date exists
        $events = $tasks->map(function ($task) use ($canSeeAll, $user) {
            // Determine event title based on role
            $assigneeNames = $task->assignees->pluck('user.name')->filter()->values();

            if ($canSeeAll && $assigneeNames->isNotEmpty()) {
                // Admin/Director: show "Assignee — Task Name"
                $firstAssignee = $assigneeNames->first();
                $otherCount = $assigneeNames->count() - 1;
                $titleSuffix = $otherCount > 0 ? " +{$otherCount}" : '';
                $title = "{$firstAssignee}{$titleSuffix} — {$task->name}";
            } else {
                // Staff: just show task name
                $title = $task->name;
            }

            // Determine event start and end dates
            $start = $task->start_date?->format('Y-m-d') ?? $task->due_date?->format('Y-m-d') ?? now()->format('Y-m-d');
            $end = $task->due_date?->format('Y-m-d');

            // FullCalendar uses exclusive end, so add 1 day to include due_date
            if ($end) {
                $end = \Carbon\Carbon::parse($end)->addDay()->format('Y-m-d');
            }

            // Determine if overdue (not completed and due_date is past)
            $isOverdue = $task->due_date &&
                         $task->due_date->lt(now()) &&
                         $task->status !== Task::STATUS_COMPLETED &&
                         $task->status !== Task::STATUS_CANCELLED;

            return [
                'id' => $task->id,
                'title' => $title,
                'start' => $start,
                'end' => $end,
                'allDay' => true,
                'backgroundColor' => $this->getEventColor($task, $isOverdue),
                'borderColor' => $this->getEventBorderColor($task, $isOverdue),
                'textColor' => '#fff',
                'extendedProps' => [
                    'name' => $task->name,
                    'description' => $task->description,
                    'status' => $task->status,
                    'status_label' => $task->status_label,
                    'priority' => $task->priority,
                    'priority_label' => $task->priority_label,
                    'start_date' => $task->start_date?->format('d M Y'),
                    'due_date' => $task->due_date?->format('d M Y'),
                    'project_name' => $task->project?->name,
                    'project_id' => $task->project_id,
                    'assignee_names' => $assigneeNames->toArray(),
                    'assignee_ids' => $task->assignees->pluck('user_id')->toArray(),
                    'is_overdue' => $isOverdue,
                    'can_edit' => $task->canEditStructure($user),
                    'can_delete' => $task->canDelete($user),
                    'task_url' => route('tasks.show', $task),
                    'edit_url' => route('tasks.edit', $task),
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'events' => $events,
            'total' => $events->count(),
            'filters' => [
                'can_see_all' => $canSeeAll,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Get event color based on status and overdue status.
     */
    protected function getEventColor(Task $task, bool $isOverdue): string
    {
        if ($isOverdue) {
            return '#ef4444'; // Red for overdue
        }

        return match($task->status) {
            Task::STATUS_TO_DO => '#6b7280',        // Gray
            Task::STATUS_IN_PROGRESS => '#3b82f6', // Blue
            Task::STATUS_WAITING_APPROVAL => '#f59e0b', // Amber
            Task::STATUS_COMPLETED => '#10b981',    // Green
            Task::STATUS_CANCELLED => '#9ca3af',   // Light Gray
            default => '#6b7280',
        };
    }

    /**
     * Get event border color based on priority.
     */
    protected function getEventBorderColor(Task $task, bool $isOverdue): string
    {
        if ($isOverdue) {
            return '#dc2626'; // Darker red
        }

        return match($task->priority) {
            Task::PRIORITY_URGENT => '#dc2626',    // Red
            Task::PRIORITY_HIGH => '#f97316',      // Orange
            Task::PRIORITY_MEDIUM => '#3b82f6',    // Blue
            Task::PRIORITY_LOW => '#9ca3af',       // Gray
            default => '#6b7280',
        };
    }

    /**
     * Move task to a milestone.
     */
    public function moveMilestone(Request $request, Task $task): RedirectResponse|JsonResponse
    {
        // Check permission using policy
        if (Gate::denies('moveMilestone', $task)) {
            if ($request->expectsJson()) {
                return $this->error('Anda tidak memiliki izin untuk memindahkan task ke milestone.', 403);
            }
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk memindahkan task ke milestone.');
        }

        // Validate milestone_id (can be null to remove from milestone)
        $request->validate([
            'milestone_id' => 'nullable|integer|exists:project_milestones,id',
        ], [
            'milestone_id.exists' => 'Milestone tidak ditemukan.',
        ]);

        $oldMilestoneId = $task->milestone_id;
        $newMilestoneId = $request->milestone_id;

        // Check if already in the same milestone
        if ($oldMilestoneId === $newMilestoneId) {
            if ($request->expectsJson()) {
                return $this->success($task, 'Task sudah berada di milestone ini.');
            }
            return redirect()->back()->with('info', 'Task sudah berada di milestone ini.');
        }

        // Check if moving to a different project
        if ($newMilestoneId) {
            $targetMilestone = \App\Models\ProjectMilestone::find($newMilestoneId);
            if ($targetMilestone && $targetMilestone->project_id !== $task->project_id) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Task hanya dapat dipindahkan ke milestone dalam project yang sama.',
                    ], 422);
                }
                return redirect()->back()->with('error', 'Task hanya dapat dipindahkan ke milestone dalam project yang sama.');
            }
        }

        // Get milestone names for response
        $oldMilestoneName = null;
        $newMilestoneName = null;

        if ($oldMilestoneId) {
            $oldMilestone = \App\Models\ProjectMilestone::find($oldMilestoneId);
            $oldMilestoneName = $oldMilestone?->name;
        }
        if ($newMilestoneId) {
            $newMilestone = \App\Models\ProjectMilestone::find($newMilestoneId);
            $newMilestoneName = $newMilestone?->name;
        }

        // Perform the update
        $task->update([
            'milestone_id' => $newMilestoneId,
            'updated_by' => $this->user()->id,
        ]);

        // Log the activity
        TaskActivity::logMilestoneChanged($task, $this->user(), $oldMilestoneId, $newMilestoneId);

        // Update project and milestone progress
        $task->project->updateProgress();
        if ($oldMilestoneId) {
            $oldMilestone = \App\Models\ProjectMilestone::find($oldMilestoneId);
            if ($oldMilestone) {
                $oldMilestone->calculateAutoProgress();
            }
        }
        if ($newMilestoneId) {
            $newMilestone = \App\Models\ProjectMilestone::find($newMilestoneId);
            if ($newMilestone) {
                $newMilestone->calculateAutoProgress();
            }
        }

        // Build human-readable message
        $fromLabel = $oldMilestoneName ?? 'Belum Dikelompokkan';
        $toLabel = $newMilestoneName ?? 'Belum Dikelompokkan';
        $message = "Task berhasil dipindahkan dari \"{$fromLabel}\" ke \"{$toLabel}\".";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'task_id' => $task->id,
                'old_milestone_id' => $oldMilestoneId,
                'old_milestone_name' => $oldMilestoneName,
                'new_milestone_id' => $newMilestoneId,
                'new_milestone_name' => $newMilestoneName,
                'moved_from' => $fromLabel,
                'moved_to' => $toLabel,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Update task dates (for drag-drop in Gantt chart).
     */
    public function updateDates(Request $request, Task $task): JsonResponse
    {
        // Check permission using policy
        if (Gate::denies('update', $task)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk mengubah task ini.',
            ], 403);
        }

        $request->validate([
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
        ], [
            'due_date.after_or_equal' => 'Tanggal deadline harus setelah tanggal mulai.',
        ]);

        $oldStartDate = $task->start_date?->toDateString();
        $oldDueDate = $task->due_date?->toDateString();

        $task->update([
            'start_date' => $request->start_date,
            'due_date' => $request->due_date,
            'updated_by' => $this->user()->id,
        ]);

        // Log the activity
        TaskActivity::logDatesChanged($task, $this->user(), $oldStartDate, $oldDueDate, $request->start_date, $request->due_date);

        // Update parent project progress
        if ($task->project) {
            $task->project->updateProgress();
        }

        return response()->json([
            'success' => true,
            'message' => 'Tanggal berhasil diperbarui.',
            'data' => [
                'start_date' => $task->start_date?->toDateString(),
                'due_date' => $task->due_date?->toDateString(),
            ],
        ]);
    }

    /**
     * Bulk move tasks to a milestone.
     * ALL OR NOTHING - Uses DB Transaction
     */
    public function bulkMoveMilestone(Request $request): RedirectResponse|JsonResponse
    {
        // Check bulk permission
        if (Gate::denies('bulkMoveMilestone', new Task())) {
            if ($request->expectsJson()) {
                return $this->error('Anda tidak memiliki izin untuk memindahkan task secara massal.', 403);
            }
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk memindahkan task secara massal.');
        }

        $request->validate([
            'task_ids' => 'required|array|min:1',
            'task_ids.*' => 'integer|exists:tasks,id',
            'milestone_id' => 'nullable|integer|exists:project_milestones,id',
        ], [
            'task_ids.required' => 'Pilih minimal satu task.',
            'task_ids.*.exists' => 'Task tidak ditemukan.',
        ]);

        $taskIds = $request->task_ids;
        $newMilestoneId = $request->milestone_id;

        // Get target milestone project if moving to a milestone
        $targetProjectId = null;
        $targetMilestoneName = null;
        if ($newMilestoneId) {
            $targetMilestone = \App\Models\ProjectMilestone::find($newMilestoneId);
            $targetProjectId = $targetMilestone?->project_id;
            $targetMilestoneName = $targetMilestone?->name;
        }

        $tasks = Task::whereIn('id', $taskIds)->with('milestone')->get();

        // Pre-validate ALL tasks before starting transaction
        $validationErrors = [];
        foreach ($tasks as $task) {
            // Check project match
            if ($targetProjectId !== null && $task->project_id !== $targetProjectId) {
                $validationErrors[] = "Task '{$task->name}' tidak dapat dipindahkan ke project lain.";
                continue;
            }

            // Check permission
            if (Gate::denies('moveMilestone', $task)) {
                $validationErrors[] = "Anda tidak memiliki izin untuk memindahkan '{$task->name}'.";
                continue;
            }

            // Check if already in same milestone
            if ($task->milestone_id === $newMilestoneId) {
                continue; // Skip silently
            }
        }

        // If ANY validation fails, return ALL errors and do nothing
        if (count($validationErrors) > 0) {
            $message = 'Tidak dapat memindahkan task: ' . implode(' ', $validationErrors);
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'errors' => $validationErrors,
                ], 422);
            }
            return redirect()->back()->with('error', $message);
        }

        // Start transaction - ALL OR NOTHING
        try {
            \DB::beginTransaction();

            $movedCount = 0;
            $affectedMilestones = ['old' => [], 'new' => $newMilestoneId];

            foreach ($tasks as $task) {
                // Skip if already in same milestone
                if ($task->milestone_id === $newMilestoneId) {
                    continue;
                }

                $oldMilestoneId = $task->milestone_id;

                // Update task
                $task->update([
                    'milestone_id' => $newMilestoneId,
                    'updated_by' => $this->user()->id,
                ]);

                // Log activity with milestone names
                TaskActivity::logMilestoneChanged(
                    $task,
                    $this->user(),
                    $oldMilestoneId,
                    $newMilestoneId
                );

                // Track affected milestones
                if ($oldMilestoneId && !in_array($oldMilestoneId, $affectedMilestones['old'])) {
                    $affectedMilestones['old'][] = $oldMilestoneId;
                }
                $movedCount++;
            }

            // Update progress for all affected milestones
            foreach ($affectedMilestones['old'] as $oldMilestoneId) {
                $oldMilestone = \App\Models\ProjectMilestone::find($oldMilestoneId);
                if ($oldMilestone) {
                    $oldMilestone->calculateAutoProgress();
                }
            }
            if ($newMilestoneId) {
                $newMilestone = \App\Models\ProjectMilestone::find($newMilestoneId);
                if ($newMilestone) {
                    $newMilestone->calculateAutoProgress();
                }
            }

            // Update project progress
            $affectedProjectIds = $tasks->pluck('project_id')->unique();
            foreach ($affectedProjectIds as $projectId) {
                $project = Project::find($projectId);
                if ($project) {
                    $project->updateProgress();
                }
            }

            \DB::commit();

            $targetName = $newMilestoneId ? "\"{$targetMilestoneName}\"" : 'Tanpa Milestone';
            $message = "{$movedCount} task berhasil dipindahkan ke {$targetName}.";

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'moved_count' => $movedCount,
                    'affected_milestones' => $affectedMilestones,
                ]);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            \DB::rollBack();

            $message = 'Gagal memindahkan task. Tidak ada perubahan yang dilakukan.';
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'error' => $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->with('error', $message);
        }
    }

    /**
     * Display kanban view of tasks.
     */
    public function kanban(Request $request): View
    {
        $query = Task::query()
            ->with('project', 'assignees.user');

        // Apply unified permission filter (handles all scope logic)
        $query = $this->applyTaskPermissionFilter($query);

        // Filter by project
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $tasks = $query->get();

        $projects = Project::where('tenant_id', $this->tenantId())->orderBy('name')->get();
        $users = $this->getAvailableUsers();
        $canSeeAll = $this->canSeeAllTasks();
        $stats = $this->getTaskStats();

        return view('crm.tasks.kanban', compact('tasks', 'stats', 'projects', 'users', 'canSeeAll'));
    }

    /**
     * API endpoint for kanban board data.
     */
    public function kanbanData(Request $request): JsonResponse
    {
        $query = Task::query()
            ->with('project', 'assignees.user', 'checklists');

        // Apply unified permission filter (handles all scope logic)
        $query = $this->applyTaskPermissionFilter($query);

        // Filter by project
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $tasks = $query->get();

        // Group tasks by status
        $kanbanData = [
            'to_do' => $tasks->where('status', Task::STATUS_TO_DO)->values(),
            'in_progress' => $tasks->where('status', Task::STATUS_IN_PROGRESS)->values(),
            'waiting_approval' => $tasks->where('status', Task::STATUS_WAITING_APPROVAL)->values(),
            'completed' => $tasks->where('status', Task::STATUS_COMPLETED)->values(),
        ];

        return response()->json([
            'success' => true,
            'data' => $kanbanData,
        ]);
    }

    /**
     * Update task status via drag-drop in kanban.
     */
    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        // Check permission
        if (Gate::denies('view', $task)) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses ke task ini.'], 403);
        }

        $request->validate([
            'status' => 'required|in:to_do,in_progress,waiting_approval,completed,cancelled',
        ]);

        $newStatus = $request->status;
        $oldStatus = $task->status;

        // Check if status transition is valid
        if ($oldStatus === $newStatus) {
            return response()->json(['success' => true, 'message' => 'Status tidak berubah.']);
        }

        // Validate status transition based on workflow
        $validTransitions = [
            'to_do' => ['in_progress', 'cancelled'],
            'in_progress' => ['waiting_approval', 'to_do', 'cancelled'],
            'waiting_approval' => ['completed', 'in_progress'],
            'completed' => ['in_progress'], // Can only reopen
            'cancelled' => ['to_do'],
        ];

        if (!in_array($newStatus, $validTransitions[$oldStatus] ?? [])) {
            return response()->json([
                'success' => false,
                'message' => 'Transisi status tidak valid dari "' . Task::STATUS_LABELS[$oldStatus] . '" ke "' . Task::STATUS_LABELS[$newStatus] . '".',
            ], 422);
        }

        // Perform status change
        switch ($newStatus) {
            case 'to_do':
                $task->update(['status' => Task::STATUS_TO_DO]);
                TaskActivity::logStatusChanged($task, $this->user(), $oldStatus, $newStatus, 'Status dikembalikan ke To Do');
                break;
            case 'in_progress':
                if ($oldStatus === 'to_do') {
                    $task->start($this->user());
                } else {
                    $task->update(['status' => Task::STATUS_IN_PROGRESS]);
                    TaskActivity::logStatusChanged($task, $this->user(), $oldStatus, $newStatus, 'Task dimulai');
                }
                break;
            case 'waiting_approval':
                $task->markAsWaitingApproval($this->user());
                break;
            case 'completed':
                if ($this->user()->hasRole(['director', 'admin'])) {
                    $task->approve($this->user());
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Hanya Director/Admin yang dapat menyetujui task.',
                    ], 403);
                }
                break;
            case 'cancelled':
                $task->cancel($this->user());
                break;
        }

        return response()->json([
            'success' => true,
            'message' => 'Status berhasil diubah.',
            'task' => $task->fresh(),
        ]);
    }

    /**
     * Store a new checklist item.
     */
    public function storeChecklist(Request $request, Task $task): JsonResponse
    {
        if (Gate::denies('view', $task)) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses.'], 403);
        }

        $request->validate([
            'content' => 'required|string|max:500',
        ]);

        $checklist = \App\Models\TaskChecklist::create([
            'tenant_id' => $task->tenant_id,
            'task_id' => $task->id,
            'user_id' => $this->user()->id,
            'content' => $request->content,
            'is_checked' => false,
            'sort_order' => $task->checklists()->max('sort_order') + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Checklist berhasil ditambahkan.',
            'checklist' => $checklist,
        ]);
    }

    /**
     * Update a checklist item.
     */
    public function updateChecklist(Request $request, \App\Models\TaskChecklist $checklist): JsonResponse
    {
        if (Gate::denies('view', $checklist->task)) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses.'], 403);
        }

        $request->validate([
            'content' => 'nullable|string|max:500',
            'is_checked' => 'nullable|boolean',
        ]);

        $checklist->update($request->only(['content', 'is_checked']));

        return response()->json([
            'success' => true,
            'message' => 'Checklist berhasil diperbarui.',
            'checklist' => $checklist->fresh(),
        ]);
    }

    /**
     * Delete a checklist item.
     */
    public function deleteChecklist(Request $request, \App\Models\TaskChecklist $checklist): JsonResponse
    {
        if (Gate::denies('view', $checklist->task)) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses.'], 403);
        }

        $checklist->delete();

        return response()->json([
            'success' => true,
            'message' => 'Checklist berhasil dihapus.',
        ]);
    }

    /**
     * Toggle checklist item checked status.
     */
    public function toggleChecklist(Request $request, \App\Models\TaskChecklist $checklist): JsonResponse
    {
        if (Gate::denies('view', $checklist->task)) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses.'], 403);
        }

        $checklist->update([
            'is_checked' => !$checklist->is_checked,
        ]);

        return response()->json([
            'success' => true,
            'message' => $checklist->is_checked ? 'Item ditandai selesai.' : 'Item ditandai belum selesai.',
            'checklist' => $checklist->fresh(),
        ]);
    }

    /**
     * Reorder checklists.
     */
    public function reorderChecklists(Request $request, Task $task): JsonResponse
    {
        if (Gate::denies('view', $task)) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses.'], 403);
        }

        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:task_checklists,id',
        ]);

        foreach ($request->order as $index => $id) {
            \App\Models\TaskChecklist::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Urutan checklist berhasil diperbarui.',
        ]);
    }
}
