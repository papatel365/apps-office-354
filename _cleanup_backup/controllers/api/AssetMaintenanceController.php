<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssetMaintenance;
use Illuminate\Http\Request;

class AssetMaintenanceController extends Controller
{
    public function index(Request $request, $assetId)
    {
        $maintenances = AssetMaintenance::where('asset_id', $assetId)
            ->with('performedBy')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $maintenances]);
    }

    public function store(Request $request, $assetId)
    {
        $request->validate([
            'maintenance_type' => 'required|string|max:100',
            'description' => 'required|string',
            'cost' => 'nullable|numeric|min:0',
            'scheduled_date' => 'required|date',
        ]);

        $maintenance = AssetMaintenance::create([
            'asset_id' => $assetId,
            'performed_by' => auth()->id(),
            'maintenance_type' => $request->maintenance_type,
            'description' => $request->description,
            'cost' => $request->cost ?? 0,
            'scheduled_date' => $request->scheduled_date,
            'status' => 'scheduled',
        ]);

        return response()->json(['data' => $maintenance, 'message' => 'Maintenance scheduled successfully'], 201);
    }

    public function start($assetId, $id)
    {
        $maintenance = AssetMaintenance::where('asset_id', $assetId)->findOrFail($id);
        $maintenance->update(['status' => 'in_progress', 'started_at' => now()]);

        return response()->json(['data' => $maintenance, 'message' => 'Maintenance started']);
    }

    public function complete($assetId, $id)
    {
        $maintenance = AssetMaintenance::where('asset_id', $assetId)->findOrFail($id);
        $maintenance->update(['status' => 'completed', 'completed_at' => now()]);

        return response()->json(['data' => $maintenance, 'message' => 'Maintenance completed']);
    }

    public function destroy($assetId, $id)
    {
        $maintenance = AssetMaintenance::where('asset_id', $assetId)->findOrFail($id);
        $maintenance->delete();

        return response()->json(['message' => 'Maintenance deleted successfully']);
    }
}
