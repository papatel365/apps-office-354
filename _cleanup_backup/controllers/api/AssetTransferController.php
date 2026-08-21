<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssetTransfer;
use Illuminate\Http\Request;

class AssetTransferController extends Controller
{
    public function index(Request $request, $assetId)
    {
        $transfers = AssetTransfer::where('asset_id', $assetId)
            ->with('fromUser', 'toUser', 'approvedBy')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $transfers]);
    }

    public function store(Request $request, $assetId)
    {
        $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $transfer = AssetTransfer::create([
            'asset_id' => $assetId,
            'from_user_id' => auth()->id(),
            'to_user_id' => $request->to_user_id,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);

        return response()->json(['data' => $transfer->load('toUser'), 'message' => 'Transfer requested successfully'], 201);
    }

    public function approve($assetId, $id)
    {
        $transfer = AssetTransfer::where('asset_id', $assetId)->findOrFail($id);
        $transfer->update(['status' => 'approved', 'approved_by' => auth()->id()]);

        return response()->json(['data' => $transfer, 'message' => 'Transfer approved']);
    }

    public function complete($assetId, $id)
    {
        $transfer = AssetTransfer::where('asset_id', $assetId)->findOrFail($id);
        $transfer->update(['status' => 'completed', 'completed_at' => now()]);

        return response()->json(['data' => $transfer, 'message' => 'Transfer completed']);
    }

    public function destroy($assetId, $id)
    {
        $transfer = AssetTransfer::where('asset_id', $assetId)->findOrFail($id);
        $transfer->delete();

        return response()->json(['message' => 'Transfer deleted successfully']);
    }
}
