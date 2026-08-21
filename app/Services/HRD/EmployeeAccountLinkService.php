<?php

namespace App\Services\HRD;

use App\Models\CompanyNotification;
use App\Models\HRD\EmployeeAccountLinkRequest;
use App\Models\HRD\EmployeeProfile;
use App\Modules\System\Models\User;
use Illuminate\Support\Facades\DB;

class EmployeeAccountLinkService
{
    /**
     * Get employees that can be linked (for admin dropdown)
     */
    public function getLinkableEmployees(int $companyId): array
    {
        $query = EmployeeProfile::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNull('user_id')
            ->with(['department', 'position']);

        // Exclude employees that already have pending requests
        $pendingEmployeeIds = EmployeeAccountLinkRequest::where('company_id', $companyId)
            ->pending()
            ->whereNotNull('employee_profile_id')
            ->pluck('employee_profile_id')
            ->toArray();

        if (!empty($pendingEmployeeIds)) {
            $query->whereNotIn('id', $pendingEmployeeIds);
        }

        return $query->orderBy('full_name')->get()->map(function ($emp) {
            return [
                'id' => $emp->id,
                'employee_number' => $emp->employee_number,
                'full_name' => $emp->full_name,
                'department' => $emp->department?->name,
                'position' => $emp->position?->name,
                'display' => $this->formatDisplay($emp),
            ];
        })->toArray();
    }

    protected function formatDisplay(EmployeeProfile $emp): string
    {
        $parts = array_filter([
            $emp->full_name,
            $emp->employee_number,
            $emp->department?->name,
            $emp->position?->name,
        ]);
        return implode(' - ', $parts);
    }

    /**
     * Check if user is admin or director
     */
    public function canApproveRequests(User $user): bool
    {
        return $user->is_director || $user->is_company_admin;
    }

    /**
     * Check if user already has employee profile
     */
    public function userHasEmployeeProfile(int $userId, int $companyId): bool
    {
        return EmployeeProfile::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->exists();
    }

    /**
     * Get user's pending request
     */
    public function getPendingRequest(int $userId): ?EmployeeAccountLinkRequest
    {
        return EmployeeAccountLinkRequest::where('user_id', $userId)
            ->pending()
            ->first();
    }

    /**
     * Get pending requests for admin/director
     */
    public function getPendingRequests(int $companyId): array
    {
        return EmployeeAccountLinkRequest::where('company_id', $companyId)
            ->pending()
            ->with('requester')
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Submit a link request (staff action)
     */
    public function submitRequest(int $userId, int $companyId): array
    {
        if ($this->userHasEmployeeProfile($userId, $companyId)) {
            return [
                'success' => false,
                'code' => 'USER_ALREADY_LINKED',
                'message' => 'Akun Anda sudah terhubung dengan profil karyawan.',
            ];
        }

        if ($this->getPendingRequest($userId)) {
            return [
                'success' => false,
                'code' => 'REQUEST_ALREADY_EXISTS',
                'message' => 'Pengajuan Anda sedang diproses.',
            ];
        }

        return DB::transaction(function () use ($userId, $companyId) {
            $request = EmployeeAccountLinkRequest::create([
                'company_id' => $companyId,
                'user_id' => $userId,
                'status' => EmployeeAccountLinkRequest::STATUS_PENDING,
            ]);

            // Send notification to admin/director
            $this->notifyAdmins($companyId, $userId, $request->id);

            return [
                'success' => true,
                'request' => $request,
                'message' => 'Pengajuan berhasil dikirim. Menunggu persetujuan Admin atau Director.',
            ];
        });
    }

    /**
     * Send notification to admin/director
     */
    protected function notifyAdmins(int $companyId, int $userId, int $requestId): void
    {
        $user = User::find($userId);
        if (!$user) return;

        $admins = User::where('company_id', $companyId)
            ->whereIn('company_role', [User::ROLE_DIRECTOR, User::ROLE_ADMIN])
            ->where('is_active', true)
            ->get();

        foreach ($admins as $admin) {
            CompanyNotification::create([
                'company_id' => $companyId,
                'user_id' => $admin->id,
                'title' => 'Pengajuan Penghubungan Akun',
                'message' => "{$user->name} mengajukan penghubungan akun dengan profil karyawan.",
                'module' => CompanyNotification::MODULE_EMPLOYEE,
                'action' => CompanyNotification::ACTION_CREATE,
                'severity' => CompanyNotification::SEVERITY_INFO,
                'notifiable_type' => EmployeeAccountLinkRequest::class,
                'notifiable_id' => $requestId,
                'notifiable_label' => $user->name,
                'action_url' => route('hrd.face-attendance.process-request', $requestId),
            ]);
        }
    }

    /**
     * Cancel a pending request
     */
    public function cancelRequest(int $requestId, int $userId): array
    {
        $request = EmployeeAccountLinkRequest::where('id', $requestId)
            ->where('user_id', $userId)
            ->pending()
            ->first();

        if (!$request) {
            return [
                'success' => false,
                'code' => 'REQUEST_NOT_FOUND',
                'message' => 'Pengajuan tidak ditemukan.',
            ];
        }

        $request->update(['status' => EmployeeAccountLinkRequest::STATUS_CANCELLED]);

        return [
            'success' => true,
            'message' => 'Pengajuan berhasil dibatalkan.',
        ];
    }

    /**
     * Process a request (admin/director approves and links)
     */
    public function processRequest(int $requestId, int $employeeId, int $processorId): array
    {
        $request = EmployeeAccountLinkRequest::where('id', $requestId)->pending()->first();

        if (!$request) {
            return [
                'success' => false,
                'code' => 'REQUEST_NOT_FOUND',
                'message' => 'Pengajuan tidak ditemukan.',
            ];
        }

        $processor = User::find($processorId);
        if (!$processor || !$this->canApproveRequests($processor)) {
            return [
                'success' => false,
                'code' => 'UNAUTHORIZED',
                'message' => 'Anda tidak memiliki akses untuk memproses pengajuan ini.',
            ];
        }

        $employee = EmployeeProfile::where('id', $employeeId)
            ->where('company_id', $request->company_id)
            ->first();

        if (!$employee) {
            return [
                'success' => false,
                'code' => 'DIFFERENT_COMPANY',
                'message' => 'Profil karyawan tidak berada dalam perusahaan yang sama.',
            ];
        }

        if ($employee->user_id !== null) {
            return [
                'success' => false,
                'code' => 'PROFILE_ALREADY_LINKED',
                'message' => 'Profil karyawan sudah terhubung dengan akun lain.',
            ];
        }

        return DB::transaction(function () use ($request, $employee, $processor) {
            $employee->update(['user_id' => $request->user_id]);

            $request->update([
                'employee_profile_id' => $employee->id,
                'status' => EmployeeAccountLinkRequest::STATUS_APPROVED,
                'processed_by' => $processor->id,
                'processed_at' => now(),
            ]);

            $this->notifyUserApproved($request->user_id, $employee);

            return [
                'success' => true,
                'message' => 'Akun berhasil dihubungkan dengan profil karyawan.',
                'employee' => $employee->fresh(['department', 'position']),
            ];
        });
    }

    /**
     * Notify user that request was approved
     */
    protected function notifyUserApproved(int $userId, EmployeeProfile $employee): void
    {
        CompanyNotification::create([
            'company_id' => $employee->company_id,
            'user_id' => $userId,
            'title' => 'Akun Berhasil Dihubungkan',
            'message' => 'Akun Anda telah dihubungkan dengan profil karyawan. Anda sekarang dapat menggunakan Face Attendance.',
            'module' => CompanyNotification::MODULE_ATTENDANCE,
            'action' => CompanyNotification::ACTION_UPDATE,
            'severity' => CompanyNotification::SEVERITY_SUCCESS,
            'notifiable_type' => EmployeeProfile::class,
            'notifiable_id' => $employee->id,
            'notifiable_label' => $employee->full_name,
        ]);
    }
}
