<?php

namespace App\Repositories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectRepository extends BaseRepository
{
    protected Project $model;

    public function __construct(Project $model)
    {
        $this->model = $model;
    }

    /**
     * Get paginated projects with filters.
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->with(['client', 'manager']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->byPriority($filters['priority']);
        }

        if (!empty($filters['client_id'])) {
            $query->byClient($filters['client_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get project with tasks summary.
     */
    public function withTasksSummary(int $id): ?Project
    {
        $project = $this->model->with(['client', 'manager', 'members.user'])->find($id);

        if ($project) {
            $tasks = $project->tasks;
            $project->tasks_count = $tasks->count();
            $project->completed_tasks_count = $tasks->where('status', 'completed')->count();
            $project->overdue_tasks_count = $tasks->filter(fn($t) => $t->is_overdue)->count();
        }

        return $project;
    }

    /**
     * Update project progress.
     */
    public function updateProgress(Project $project): Project
    {
        $project->updateProgress();
        return $project->fresh();
    }

    /**
     * Get projects due soon.
     */
    public function dueSoon(int $days = 7): Collection
    {
        return $this->model->dueSoon($days)->get();
    }

    /**
     * Get overdue projects.
     */
    public function overdue(): Collection
    {
        return $this->model->overdue()->get();
    }

    /**
     * Get my projects.
     */
    public function myProjects(int $userId): Collection
    {
        return $this->model->whereHas('members', fn($q) => $q->where('user_id', $userId))->get();
    }

    /**
     * Get summary statistics.
     */
    public function summary(): array
    {
        return [
            'total' => $this->model->count(),
            'active' => $this->model->active()->count(),
            'completed' => $this->model->completed()->count(),
            'overdue' => $this->model->overdue()->count(),
            'due_soon' => $this->model->dueSoon(7)->count(),
        ];
    }
}
