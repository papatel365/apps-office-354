<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssetCheckinCheckout;
use Illuminate\Http\Request;

class AssetCheckinCheckoutController extends Controller
{
    public function index(Request $request, $assetId)
    {
        $records = AssetCheckinCheckout::where('asset_id', $assetId)
            ->with('user', 'checkedBy')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $records]);
    }

    public function store(Request $request, $assetId)
    {
        $request->validate([
            'type' => 'required|in:checkin,checkout',
            'notes' => 'nullable|string',
        ]);

        $record = AssetCheckinCheckout::create([
            'asset_id' => $assetId,
            'user_id' => auth()->id(),
            'type' => $request->type,
            'notes' => $request->notes,
        ]);

        return response()->json(['data' => $record->load('user'), 'message' => 'Record created successfully'], 201);
    }

    public function destroy($assetId, $id)
    {
        $record = AssetCheckinCheckout::where('asset_id', $assetId)->findOrFail($id);
        $record->delete();

        return response()->json(['message' => 'Record deleted successfully']);
    }
}
