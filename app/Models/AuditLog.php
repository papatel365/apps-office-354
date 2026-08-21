<?php

namespace App\Models;

use App\Modules\System\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'user_type',
        'action',
        'model_type',
        'model_id',
        'model_label',
        'old_values',
        'new_values',
        'changes',
        'ip_address',
        'user_agent',
        'device',
        'browser',
        'browser_version',
        'os',
        'location',
        'reason',
        'metadata',
        'category',
        'logged_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changes' => 'array',
        'metadata' => 'array',
        'logged_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            if (empty($model->tenant_id)) {
                $model->tenant_id = auth()->check() ? auth()->user()->tenant_id : null;
            }

            if (empty($model->logged_at)) {
                $model->logged_at = now();
            }
        });
    }

    /**
     * Get the user that owns the audit log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for filtering by category.
     */
    public function scopeOfCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for filtering by action.
     */
    public function scopeOfAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for filtering by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for filtering by model.
     */
    public function scopeForModel($query, string $modelType, ?int $modelId = null)
    {
        $query->where('model_type', $modelType);
        if ($modelId !== null) {
            $query->where('model_id', $modelId);
        }
        return $query;
    }

    /**
     * Scope for filtering by date range.
     */
    public function scopeDateRange($query, $from, $to = null)
    {
        if ($from) {
            $query->where('logged_at', '>=', $from);
        }
        if ($to) {
            $query->where('logged_at', '<=', $to);
        }
        return $query;
    }

    /**
     * Get action label with icon.
     */
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'create', 'created' => 'Created',
            'update', 'updated' => 'Updated',
            'delete', 'deleted' => 'Deleted',
            'login' => 'Logged In',
            'logout' => 'Logged Out',
            'failed_login' => 'Failed Login',
            'grant' => 'Granted',
            'revoke' => 'Revoked',
            'extend' => 'Extended',
            'suspend' => 'Suspended',
            'activate' => 'Activated',
            'override' => 'Override',
            'export' => 'Exported',
            default => ucfirst($this->action),
        };
    }

    /**
     * Get category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'auth' => 'Authentication',
            'company' => 'Company',
            'license' => 'License',
            'module' => 'Module',
            'payment' => 'Payment',
            'gateway' => 'Gateway',
            'settings' => 'Settings',
            'override' => 'Override',
            'system' => 'System',
            'api' => 'API',
            default => ucfirst($this->category),
        };
    }

    /**
     * Get the icon for the action.
     */
    public function getActionIconAttribute(): string
    {
        return match($this->action) {
            'create', 'created', 'grant' => 'fa-plus-circle text-emerald-500',
            'update', 'updated', 'extend' => 'fa-edit text-blue-500',
            'delete', 'deleted', 'revoke' => 'fa-trash text-red-500',
            'login' => 'fa-sign-in text-indigo-500',
            'logout' => 'fa-sign-out text-gray-500',
            'failed_login' => 'fa-exclamation-triangle text-red-500',
            'suspend' => 'fa-ban text-amber-500',
            'activate' => 'fa-check-circle text-emerald-500',
            'override' => 'fa-exclamation-circle text-purple-500',
            'export' => 'fa-download text-blue-500',
            default => 'fa-info-circle text-gray-500',
        };
    }

    /**
     * Get the icon background class.
     */
    public function getActionIconBgAttribute(): string
    {
        return match($this->action) {
            'create', 'created', 'grant' => 'bg-emerald-100',
            'update', 'updated', 'extend' => 'bg-blue-100',
            'delete', 'deleted', 'revoke' => 'bg-red-100',
            'login' => 'bg-indigo-100',
            'logout' => 'bg-gray-100',
            'failed_login' => 'bg-red-100',
            'suspend' => 'bg-amber-100',
            'activate' => 'bg-emerald-100',
            'override' => 'bg-purple-100',
            'export' => 'bg-blue-100',
            default => 'bg-gray-100',
        };
    }

    /**
     * Format changes for display.
     */
    public function getFormattedChangesAttribute(): ?array
    {
        if (!$this->changes) {
            return null;
        }

        $formatted = [];
        foreach ($this->changes as $field => $change) {
            $formatted[] = [
                'field' => $field,
                'old' => is_array($change['old']) ? json_encode($change['old']) : $change['old'],
                'new' => is_array($change['new']) ? json_encode($change['new']) : $change['new'],
            ];
        }

        return $formatted;
    }

    /**
     * Detect device from user agent.
     */
    public static function detectDevice(string $userAgent): string
    {
        $userAgent = strtolower($userAgent);

        if (str_contains($userAgent, 'mobile') || str_contains($userAgent, 'android')) {
            return 'Mobile';
        }
        if (str_contains($userAgent, 'tablet') || str_contains($userAgent, 'ipad')) {
            return 'Tablet';
        }
        if (str_contains($userAgent, 'bot') || str_contains($userAgent, 'crawler')) {
            return 'Bot';
        }

        return 'Desktop';
    }

    /**
     * Detect browser from user agent.
     */
    public static function detectBrowser(string $userAgent): array
    {
        $userAgent = strtolower($userAgent);

        $browsers = [
            'edge' => ['Edge', '/edge\/([\d\.]+)/i'],
            'chrome' => ['Chrome', '/chrome\/([\d\.]+)/i'],
            'firefox' => ['Firefox', '/firefox\/([\d\.]+)/i'],
            'safari' => ['Safari', '/safari\/([\d\.]+)/i'],
            'opera' => ['Opera', '/opera\/([\d\.]+)/i'],
            'ie' => ['Internet Explorer', '/msie ([\d\.]+)/i'],
        ];

        foreach ($browsers as $browser => [$name, $pattern]) {
            if (preg_match($pattern, $userAgent, $matches)) {
                return [
                    'browser' => $name,
                    'version' => $matches[1] ?? null,
                ];
            }
        }

        return ['browser' => 'Unknown', 'version' => null];
    }

    /**
     * Detect OS from user agent.
     */
    public static function detectOS(string $userAgent): string
    {
        $userAgent = strtolower($userAgent);

        $systems = [
            'windows' => ['Windows', 'windows'],
            'mac' => ['macOS', 'macintosh'],
            'linux' => ['Linux', 'linux'],
            'android' => ['Android', 'android'],
            'ios' => ['iOS', 'iphone'],
            'ipad' => ['iPadOS', 'ipad'],
        ];

        foreach ($systems as $system => [$name, $pattern]) {
            if (str_contains($userAgent, $pattern)) {
                return $name;
            }
        }

        return 'Unknown';
    }
}
