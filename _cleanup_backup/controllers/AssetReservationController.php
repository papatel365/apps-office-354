<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetReservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetReservationController extends Controller
{
    /**
     * Store a newly created reservation.
     */
    public function store(Request $request, Asset $asset): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'purpose' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $user = \App\Modules\System\Models\User::findOrFail($request->user_id);

        $reservation = AssetReservation::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $this->tenantId(),
            'asset_id' => $asset->id,
            'user_id' => $user->id,
            'reservation_number' => AssetReservation::generateNumber(),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'purpose' => $request->purpose,
            'notes' => $request->notes,
            'status' => AssetReservation::STATUS_PENDING,
        ]);

        return $this->success($reservation, 'Reservation created successfully', 201);
    }

    /**
     * Approve reservation.
     */
    public function approve(Asset $asset, AssetReservation $reservation): JsonResponse
    {
        $reservation->approve();

        return $this->success($reservation, 'Reservation approved');
    }

    /**
     * Reject reservation.
     */
    public function reject(Asset $asset, AssetReservation $reservation): JsonResponse
    {
        $reservation->reject();

        return $this->success($reservation, 'Reservation rejected');
    }

    /**
     * Cancel reservation.
     */
    public function destroy(Asset $asset, AssetReservation $reservation): JsonResponse
    {
        $reservation->cancel();

        return $this->success(null, 'Reservation cancelled');
    }
}
