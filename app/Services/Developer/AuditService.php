<?php

namespace App\Services\Developer;

use App\Models\AuditLog;
use App\Modules\System\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditService
{
    /**
     * Log an audit event.
     */
    public static function log(
        string $action,
        string $category = 'general',
        ?string $modelType = null,
        ?int $modelId = null,
        ?string $modelLabel = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null,
        ?array $metadata = null
    ): AuditLog {
        $user = Auth::user();
        $request = request();

        // Calculate changes
        $changes = null;
        if ($oldValues && $newValues) {
            $changes = self::calculateChanges($oldValues, $newValues);
        }

        // Detect device info
        $device = $request ? AuditLog::detectDevice($request->userAgent() ?? '') : 'Unknown';
        $browserInfo = $request ? AuditLog::detectBrowser($request->userAgent() ?? '') : ['browser' => 'Unknown', 'version' => null];

        return AuditLog::create([
            'user_id' => $user?->id,
            'user_type' => $user ? get_class($user) : null,
            'action' => $action,
            'category' => $category,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'model_label' => $modelLabel,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changes' => $changes,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'device' => $device,
            'browser' => $browserInfo['browser'],
            'browser_version' => $browserInfo['version'],
            'os' => $request ? AuditLog::detectOS($request->userAgent() ?? '') : 'Unknown',
            'reason' => $reason,
            'metadata' => $metadata,
            'logged_at' => now(),
        ]);
    }

    /**
     * Log an authentication event.
     */
    public static function auth(
        string $action,
        ?string $email = null,
        ?bool $success = true,
        ?string $reason = null
    ): AuditLog {
        return self::log(
            action: $action,
            category: 'auth',
            metadata: [
                'email' => $email,
                'success' => $success,
            ],
            reason: $reason
        );
    }

    /**
     * Log a company event.
     */
    public static function company(
        string $action,
        $company,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null
    ): AuditLog {
        return self::log(
            action: $action,
            category: 'company',
            modelType: get_class($company),
            modelId: $company->id,
            modelLabel: $company->name,
            oldValues: $oldValues,
            newValues: $newValues,
            reason: $reason
        );
    }

    /**
     * Log a license event.
     */
    public static function license(
        string $action,
        $company,
        string $moduleCode,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null
    ): AuditLog {
        return self::log(
            action: $action,
            category: 'license',
            modelType: get_class($company),
            modelId: $company->id,
            modelLabel: "{$company->name} - {$moduleCode}",
            oldValues: $oldValues,
            newValues: $newValues,
            reason: $reason
        );
    }

    /**
     * Log a module event.
     */
    public static function module(
        string $action,
        $module,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null
    ): AuditLog {
        return self::log(
            action: $action,
            category: 'module',
            modelType: get_class($module),
            modelId: $module->id,
            modelLabel: $module->name,
            oldValues: $oldValues,
            newValues: $newValues,
            reason: $reason
        );
    }

    /**
     * Log a payment event.
     */
    public static function payment(
        string $action,
        $transaction,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null
    ): AuditLog {
        return self::log(
            action: $action,
            category: 'payment',
            modelType: get_class($transaction),
            modelId: $transaction->id,
            modelLabel: $transaction->invoice_number ?? "Transaction #{$transaction->id}",
            oldValues: $oldValues,
            newValues: $newValues,
            reason: $reason
        );
    }

    /**
     * Log a gateway event.
     */
    public static function gateway(
        string $action,
        string $gatewayName,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null
    ): AuditLog {
        return self::log(
            action: $action,
            category: 'gateway',
            modelLabel: $gatewayName,
            oldValues: $oldValues,
            newValues: $newValues,
            reason: $reason
        );
    }

    /**
     * Log a settings event.
     */
    public static function settings(
        string $action,
        string $group,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null
    ): AuditLog {
        return self::log(
            action: $action,
            category: 'settings',
            modelLabel: "Settings: {$group}",
            oldValues: $oldValues,
            newValues: $newValues,
            reason: $reason
        );
    }

    /**
     * Log an override event (developer manual override).
     */
    public static function override(
        string $action,
        string $type,
        $model,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null
    ): AuditLog {
        return self::log(
            action: $action,
            category: 'override',
            modelType: get_class($model),
            modelId: $model->id ?? null,
            modelLabel: method_exists($model, 'name') ? $model->name : (method_exists($model, 'company') ? $model->company?->name : null),
            oldValues: $oldValues,
            newValues: $newValues,
            reason: $reason,
            metadata: ['override_type' => $type]
        );
    }

    /**
     * Log a system event.
     */
    public static function system(
        string $action,
        ?string $description = null,
        ?array $metadata = null,
        ?string $reason = null
    ): AuditLog {
        return self::log(
            action: $action,
            category: 'system',
            modelLabel: $description ?? "System: {$action}",
            reason: $reason,
            metadata: $metadata
        );
    }

    /**
     * Log an API event.
     */
    public static function api(
        string $action,
        ?string $endpoint = null,
        ?string $method = null,
        ?array $metadata = null,
        ?string $reason = null
    ): AuditLog {
        return self::log(
            action: $action,
            category: 'api',
            modelLabel: $endpoint,
            reason: $reason,
            metadata: array_merge($metadata ?? [], [
                'endpoint' => $endpoint,
                'method' => $method,
            ])
        );
    }

    /**
     * Calculate changes between old and new values.
     */
    protected static function calculateChanges(array $old, array $new): array
    {
        $changes = [];

        // Find modified fields
        foreach ($new as $key => $newValue) {
            if (!array_key_exists($key, $old)) {
                $changes[$key] = ['old' => null, 'new' => $newValue];
            } elseif ($old[$key] !== $newValue) {
                $changes[$key] = ['old' => $old[$key], 'new' => $newValue];
            }
        }

        // Find removed fields
        foreach ($old as $key => $oldValue) {
            if (!array_key_exists($key, $new)) {
                $changes[$key] = ['old' => $oldValue, 'new' => null];
            }
        }

        return $changes;
    }

    /**
     * Get recent audit logs.
     */
    public static function getRecent(int $limit = 20, ?string $category = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = AuditLog::with('user')
            ->orderBy('logged_at', 'desc')
            ->limit($limit);

        if ($category) {
            $query->where('category', $category);
        }

        return $query->get();
    }

    /**
     * Get audit logs for a specific model.
     */
    public static function forModel(string $modelType, int $modelId): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::with('user')
            ->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->orderBy('logged_at', 'desc')
            ->get();
    }

    /**
     * Get audit statistics.
     */
    public static function getStats(): array
    {
        return [
            'total' => AuditLog::count(),
            'today' => AuditLog::whereDate('logged_at', today())->count(),
            'this_week' => AuditLog::whereWeek('logged_at', now()->week)->count(),
            'this_month' => AuditLog::whereMonth('logged_at', now()->month)->count(),
            'by_category' => AuditLog::selectRaw('category, count(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category')
                ->toArray(),
            'by_action' => AuditLog::selectRaw('action, count(*) as count')
                ->groupBy('action')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'action')
                ->toArray(),
        ];
    }

    /**
     * Export audit logs to CSV.
     */
    public static function exportToCsv(?array $filters = null)
    {
        $query = AuditLog::with('user');

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['from'])) {
            $query->where('logged_at', '>=', $filters['from']);
        }
        if (isset($filters['to'])) {
            $query->where('logged_at', '<=', $filters['to']);
        }

        $logs = $query->orderBy('logged_at', 'desc')->get();

        $filename = 'audit_logs_' . now()->format('Y_m_d_His') . '.csv';

        $handle = fopen('php://temp', 'r+');

        // Header
        fputcsv($handle, [
            'ID', 'Timestamp', 'User', 'Action', 'Category',
            'Model Type', 'Model ID', 'Model Label',
            'IP Address', 'Device', 'Browser', 'OS',
            'Reason', 'Changes'
        ]);

        // Data
        foreach ($logs as $log) {
            fputcsv($handle, [
                $log->id,
                $log->logged_at->toDateTimeString(),
                $log->user?->name ?? 'System',
                $log->action,
                $log->category,
                $log->model_type,
                $log->model_id,
                $log->model_label,
                $log->ip_address,
                $log->device,
                $log->browser,
                $log->os,
                $log->reason,
                $log->changes ? json_encode($log->changes) : '',
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return response()->streamDownload(
            function () use ($content) {
                echo $content;
            },
            $filename,
            ['Content-Type' => 'text/csv']
        );
    }

    /**
     * Clear old audit logs.
     */
    public static function clearOld(int $days = 90): int
    {
        return AuditLog::where('logged_at', '<', now()->subDays($days))->delete();
    }
}
