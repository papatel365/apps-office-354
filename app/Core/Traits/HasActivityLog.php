<?php

namespace App\Core\Traits;

use App\Modules\System\Models\ActivityLog;
use App\Modules\System\Models\Tenant;
use Illuminate\Database\Eloquent\Model;

trait HasActivityLog
{
    public function activities()
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public function logActivity(
        string $description,
        string $logName = 'system',
        ?string $event = null,
        array $properties = []
    ): ActivityLog {
        $user = auth()->user();

        // Get tenant_id from various sources
        $tenantId = null;

        if (!empty($this->tenant_id)) {
            // Verify tenant exists before using it
            // This prevents FK violations when tenant_id is stale/invalid
            $tenantExists = Tenant::where('id', $this->tenant_id)->exists();
            if ($tenantExists) {
                $tenantId = $this->tenant_id;
            }
            // If tenant doesn't exist, $tenantId remains null
        } elseif (function_exists('current_tenant_id')) {
            $tenantId = current_tenant_id();
        }

        return ActivityLog::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'tenant_id' => $tenantId,
            'subject_type' => get_class($this),
            'subject_id' => $this->getKey(),
            'user_id' => $user?->id,
            'log_name' => $logName,
            'description' => $description,
            'event' => $event,
            'properties' => $properties,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    public function logCreated(string $logName = 'system', array $properties = []): ActivityLog
    {
        return $this->logActivity(
            "Created " . class_basename($this),
            $logName,
            'created',
            $properties
        );
    }

    public function logUpdated(array $changes = [], string $logName = 'system'): ActivityLog
    {
        return $this->logActivity(
            "Updated " . class_basename($this),
            $logName,
            'updated',
            ['changes' => $changes]
        );
    }

    public function logDeleted(string $logName = 'system'): ActivityLog
    {
        return $this->logActivity(
            "Deleted " . class_basename($this),
            $logName,
            'deleted'
        );
    }
}
