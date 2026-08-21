<?php

namespace App\Services;

use App\Models\CompanyNotification;
use App\Modules\System\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Create a notification for all company users.
     */
    public function createForCompany(int $companyId, array $data): ?CompanyNotification
    {
        try {
            return CompanyNotification::create([
                'company_id' => $companyId,
                'user_id' => $data['user_id'] ?? auth()->id(),
                'title' => $data['title'],
                'message' => $data['message'],
                'module' => $data['module'],
                'action' => $data['action'],
                'severity' => $data['severity'] ?? CompanyNotification::SEVERITY_INFO,
                'notifiable_type' => $data['notifiable_type'] ?? null,
                'notifiable_id' => $data['notifiable_id'] ?? null,
                'notifiable_label' => $data['notifiable_label'] ?? null,
                'action_url' => $data['action_url'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create company notification: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create notification for a specific user.
     */
    public function createForUser(int $userId, array $data): ?CompanyNotification
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                return null;
            }

            return CompanyNotification::create([
                'company_id' => $user->company_id,
                'user_id' => $userId,
                'title' => $data['title'],
                'message' => $data['message'],
                'module' => $data['module'],
                'action' => $data['action'],
                'severity' => $data['severity'] ?? CompanyNotification::SEVERITY_INFO,
                'notifiable_type' => $data['notifiable_type'] ?? null,
                'notifiable_id' => $data['notifiable_id'] ?? null,
                'notifiable_label' => $data['notifiable_label'] ?? null,
                'action_url' => $data['action_url'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create user notification: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a notification for model event (create, update, delete).
     */
    public function notifyModelEvent(
        string $event,
        string $modelClass,
        $model,
        int $companyId,
        ?int $actorUserId = null
    ): ?CompanyNotification {
        $action = match($event) {
            'created' => CompanyNotification::ACTION_CREATE,
            'updated' => CompanyNotification::ACTION_UPDATE,
            'deleted' => CompanyNotification::ACTION_DELETE,
            default => $event,
        };

        // Get model label/name
        $label = $this->getModelLabel($model);

        // Get module name
        $module = $this->getModelModuleName($modelClass);

        // Get user who performed the action
        $userId = $actorUserId ?? auth()->id();
        $userName = $userId ? User::find($userId)?->name : 'System';

        // Generate title and message
        $actionLabel = match($action) {
            CompanyNotification::ACTION_CREATE => 'membuat',
            CompanyNotification::ACTION_UPDATE => 'mengubah',
            CompanyNotification::ACTION_DELETE => 'menghapus',
            default => $action,
        };

        $title = match($action) {
            CompanyNotification::ACTION_CREATE => "{$userName} membuat {$module} baru",
            CompanyNotification::ACTION_UPDATE => "{$userName} mengubah {$module}",
            CompanyNotification::ACTION_DELETE => "{$userName} menghapus {$module}",
            default => "{$module} - {$action}",
        };

        $message = "{$userName} {$actionLabel} {$module}" . ($label ? " \"{$label}\"" : '');

        // Generate action URL
        $actionUrl = $this->generateActionUrl($modelClass, $model, $action);

        return $this->createForCompany($companyId, [
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'module' => $module,
            'action' => $action,
            'severity' => $action === CompanyNotification::ACTION_DELETE
                ? CompanyNotification::SEVERITY_WARNING
                : CompanyNotification::SEVERITY_INFO,
            'notifiable_type' => $modelClass,
            'notifiable_id' => $model->id ?? null,
            'notifiable_label' => $label,
            'action_url' => $actionUrl,
        ]);
    }

    /**
     * Get the label/name from a model.
     */
    protected function getModelLabel($model): ?string
    {
        // Try common name fields
        $nameFields = ['name', 'title', 'label', 'subject', 'code', 'number'];

        foreach ($nameFields as $field) {
            if (isset($model->{$field})) {
                return $model->{$field};
            }
        }

        // Try getting from user relation
        if (isset($model->user) && isset($model->user->name)) {
            return $model->user->name;
        }

        return null;
    }

    /**
     * Get the module name from model class.
     */
    protected function getModelModuleName(string $modelClass): string
    {
        $classBasename = class_basename($modelClass);

        return match($classBasename) {
            'Client' => 'Klien',
            'Lead' => 'Lead',
            'Proposal' => 'Proposal',
            'Estimate' => 'Estimasi',
            'Invoice' => 'Invoice',
            'Payment' => 'Pembayaran',
            'Expense' => 'Pengeluaran',
            'Contract' => 'Kontrak',
            'Subscription' => 'Langganan',
            'Project' => 'Proyek',
            'Task' => 'Tugas',
            'Asset' => 'Aset',
            'Transaction' => 'Transaksi',
            'EmployeeProfile' => 'Karyawan',
            'Attendance' => 'Absensi',
            'Leave' => 'Cuti',
            'Recruitment' => 'Rekrutmen',
            'Department' => 'Departemen',
            'Division' => 'Divisi',
            'User' => 'User',
            default => $classBasename,
        };
    }

    /**
     * Generate action URL for a model.
     */
    protected function generateActionUrl(string $modelClass, $model, string $action): ?string
    {
        $classBasename = class_basename($modelClass);

        // If deleted, no URL needed
        if ($action === CompanyNotification::ACTION_DELETE) {
            return null;
        }

        // Generate index or show URL based on model
        $routes = match($classBasename) {
            'Client' => ['index' => 'clients.index', 'show' => 'clients.show'],
            'Lead' => ['index' => 'leads.index', 'show' => 'leads.show'],
            'Proposal' => ['index' => 'proposals.index', 'show' => 'proposals.show'],
            'Estimate' => ['index' => 'estimates.index', 'show' => 'estimates.show'],
            'Invoice' => ['index' => 'invoices.index', 'show' => 'invoices.show'],
            'Payment' => ['index' => 'payments.index', 'show' => 'payments.show'],
            'Contract' => ['index' => 'contracts.index', 'show' => 'contracts.show'],
            'Subscription' => ['index' => 'subscriptions.index', 'show' => 'subscriptions.show'],
            'Project' => ['index' => 'projects.index', 'show' => 'projects.show'],
            'Task' => ['index' => 'tasks.index', 'show' => 'tasks.show'],
            'Asset' => ['index' => 'assets.index', 'show' => 'assets.show'],
            'EmployeeProfile' => ['index' => 'hrd.employees.index', 'show' => 'hrd.employees.show'],
            'Attendance' => ['index' => 'hrd.attendances.index'],
            default => null,
        };

        if (!$routes || !isset($model->id)) {
            return null;
        }

        $routeName = $action === CompanyNotification::ACTION_CREATE
            ? $routes['index']
            : $routes['show'];

        try {
            return route($routeName, $model->id);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Mark all notifications as read for a company.
     */
    public function markAllAsRead(int $companyId, ?int $userId = null): int
    {
        $query = CompanyNotification::where('company_id', $companyId)->where('is_read', false);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Delete old notifications (cleanup).
     */
    public function deleteOldNotifications(int $days = 30): int
    {
        return CompanyNotification::where('created_at', '<', now()->subDays($days))
            ->where('is_read', true)
            ->delete();
    }

    /**
     * Get unread count for a user.
     */
    public function getUnreadCount(int $companyId, ?int $userId = null): int
    {
        $query = CompanyNotification::where('company_id', $companyId)
            ->where('is_read', false);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->count();
    }
}
