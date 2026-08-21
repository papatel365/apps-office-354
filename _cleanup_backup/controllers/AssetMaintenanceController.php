<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetMaintenanceController extends Controller
{
    /**
     * Store a newly created maintenance record.
     */
    public function store(Request $request, Asset $asset): JsonResponse
    {
        $request->validate([
            'maintenance_type' => 'required|in:preventive,corrective,inspection,upgrade',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_date' => 'required|date',
            'performed_by' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $maintenance = AssetMaintenance::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $this->tenantId(),
            'asset_id' => $asset->id,
            'maintenance_type' => $request->maintenance_type,
            'title' => $request->title,
            'description' => $request->description,
            'scheduled_date' => $request->scheduled_date,
            'performed_by' => $request->performed_by,
            'cost' => $request->cost ?? 0,
            'notes' => $request->notes,
            'status' => AssetMaintenance::STATUS_SCHEDULED,
            'created_by' => $this->user()->id,
        ]);

        // Mark asset as under maintenance
        $asset->markAsMaintenance();

        return $this->success($maintenance, 'Maintenance scheduled successfully', 201);
    }

    /**
     * Update the specified maintenance.
     */
    public function update(Request $request, Asset $asset, AssetMaintenance $maintenance): JsonResponse
    {
        $request->validate([
            'maintenance_type' => 'nullable|in:preventive,corrective,inspection,upgrade',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'scheduled_date' => 'nullable|date',
            'performed_by' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $maintenance->update($request->only([
            'maintenance_type', 'title', 'description', 'scheduled_date', 'performed_by', 'cost', 'notes'
        ]));

        return $this->success($maintenance, 'Maintenance updated successfully');
    }

    /**
     * Start maintenance.
     */
    public function start(Asset $asset, AssetMaintenance $maintenance): JsonResponse
    {
        $maintenance->start();

        return $this->success($maintenance, 'Maintenance started');
    }

    /**
     * Complete maintenance.
     */
    public function complete(Request $request, Asset $asset, AssetMaintenance $maintenance): JsonResponse
    {
        $maintenance->complete();

        return $this->success($maintenance, 'Maintenance completed');
    }

    /**
     * Cancel maintenance.
     */
    public function destroy(Asset $asset, AssetMaintenance $maintenance): JsonResponse
    {
        if ($maintenance->isCompleted()) {
            return $this->error('Cannot cancel completed maintenance', 400);
        }

        $maintenance->cancel();

        // Check if there are other active maintenance records
        $activeMaintenance = AssetMaintenance::where('asset_id', $asset->id)
            ->whereIn('status', [AssetMaintenance::STATUS_SCHEDULED, AssetMaintenance::STATUS_IN_PROGRESS])
            ->where('id', '!=', $maintenance->id)
            ->count();

        if ($activeMaintenance === 0) {
            $asset->update(['status' => Asset::STATUS_AVAILABLE]);
        }

        return $this->success(null, 'Maintenance cancelled');
    }
}
