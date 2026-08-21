<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetActivityController extends Controller
{
    /**
     * Store a new allocation activity.
     *
     * POST /api/assets/{asset}/activities/allocation
     *
     * Body:
     * - received_by: string (required) - Nama penerima
     * - location: string (required) - Lokasi penerimaan
     * - sent_by: string (optional) - Nama pengirim
     * - sent_date: date (optional) - Tanggal dikirim
     * - received_date: date (optional) - Tanggal diterima
     * - notes: string (optional) - Catatan
     */
    public function storeAllocation(Request $request, Asset $asset): JsonResponse
    {
        $validated = $request->validate([
            'received_by' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'sent_by' => 'nullable|string|max:255',
            'sent_date' => 'nullable|date',
            'received_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        try {
            // Check if asset can have activities
            if (!$asset->canHaveActivities()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Asset harus berstatus Available untuk dialokasikan.',
                ], 422);
            }

            // Check if already has active allocation
            if ($asset->current_allocation_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Asset sudah memiliki alokasi aktif. Selesaikan alokasi sebelumnya terlebih dahulu.',
                ], 422);
            }

            $activity = $asset->createAllocation($validated);

            return response()->json([
                'success' => true,
                'message' => 'Alokasi berhasil dibuat.',
                'data' => [
                    'activity' => $activity,
                    'asset' => $asset->fresh(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Complete/cancel an allocation.
     *
     * PATCH /api/assets/{asset}/activities/{activity}/complete-allocation
     */
    public function completeAllocation(Asset $asset, AssetActivity $activity): JsonResponse
    {
        // Verify this is the correct activity for this asset
        if ($activity->asset_id !== $asset->id || $activity->activity_type !== AssetActivity::TYPE_ALLOCATION) {
            return response()->json([
                'success' => false,
                'message' => 'Aktivitas tidak valid.',
            ], 422);
        }

        if (!$activity->canModify()) {
            return response()->json([
                'success' => false,
                'message' => 'Alokasi tidak dapat diselesaikan.',
            ], 422);
        }

        $asset->completeAllocation();

        return response()->json([
            'success' => true,
            'message' => 'Alokasi berhasil diselesaikan.',
            'data' => [
                'activity' => $activity->fresh(),
                'asset' => $asset->fresh(),
            ],
        ]);
    }

    /**
     * Store a new reservation activity.
     *
     * POST /api/assets/{asset}/activities/reservation
     *
     * Body:
     * - used_by: string (required) - Siapa yang menggunakan
     * - installation_location: string (required) - Lokasi pemasangan
     * - department: string (optional) - Departemen
     * - start_date: date (optional) - Tanggal mulai
     * - end_date: date (optional) - Tanggal selesai
     * - notes: string (optional) - Catatan
     */
    public function storeReservation(Request $request, Asset $asset): JsonResponse
    {
        $validated = $request->validate([
            'used_by' => 'required|string|max:255',
            'installation_location' => 'required|string|max:255',
            'department' => 'nullable|string|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        try {
            // Check if asset can have activities
            if (!$asset->canHaveActivities()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Asset harus berstatus Available untuk direservasi.',
                ], 422);
            }

            // Check if already has active reservation
            if ($asset->current_reservation_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Asset sudah memiliki reservasi aktif. Selesaikan reservasi sebelumnya terlebih dahulu.',
                ], 422);
            }

            $activity = $asset->createReservation($validated);

            return response()->json([
                'success' => true,
                'message' => 'Reservasi berhasil dibuat.',
                'data' => [
                    'activity' => $activity,
                    'asset' => $asset->fresh(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Complete/cancel a reservation.
     *
     * PATCH /api/assets/{asset}/activities/{activity}/complete-reservation
     */
    public function completeReservation(Asset $asset, AssetActivity $activity): JsonResponse
    {
        // Verify this is the correct activity for this asset
        if ($activity->asset_id !== $asset->id || $activity->activity_type !== AssetActivity::TYPE_RESERVATION) {
            return response()->json([
                'success' => false,
                'message' => 'Aktivitas tidak valid.',
            ], 422);
        }

        if (!$activity->canModify()) {
            return response()->json([
                'success' => false,
                'message' => 'Reservasi tidak dapat diselesaikan.',
            ], 422);
        }

        $asset->completeReservation();

        return response()->json([
            'success' => true,
            'message' => 'Reservasi berhasil diselesaikan.',
            'data' => [
                'activity' => $activity->fresh(),
                'asset' => $asset->fresh(),
            ],
        ]);
    }

    /**
     * Store a new maintenance activity.
     *
     * POST /api/assets/{asset}/activities/maintenance
     *
     * Body:
     * - maintenance_type: string (required) - preventive, corrective, inspection, upgrade
     * - technician: string (optional) - Nama teknisi
     * - start_date: date (optional) - Tanggal mulai
     * - end_date: date (optional) - Tanggal selesai/perkiraan
     * - cost: numeric (optional) - Biaya maintenance
     * - notes: string (optional) - Catatan
     */
    public function storeMaintenance(Request $request, Asset $asset): JsonResponse
    {
        $validated = $request->validate([
            'maintenance_type' => 'required|in:preventive,corrective,inspection,upgrade',
            'technician' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            // Check if already in maintenance
            if ($asset->current_maintenance_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Asset sedang dalam maintenance. Selesaikan maintenance sebelumnya terlebih dahulu.',
                ], 422);
            }

            // In the new multi-status model, maintenance can run alongside allocation
            // No need to check for available status

            $activity = $asset->createMaintenance($validated);

            return response()->json([
                'success' => true,
                'message' => 'Maintenance berhasil dijadwalkan. Asset sekarang memiliki status Maintenance.',
                'data' => [
                    'activity' => $activity,
                    'asset' => $asset->fresh(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Start maintenance.
     *
     * PATCH /api/assets/{asset}/activities/{activity}/start-maintenance
     */
    public function startMaintenance(Asset $asset, AssetActivity $activity): JsonResponse
    {
        // Verify this is the correct activity for this asset
        if ($activity->asset_id !== $asset->id || $activity->activity_type !== AssetActivity::TYPE_MAINTENANCE) {
            return response()->json([
                'success' => false,
                'message' => 'Aktivitas tidak valid.',
            ], 422);
        }

        if ($activity->status !== AssetActivity::STATUS_SCHEDULED) {
            return response()->json([
                'success' => false,
                'message' => 'Maintenance tidak dapat dimulai.',
            ], 422);
        }

        $activity->update(['status' => AssetActivity::STATUS_IN_PROGRESS]);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance dimulai.',
            'data' => [
                'activity' => $activity->fresh(),
                'asset' => $asset->fresh(),
            ],
        ]);
    }

    /**
     * Complete maintenance.
     *
     * PATCH /api/assets/{asset}/activities/{activity}/complete-maintenance
     */
    public function completeMaintenance(Asset $asset, AssetActivity $activity): JsonResponse
    {
        // Verify this is the correct activity for this asset
        if ($activity->asset_id !== $asset->id || $activity->activity_type !== AssetActivity::TYPE_MAINTENANCE) {
            return response()->json([
                'success' => false,
                'message' => 'Aktivitas tidak valid.',
            ], 422);
        }

        if ($activity->isCompleted || $activity->isCancelled) {
            return response()->json([
                'success' => false,
                'message' => 'Maintenance sudah selesai atau dibatalkan.',
            ], 422);
        }

        $asset->completeMaintenance();

        return response()->json([
            'success' => true,
            'message' => 'Maintenance selesai. Asset kembali ke status Available.',
            'data' => [
                'activity' => $activity->fresh(),
                'asset' => $asset->fresh(),
            ],
        ]);
    }

    /**
     * Cancel an activity.
     *
     * PATCH /api/assets/{asset}/activities/{activity}/cancel
     */
    public function cancel(Asset $asset, AssetActivity $activity, Request $request): JsonResponse
    {
        // Verify this is the correct activity for this asset
        if ($activity->asset_id !== $asset->id) {
            return response()->json([
                'success' => false,
                'message' => 'Aktivitas tidak valid.',
            ], 422);
        }

        if (!$activity->canModify()) {
            return response()->json([
                'success' => false,
                'message' => 'Aktivitas tidak dapat dibatalkan.',
            ], 422);
        }

        $reason = $request->input('reason');

        // If cancelling maintenance, only clear the reference - don't change base status
        if ($activity->activity_type === AssetActivity::TYPE_MAINTENANCE) {
            $activity->cancel($reason);
            $asset->update([
                'current_maintenance_id' => null,
            ]);
        }
        // If cancelling allocation
        elseif ($activity->activity_type === AssetActivity::TYPE_ALLOCATION) {
            $activity->cancel($reason);
            $asset->update(['current_allocation_id' => null]);
        }
        // If cancelling reservation
        elseif ($activity->activity_type === AssetActivity::TYPE_RESERVATION) {
            $activity->cancel($reason);
            $asset->update(['current_reservation_id' => null]);
        }
        // For other activities
        else {
            $activity->cancel($reason);
        }

        return response()->json([
            'success' => true,
            'message' => 'Aktivitas berhasil dibatalkan.',
            'data' => [
                'activity' => $activity->fresh(),
                'asset' => $asset->fresh(),
            ],
        ]);
    }

    /**
     * Get asset activities timeline.
     *
     * GET /api/assets/{asset}/activities
     */
    public function index(Asset $asset): JsonResponse
    {
        $activities = $asset->activities()
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }

    /**
     * Get active activities for an asset.
     *
     * GET /api/assets/{asset}/activities/active
     */
    public function active(Asset $asset): JsonResponse
    {
        $allocation = $asset->current_allocation_id
            ? $asset->activities()->find($asset->current_allocation_id)
            : null;

        $reservation = $asset->current_reservation_id
            ? $asset->activities()->find($asset->current_reservation_id)
            : null;

        $maintenance = $asset->current_maintenance_id
            ? $asset->activities()->find($asset->current_maintenance_id)
            : null;

        return response()->json([
            'success' => true,
            'data' => [
                'allocation' => $allocation,
                'reservation' => $reservation,
                'maintenance' => $maintenance,
            ],
        ]);
    }
}
