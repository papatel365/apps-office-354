<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeveloperNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'type',
        'title',
        'message',
        'severity',
        'is_read',
        'action_url',
        'action_label',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'metadata' => 'array',
    ];

    // Severity constants
    const SEVERITY_INFO = 'info';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_CRITICAL = 'critical';

    // Type constants
    const TYPE_GATEWAY_DOWN = 'gateway_down';
    const TYPE_SERVER_DOWN = 'server_down';
    const TYPE_CALLBACK_FAILED = 'callback_failed';
    const TYPE_LICENSE_EXPIRED = 'license_expired';
    const TYPE_STORAGE_FULL = 'storage_full';
    const TYPE_QUEUE_ERROR = 'queue_error';
    const TYPE_CRON_NOT_RUNNING = 'cron_not_running';

    /**
     * Get the user who created the notification.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(): bool
    {
        return $this->update(['is_read' => true]);
    }

    /**
     * Mark notification as unread.
     */
    public function markAsUnread(): bool
    {
        return $this->update(['is_read' => false]);
    }

    /**
     * Scope to get unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to get critical notifications.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    /**
     * Scope to get warnings.
     */
    public function scopeWarnings($query)
    {
        return $query->where('severity', self::SEVERITY_WARNING);
    }

    /**
     * Scope to order by most recent.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Get severity badge color.
     */
    public function getSeverityBadgeAttribute(): string
    {
        return match($this->severity) {
            self::SEVERITY_CRITICAL => 'bg-red-100 text-red-700 border-red-200',
            self::SEVERITY_WARNING => 'bg-amber-100 text-amber-700 border-amber-200',
            default => 'bg-blue-100 text-blue-700 border-blue-200',
        };
    }

    /**
     * Get severity icon.
     */
    public function getSeverityIconAttribute(): string
    {
        return match($this->severity) {
            self::SEVERITY_CRITICAL => 'fa-circle-exclamation text-red-500',
            self::SEVERITY_WARNING => 'fa-triangle-exclamation text-amber-500',
            default => 'fa-circle-info text-blue-500',
        };
    }

    /**
     * Get type icon.
     */
    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            self::TYPE_GATEWAY_DOWN => 'fa-credit-card',
            self::TYPE_SERVER_DOWN => 'fa-server',
            self::TYPE_CALLBACK_FAILED => 'fa-phone-slash',
            self::TYPE_LICENSE_EXPIRED => 'fa-key',
            self::TYPE_STORAGE_FULL => 'fa-hard-drive',
            self::TYPE_QUEUE_ERROR => 'fa-list-check',
            self::TYPE_CRON_NOT_RUNNING => 'fa-clock',
            default => 'fa-bell',
        };
    }

    /**
     * Get time ago since creation.
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }
}
