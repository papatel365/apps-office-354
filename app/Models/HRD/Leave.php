<?php

namespace App\Models\HRD;

use App\Core\Traits\BelongsToCompany;
use App\Modules\System\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leave extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'leave_type',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'status',
        'document_path',
        'approved_by',
        'approved_at',
        'rejected_reason',
        'is_half_day',
        'half_day_type', // morning, afternoon
        'handover_notes',
        'contact_during_leave',
        'remaining_balance_after',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_days' => 'decimal:2',
        'approved_at' => 'datetime',
        'is_half_day' => 'boolean',
        'remaining_balance_after' => 'decimal:2',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED_SUPERVISOR = 'approved_supervisor';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    public function employee(): BelongsTo
    {
        // The leaves table uses user_id, so we join through the user relationship
        return $this->belongsTo(EmployeeProfile::class, 'user_id', 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($q)
    {
        return $q->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($q)
    {
        return $q->whereIn('status', [self::STATUS_APPROVED, self::STATUS_APPROVED_SUPERVISOR]);
    }

    public function scopeAwaitingHrApproval($q)
    {
        return $q->where('status', self::STATUS_APPROVED_SUPERVISOR);
    }

    public function approve(User $user): bool
    {
        if ($this->status === self::STATUS_PENDING) {
            $this->update([
                'status' => self::STATUS_APPROVED_SUPERVISOR,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);
            return true;
        }

        if ($this->status === self::STATUS_APPROVED_SUPERVISOR) {
            $this->update([
                'status' => self::STATUS_APPROVED,
                'approved_at' => now(),
            ]);
            // Reduce leave balance
            if ($this->leaveType && $this->leaveType->is_paid) {
                $this->employee->decrement('leave_balance', $this->total_days);
            }
            return true;
        }

        return false;
    }

    public function reject(User $user, string $reason): bool
    {
        if (in_array($this->status, [self::STATUS_APPROVED, self::STATUS_REJECTED])) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejected_reason' => $reason,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return true;
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Menunggu',
            self::STATUS_APPROVED_SUPERVISOR => 'Disetujui Supervisor',
            self::STATUS_APPROVED => 'Disetujui HR',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_APPROVED_SUPERVISOR => 'blue',
            self::STATUS_APPROVED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray',
        };
    }
}
