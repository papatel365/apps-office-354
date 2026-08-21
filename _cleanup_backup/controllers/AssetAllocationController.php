<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAllocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetAllocationController extends Controller
{
    /**
     * Store a newly created allocation.
     */
    public function store(Request $request, Asset $asset): JsonResponse
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'client_id' => 'nullable|exists:clients,id',
            'return_due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $allocation = AssetAllocation::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $this->tenantId(),
            'asset_id' => $asset->id,
            'user_id' => $request->user_id,
            'client_id' => $request->client_id,
            'allocated_by' => $this->user()->id,
            'allocation_date' => now(),
            'return_due_date' => $request->return_due_date,
            'notes' => $request->notes,
            'status' => AssetAllocation::STATUS_ACTIVE,
        ]);

        $asset->update(['status' => Asset::STATUS_ASSIGNED]);

        return $this->success($allocation, 'Asset allocated successfully', 201);
    }

    /**
     * Update the specified allocation.
     */
    public function update(Request $request, Asset $asset, AssetAllocation $allocation): JsonResponse
    {
        $request->validate([
            'return_due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $allocation->update($request->only(['return_due_date', 'notes']));

        return $this->success($allocation, 'Allocation updated successfully');
    }

    /**
     * Return asset.
     */
    public function return(Asset $asset, AssetAllocation $allocation): JsonResponse
    {
        $allocation->return();

        return $this->success($allocation, 'Asset returned successfully');
    }

    /**
     * Remove the specified allocation.
     */
    public function destroy(Asset $asset, AssetAllocation $allocation): JsonResponse
    {
        if ($allocation->status === AssetAllocation::STATUS_ACTIVE) {
            return $this->error('Please return the asset first', 400);
        }

        $allocation->delete();

        return $this->success(null, 'Allocation deleted successfully');
    }
}
