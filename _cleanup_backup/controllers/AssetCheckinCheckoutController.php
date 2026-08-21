<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetCheckinCheckout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetCheckinCheckoutController extends Controller
{
    /**
     * Record check-out.
     */
    public function store(Request $request, Asset $asset): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'condition' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'location' => 'nullable|string|max:255',
        ]);

        $user = \App\Modules\System\Models\User::findOrFail($request->user_id);

        $record = AssetCheckinCheckout::recordCheckout($asset, $user, [
            'condition' => $request->condition,
            'notes' => $request->notes,
            'location' => $request->location,
        ]);

        return $this->success($record, 'Asset checked out successfully', 201);
    }

    /**
     * Record check-in.
     */
    public function update(Request $request, Asset $asset, AssetCheckinCheckout $checkinCheckout): JsonResponse
    {
        $request->validate([
            'condition' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $checkinCheckout->update([
            'checkin_date' => now(),
            'action' => 'check_in',
            'checkin_condition' => $request->condition,
            'checkin_notes' => $request->notes,
        ]);

        // Update asset status to available
        $asset->update(['status' => Asset::STATUS_AVAILABLE]);

        return $this->success($checkinCheckout, 'Asset checked in successfully');
    }
}
