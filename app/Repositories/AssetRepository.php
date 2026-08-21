<?php

namespace App\Repositories;

use App\Models\Asset;
use App\Modules\System\Models\User;
use Illuminate\Database\Eloquent\Collection;

class AssetRepository extends BaseRepository
{
    protected Asset $model;

    public function __construct(Asset $model)
    {
        $this->model = $model;
    }

    /**
     * Get paginated assets with filters.
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 20)
    {
        $query = $this->model->with(['category']);

        if (!empty($filters['category_id'])) {
            $query->byCategory($filters['category_id']);
        }

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (!empty($filters['location'])) {
            $query->byLocation($filters['location']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Assign asset to user.
     */
    public function assignToUser(Asset $asset, int $userId, ?string $notes = null): Asset
    {
        $user = User::findOrFail($userId);
        $asset->assignTo($user, $notes);
        return $asset->fresh();
    }

    /**
     * Unassign asset.
     */
    public function unassign(Asset $asset): Asset
    {
        $asset->unassign();
        return $asset->fresh();
    }

    /**
     * Record check in/out.
     */
    public function recordCheckInOut(Asset $asset, int $userId, string $action, array $data = []): Asset
    {
        $user = User::findOrFail($userId);

        if ($action === 'check_out') {
            \App\Models\AssetCheckinCheckout::recordCheckout($asset, $user, $data);
        $asset->update(['status' => Asset::STATUS_ASSIGNED]);
        $asset->fresh();
        $asset->assignTo($user);
        return $asset;
        }

        \App\Models\AssetCheckinCheckout::recordCheckin($asset, $user, $data);
        $asset->update(['status' => Asset::STATUS_AVAILABLE]);
        return $asset->fresh();
    }

    /**
     * Schedule maintenance.
     */
    public function scheduleMaintenance(Asset $asset, array $data): Asset
    {
        \App\Models\AssetMaintenance::create(array_merge($data, [
            'uuid' => \Illuminate\Support\Str::uuid(),
            'tenant_id' => $asset->tenant_id,
            'asset_id' => $asset->id,
            'created_by' => auth()->id(),
        ]));

        $asset->markAsMaintenance();
        return $asset->fresh();
    }

    /**
     * Get assets by location.
     */
    public function byLocation(): Collection
    {
        return $this->model->whereNotNull('location')
            ->groupBy('location')
            ->selectRaw('location, COUNT(*) as count, SUM(current_value) as total_value')
            ->get();
    }

    /**
     * Get depreciation report.
     */
    public function depreciationReport(): Collection
    {
        return $this->model->where('purchase_cost', '>', 0)
            ->get()
            ->map(fn($asset) => [
                'id' => $asset->id,
                'name' => $asset->name,
                'purchase_cost' => $asset->purchase_cost,
                'current_value' => $asset->calculateCurrentValue(),
                'depreciation' => $asset->purchase_cost - $asset->calculateCurrentValue(),
            ]);
    }

    /**
     * Get summary statistics.
     */
    public function summary(): array
    {
        return [
            'total' => $this->model->count(),
            'available' => $this->model->available()->count(),
            'assigned' => $this->model->assigned()->count(),
            'maintenance' => $this->model->inMaintenance()->count(),
            'warranty_expiring' => $this->model->warrantyExpiringSoon(30)->count(),
            'total_value' => $this->model->sum('current_value'),
        ];
    }
}
