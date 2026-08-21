<?php

namespace App\Models\HRD;

use App\Core\Traits\BelongsToCompany;
use App\Core\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Overtime extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use BelongsToCompany;

    protected $table = 'overtimes';

    protected $fillable = [
        'user_id',
        'employee_id',
        'company_id',
        'date',
        'start_time',
        'end_time',
        'total_hours',
        'hourly_rate',
        'overtime_type',
        'multiplier',
        'total_payment',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'notes',
        'rejected_reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'total_hours' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'multiplier' => 'decimal:2',
            'total_payment' => 'decimal:2',
            'approved_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // =====================================================
    // CONSTANTS
    // =====================================================

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    const TYPE_WEEKDAY = 'weekday';
    const TYPE_WEEKEND = 'weekend';
    const TYPE_HOLIDAY = 'holiday';

    // =====================================================
    // RELATIONSHIPS
    // =====================================================

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // =====================================================
    // SCOPES
    // =====================================================

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

    public function getIsPendingAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function getIsRejectedAttribute(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function getFormattedTotalHoursAttribute(): string
    {
        return number_format($this->total_hours, 2) . ' hours';
    }

    public function getFormattedTotalPaymentAttribute(): string
    {
        return 'Rp ' . number_format($this->total_payment, 0, ',', '.');
    }

    // =====================================================
    // HELPERS
    // =====================================================

    public function approve(int $approverId): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approverId,
            'approved_at' => now(),
        ]);
    }

    public function reject(int $approverId, string $reason): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $approverId,
            'approved_at' => now(),
            'rejected_reason' => $reason,
        ]);
    }
}
