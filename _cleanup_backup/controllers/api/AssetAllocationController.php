<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssetAllocation;
use Illuminate\Http\Request;

class AssetAllocationController extends Controller
{
    public function index(Request $request, $assetId)
    {
        $allocations = AssetAllocation::where('asset_id', $assetId)->with('user')->get();
        return response()->json(['data' => $allocations]);
    }

    public function store(Request $request, $assetId)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $allocation = AssetAllocation::create([
            'asset_id' => $assetId,
            'user_id' => $request->user_id,
            'assigned_by' => auth()->id(),
            'notes' => $request->notes,
        ]);

        return response()->json(['data' => $allocation->load('user'), 'message' => 'Asset allocated successfully'], 201);
    }

    public function update(Request $request, $assetId, $id)
    {
        $allocation = AssetAllocation::where('asset_id', $assetId)->findOrFail($id);
        $allocation->update($request->only(['notes']));

        return response()->json(['data' => $allocation, 'message' => 'Allocation updated successfully']);
    }

    public function destroy($assetId, $id)
    {
        $allocation = AssetAllocation::where('asset_id', $assetId)->findOrFail($id);
        $allocation->delete();

        return response()->json(['message' => 'Allocation deleted successfully']);
    }

    public function return($assetId, $id)
    {
        $allocation = AssetAllocation::where('asset_id', $assetId)->findOrFail($id);
        $allocation->update(['returned_at' => now()]);

        return response()->json(['data' => $allocation, 'message' => 'Asset returned successfully']);
    }
}
