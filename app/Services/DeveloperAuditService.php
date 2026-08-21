<?php

namespace App\Services;

use App\Modules\System\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DeveloperAuditService
{
    /**
     * Log a developer action to the activity log.
     *
     * @param string $action       Action identifier, e.g. 'license.grant', 'addon.price.update'
     * @param string $description  Human-readable description
     * @param Model|null $subject  Related model (Company, Module, ModuleTransaction, etc.)
     * @param array $properties    Extra data: ['old_values' => [...], 'new_values' => [...], 'reason' => ...]
     */
    public static function log(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = []
    ): void {
        $user = auth()->user();

        $data = [
            'uuid' => Str::uuid(),
            'log_name' => 'developer',
            'description' => $description,
            'event' => $action,
            'properties' => [
                'action' => $action,
                'reason' => $properties['reason'] ?? null,
                'old_values' => $properties['old_values'] ?? [],
                'new_values' => $properties['new_values'] ?? [],
                'extra' => $properties['extra'] ?? [],
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ];

        if ($user) {
            $data['user_id'] = $user->id;
        }

        if ($subject) {
            $data['subject_type'] = get_class($subject);
            $data['subject_id'] = $subject->getKey();
        }

        // Store tenant_id if available (for developer, it's usually null or a system tenant)
        $data['tenant_id'] = null;

        ActivityLog::create($data);
    }

    /**
     * Log license action
     */
    public static function license(string $action, string $description, ?Model $company = null, array $properties = []): void
    {
        self::log("license.{$action}", $description, $company, $properties);
    }

    /**
     * Log addon action
     */
    public static function addon(string $action, string $description, ?Model $module = null, array $properties = []): void
    {
        self::log("addon.{$action}", $description, $module, $properties);
    }

    /**
     * Log payment action
     */
    public static function payment(string $action, string $description, ?Model $transaction = null, array $properties = []): void
    {
        self::log("payment.{$action}", $description, $transaction, $properties);
    }

    /**
     * Log gateway action
     */
    public static function gateway(string $action, string $description, ?Model $gateway = null, array $properties = []): void
    {
        self::log("gateway.{$action}", $description, $gateway, $properties);
    }

    /**
     * Log subscription action
     */
    public static function subscription(string $action, string $description, ?Model $subscription = null, array $properties = []): void
    {
        self::log("subscription.{$action}", $description, $subscription, $properties);
    }

    /**
     * Log company action
     */
    public static function company(string $action, string $description, ?Model $company = null, array $properties = []): void
    {
        self::log("company.{$action}", $description, $company, $properties);
    }

    /**
     * Get action label for display
     */
    public static function getActionLabel(string $action): string
    {
        return match ($action) {
            'license.grant' => 'Grant License',
            'license.revoke' => 'Revoke License',
            'license.extend' => 'Extend License',
            'license.lifetime' => 'Set Lifetime License',
            'addon.price.update' => 'Update Harga',
            'addon.description.update' => 'Update Deskripsi',
            'addon.trial.update' => 'Update Trial',
            'addon.status.toggle' => 'Toggle Status',
            'addon.promo.set' => 'Set Promo',
            'addon.promo.remove' => 'Hapus Promo',
            'payment.retry' => 'Retry Callback',
            'payment.refund' => 'Refund',
            'payment.mark_paid' => 'Mark As Paid',
            'payment.cancel' => 'Cancel Payment',
            'gateway.update' => 'Update Gateway',
            'gateway.test' => 'Test Connection',
            'gateway.toggle' => 'Toggle Gateway',
            'gateway.set_default' => 'Set Default Gateway',
            'subscription.suspend' => 'Suspend Subscription',
            'subscription.activate' => 'Activate Subscription',
            'subscription.extend' => 'Extend Subscription',
            'subscription.cancel' => 'Cancel Subscription',
            'company.addon.activate' => 'Aktifkan Addon',
            'company.addon.deactivate' => 'Nonaktifkan Addon',
            'company.addon.extend' => 'Perpanjang Addon',
            'company.addon.lifetime' => 'Set Lifetime Addon',
            default => ucfirst(str_replace('.', ' ', $action)),
        };
    }
}
