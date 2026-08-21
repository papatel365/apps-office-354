<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AssetActivityController extends Controller
{
    /**
     * Get timeline for an asset.
     */
    public function timeline(Asset $asset): JsonResponse
    {
        $activities = $asset->activities()
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->success([
            'activities' => $activities,
            'stats' => [
                'total' => $asset->activities()->count(),
                'allocation' => $asset->activities()->allocations()->count(),
                'check_out' => $asset->activities()->checkOuts()->count(),
                'reservation' => $asset->activities()->reservations()->count(),
                'check_in' => $asset->activities()->checkIns()->count(),
                'transfer' => $asset->activities()->transfers()->count(),
                'recall' => $asset->activities()->recalls()->count(),
                'maintenance' => $asset->activities()->maintenance()->count(),
            ],
        ]);
    }

    /**
     * Create an allocation activity.
     */
    public function allocate(Request $request, Asset $asset): JsonResponse
    {
        $request->validate([
            'to_user_id' => 'nullable|exists:users,id',
            'to_department_id' => 'nullable|exists:divisions,id',
            'to_location' => 'nullable|string|max:255',
            'from_location' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        try {
            // Check if already has active allocation
            if ($asset->current_allocation_id) {
                throw new \Exception('Asset sudah memiliki alokasi aktif. Selesaikan alokasi sebelumnya terlebih dahulu.');
            }

            // Check for conflicting activities - reservation blocks allocation
            // Maintenance CAN coexist with allocation
            if ($asset->current_reservation_id) {
                throw new \Exception('Asset sedang dalam reservasi. Selesaikan reservasi terlebih dahulu.');
            }

            $activity = $asset->createAllocation($request->all());

            Log::info('[Asset Allocation]', [
                'asset_id' => $asset->id,
                'activity_id' => $activity->id,
                'to_user_id' => $request->to_user_id,
                'user_id' => auth()->id(),
            ]);

            return $this->success($activity->load('creator'), 'Asset dialokasikan. Sekarang memiliki status Tersedia + Dialokasikan.', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Complete an allocation - removes ONLY the allocation status.
     * Preserves all other active statuses.
     */
    public function completeAllocation(Asset $asset): JsonResponse
    {
        try {
            $asset->completeAllocation();

            Log::info('[Asset Allocation Completed]', [
                'asset_id' => $asset->id,
                'user_id' => auth()->id(),
            ]);

            return $this->success(null, 'Alokasi selesai. Status Dialokasikan dihapus. Status lain tetap ada.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Create a check-out activity.
     */
    public function checkOut(Request $request, Asset $asset): JsonResponse
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'from_location' => 'nullable|string|max:255',
            'condition' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        try {
            $activity = $asset->createCheckOut($request->all());

            Log::info('[Asset Check Out]', [
                'asset_id' => $asset->id,
                'activity_id' => $activity->id,
                'user_id' => auth()->id(),
            ]);

            return $this->success($activity, 'Asset checked out successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Create a reservation activity.
     */
    public function reserve(Request $request, Asset $asset): JsonResponse
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'purpose' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        try {
            // Check if already has active reservation
            if ($asset->current_reservation_id) {
                throw new \Exception('Asset sudah memiliki reservasi aktif. Selesaikan reservasi sebelumnya terlebih dahulu.');
            }

            // Check for conflicting activities - allocation blocks reservation
            // Maintenance CAN coexist with reservation
            if ($asset->current_allocation_id) {
                throw new \Exception('Asset sedang dalam alokasi. Selesaikan alokasi terlebih dahulu.');
            }

            $activity = $asset->createReservation($request->all());

            Log::info('[Asset Reservation]', [
                'asset_id' => $asset->id,
                'activity_id' => $activity->id,
                'user_id' => $request->user_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);

            return $this->success($activity->load('creator'), 'Asset direservasi. Sekarang memiliki status Tersedia + Direservasi.', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Complete a reservation - removes ONLY the reservation status.
     * Preserves all other active statuses.
     */
    public function completeReservation(Asset $asset): JsonResponse
    {
        try {
            $asset->completeReservation();

            Log::info('[Asset Reservation Completed]', [
                'asset_id' => $asset->id,
                'user_id' => auth()->id(),
            ]);

            return $this->success(null, 'Reservasi selesai. Status Direservasi dihapus. Status lain tetap ada.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Create a check-in activity.
     */
    public function checkIn(Request $request, Asset $asset): JsonResponse
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'to_location' => 'nullable|string|max:255',
            'condition' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        try {
            $activity = $asset->createCheckIn($request->all());

            Log::info('[Asset Check In]', [
                'asset_id' => $asset->id,
                'activity_id' => $activity->id,
                'user_id' => auth()->id(),
            ]);

            return $this->success($activity, 'Asset checked in successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Create a transfer activity.
     */
    public function transfer(Request $request, Asset $asset): JsonResponse
    {
        $request->validate([
            'to_location' => 'required|string|max:255',
            'from_location' => 'nullable|string|max:255',
            'to_department_id' => 'nullable|exists:divisions,id',
            'from_department_id' => 'nullable|exists:divisions,id',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        try {
            $activity = $asset->createTransfer($request->all());

            Log::info('[Asset Transfer]', [
                'asset_id' => $asset->id,
                'activity_id' => $activity->id,
                'from_location' => $request->from_location,
                'to_location' => $request->to_location,
            ]);

            return $this->success($activity->load('creator'), 'Transfer initiated successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Complete a transfer.
     */
    public function completeTransfer(Asset $asset, AssetActivity $activity): JsonResponse
    {
        try {
            $asset->completeTransfer($activity);

            Log::info('[Asset Transfer Completed]', [
                'asset_id' => $asset->id,
                'activity_id' => $activity->id,
            ]);

            return $this->success(null, 'Transfer completed successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Create a recall activity.
     */
    public function recall(Request $request, Asset $asset): JsonResponse
    {
        $request->validate([
            'from_user_id' => 'nullable|exists:users,id',
            'reason' => 'required|string',
            'notes' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        try {
            $activity = $asset->createRecall($request->all());

            Log::info('[Asset Recall]', [
                'asset_id' => $asset->id,
                'activity_id' => $activity->id,
                'from_user_id' => $request->from_user_id,
                'reason' => $request->reason,
            ]);

            return $this->success($activity->load('creator'), 'Recall initiated successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Create a maintenance activity.
     * In the new multi-status model, maintenance can run alongside allocation and reservation.
     */
    public function maintenance(Request $request, Asset $asset): JsonResponse
    {
        $request->validate([
            'maintenance_type' => 'required|in:preventive,corrective,inspection,upgrade',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'performed_by' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        try {
            // Check if already in maintenance
            if ($asset->current_maintenance_id) {
                throw new \Exception('Asset sedang dalam maintenance. Selesaikan maintenance sebelumnya terlebih dahulu.');
            }

            // In the new multi-status model, maintenance can coexist with allocation and reservation
            // No need to check for available status

            $activity = $asset->createMaintenance($request->all());

            Log::info('[Asset Maintenance]', [
                'asset_id' => $asset->id,
                'activity_id' => $activity->id,
                'type' => $request->maintenance_type,
            ]);

            return $this->success($activity->load('creator'), 'Maintenance dijadwalkan. Asset sekarang memiliki status Tersedia + Maintenance.', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Complete maintenance - removes ONLY the maintenance status.
     * Preserves all other active statuses.
     */
    public function completeMaintenance(Asset $asset): JsonResponse
    {
        try {
            $asset->completeMaintenance();

            Log::info('[Asset Maintenance Completed]', [
                'asset_id' => $asset->id,
                'user_id' => auth()->id(),
            ]);

            return $this->success(null, 'Maintenance selesai. Status Maintenance dihapus. Status lain tetap ada.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Cancel an activity.
     */
    public function cancel(Request $request, Asset $asset, AssetActivity $activity): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string',
        ]);

        try {
            if (!$activity->canModify()) {
                return $this->error('Activity cannot be cancelled', 422);
            }

            $activity->cancel($request->reason);

            // Clear current activity reference if needed
            if ($activity->activity_type === AssetActivity::TYPE_ALLOCATION && $asset->current_allocation_id === $activity->id) {
                $asset->update(['current_allocation_id' => null]);
            } elseif ($activity->activity_type === AssetActivity::TYPE_RESERVATION && $asset->current_reservation_id === $activity->id) {
                $asset->update(['current_reservation_id' => null]);
            }

            Log::info('[Asset Activity Cancelled]', [
                'asset_id' => $asset->id,
                'activity_id' => $activity->id,
                'reason' => $request->reason,
            ]);

            return $this->success(null, 'Activity cancelled successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Get available actions for an asset based on its status.
     * In the new multi-status model, activities can coexist.
     */
    public function availableActions(Asset $asset): JsonResponse
    {
        $actions = [];

        // Can create allocation if no active allocation exists (but maintenance is ok)
        if (!$asset->current_allocation_id) {
            $actions[] = ['type' => 'allocation', 'label' => 'Alokasi', 'icon' => 'fa-box'];
        }

        // Can create reservation if no active reservation exists (but maintenance is ok)
        if (!$asset->current_reservation_id) {
            $actions[] = ['type' => 'reservation', 'label' => 'Reservasi', 'icon' => 'fa-calendar-check'];
        }

        // Can create maintenance if no active maintenance exists
        // Maintenance can coexist with allocation and reservation
        if (!$asset->current_maintenance_id) {
            $actions[] = ['type' => 'maintenance', 'label' => 'Maintenance', 'icon' => 'fa-wrench'];
        }

        // Can complete allocation if one exists
        if ($asset->current_allocation_id) {
            $actions[] = ['type' => 'complete_allocation', 'label' => 'Selesaikan Alokasi', 'icon' => 'fa-check'];
        }

        // Can complete reservation if one exists
        if ($asset->current_reservation_id) {
            $actions[] = ['type' => 'complete_reservation', 'label' => 'Selesaikan Reservasi', 'icon' => 'fa-check'];
        }

        // Can complete maintenance if one exists
        if ($asset->current_maintenance_id) {
            $actions[] = ['type' => 'complete_maintenance', 'label' => 'Selesaikan Maintenance', 'icon' => 'fa-check'];
        }

        return $this->success([
            'asset_statuses' => $asset->statuses,
            'can_have_activities' => $asset->can_have_activities,
            'available_actions' => $actions,
        ]);
    }
}
