<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssetReservation;
use Illuminate\Http\Request;

class AssetReservationController extends Controller
{
    public function index(Request $request, $assetId)
    {
        $reservations = AssetReservation::where('asset_id', $assetId)
            ->with('user')
            ->orderBy('start_date')
            ->get();

        return response()->json(['data' => $reservations]);
    }

    public function store(Request $request, $assetId)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'notes' => 'nullable|string',
        ]);

        $reservation = AssetReservation::create([
            'asset_id' => $assetId,
            'user_id' => $request->user_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);

        return response()->json(['data' => $reservation->load('user'), 'message' => 'Reservation created successfully'], 201);
    }

    public function approve($assetId, $id)
    {
        $reservation = AssetReservation::where('asset_id', $assetId)->findOrFail($id);
        $reservation->update(['status' => 'approved']);

        return response()->json(['data' => $reservation, 'message' => 'Reservation approved']);
    }

    public function reject($assetId, $id)
    {
        $reservation = AssetReservation::where('asset_id', $assetId)->findOrFail($id);
        $reservation->update(['status' => 'rejected']);

        return response()->json(['data' => $reservation, 'message' => 'Reservation rejected']);
    }

    public function destroy($assetId, $id)
    {
        $reservation = AssetReservation::where('asset_id', $assetId)->findOrFail($id);
        $reservation->delete();

        return response()->json(['message' => 'Reservation deleted successfully']);
    }
}
