<?php

namespace App\Models;

use App\Modules\System\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CompanyNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'title',
        'message',
        'module',
        'action',
        'severity',
        'notifiable_type',
        'notifiable_id',
        'notifiable_label',
        'action_url',
        'is_read',
        'read_at',
        'metadata',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Boot method to auto-generate UUID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($notification) {
            if (empty($notification->uuid)) {
                $notification->uuid = (string) Str::uuid();
            }
        });
    }

    // Severity constants
    const SEVERITY_INFO = 'info';
    const SEVERITY_SUCCESS = 'success';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_ERROR = 'error';

    // Action constants
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';

    // Module names
    const MODULE_CLIENT = 'Klien';
    const MODULE_LEAD = 'Lead';
    const MODULE_PROPOSAL = 'Proposal';
    const MODULE_ESTIMATE = 'Estimasi';
    const MODULE_INVOICE = 'Invoice';
    const MODULE_PAYMENT = 'Pembayaran';
    const MODULE_EXPENSE = 'Pengeluaran';
    const MODULE_CONTRACT = 'Kontrak';
    const MODULE_SUBSCRIPTION = 'Langganan';
    const MODULE_PROJECT = 'Proyek';
    const MODULE_TASK = 'Tugas';
    const MODULE_ASSET = 'Aset';
    const MODULE_TRANSACTION = 'Transaksi';
    const MODULE_EMPLOYEE = 'Karyawan';
    const MODULE_ATTENDANCE = 'Absensi';
    const MODULE_LEAVE = 'Cuti';
    const MODULE_RECRUITMENT = 'Rekrutmen';

    /**
     * Get the company that owns the notification.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the notifiable model (polymorphic).
     */
    public function notifiable()
    {
        return $this->morphTo('notifiable', 'notifiable_type', 'notifiable_id');
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(): bool
    {
        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark notification as unread.
     */
    public function markAsUnread(): bool
    {
        return $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Get action icon based on action type.
     */
    public function getActionIconAttribute(): string
    {
        return match($this->action) {
            self::ACTION_CREATE => 'fa-plus-circle text-emerald-500',
            self::ACTION_UPDATE => 'fa-pencil text-blue-500',
            self::ACTION_DELETE => 'fa-trash text-red-500',
            default => 'fa-bell text-gray-500',
        };
    }

    /**
     * Get action icon background based on severity.
     */
    public function getSeverityBgAttribute(): string
    {
        return match($this->severity) {
            self::SEVERITY_SUCCESS => 'bg-emerald-100',
            self::SEVERITY_WARNING => 'bg-amber-100',
            self::SEVERITY_ERROR => 'bg-red-100',
            default => 'bg-blue-100',
        };
    }

    /**
     * Get human-readable action label.
     */
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            self::ACTION_CREATE => 'membuat',
            self::ACTION_UPDATE => 'mengubah',
            self::ACTION_DELETE => 'menghapus',
            default => $this->action,
        };
    }

    /**
     * Get time ago since creation.
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Scope to get unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to get notifications for a company.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to order by most recent.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Check if the notifiable model still exists.
     */
    public function getModelStillExistsAttribute(): bool
    {
        if (!$this->notifiable_type || !$this->notifiable_id) {
            return false;
        }

        return $this->notifiable()->withoutGlobalScopes()->exists();
    }

    /**
     * Get the URL for this notification, with fallback.
     */
    public function getLinkUrlAttribute(): ?string
    {
        if ($this->action_url) {
            return $this->action_url;
        }

        if ($this->model_still_exists && $this->notifiable) {
            if (method_exists($this->notifiable, 'getShowUrl')) {
                return $this->notifiable->getShowUrl();
            }
        }

        return null;
    }
}
