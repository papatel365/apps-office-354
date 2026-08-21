<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $assets = Asset::query()
            ->with('category')
            ->when($request->category_id, fn($q) => $q->byCategory($request->category_id))
            ->when($request->status, fn($q) => $q->byStatus($request->status))
            ->orderBy('name')
            ->paginate($request->per_page ?? 20);

        return $this->paginated($assets);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'category_id' => 'required|exists:asset_categories,id',
            'name' => 'required|string|max:255',
            'serial_number' => 'nullable|string|max:100',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|in:available,assigned,maintenance,reserved,retired,lost,stolen',
        ]);

        $asset = Asset::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $this->tenantId(),
            'asset_number' => Asset::generateNumber(),
            'created_by' => $this->user()->id,
            ...$request->validated(),
        ]);

        return $this->success($asset, 'Asset created', 201);
    }

    public function show(Asset $asset): JsonResponse
    {
        $asset->load('category', 'currentAllocation.user');
        return $this->success($asset);
    }

    public function update(Request $request, Asset $asset): JsonResponse
    {
        $asset->update($request->validated());
        return $this->success($asset, 'Asset updated');
    }

    public function destroy(Asset $asset): JsonResponse
    {
        $asset->delete();
        return $this->success(null, 'Asset deleted');
    }

    public function assign(Request $request, Asset $asset): JsonResponse
    {
        $request->validate(['user_id' => 'required|exists:users,id']);
        $asset->assignTo(\App\Modules\System\Models\User::find($request->user_id));
        return $this->success($asset, 'Asset assigned');
    }

    public function unassign(Asset $asset): JsonResponse
    {
        $asset->unassign();
        return $this->success($asset, 'Asset unassigned');
    }

    public function markAsMaintenance(Asset $asset): JsonResponse
    {
        $asset->markAsMaintenance();
        return $this->success($asset, 'Asset marked as under maintenance');
    }
}
