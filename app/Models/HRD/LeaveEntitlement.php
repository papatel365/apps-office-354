<?php

namespace App\Models\HRD;

use App\Core\Traits\BelongsToCompany;
use App\Modules\System\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveEntitlement extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'employee_id',
        'leave_type_id',
        'year',
        'entitled_days',
        'used_days',
        'pending_days',
        'effective_date',
        'expired_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'entitled_days' => 'integer',
        'used_days' => 'integer',
        'pending_days' => 'integer',
        'effective_date' => 'date',
        'expired_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getRemainingDaysAttribute(): int
    {
        return max(0, $this->entitled_days - $this->used_days - $this->pending_days);
    }

    public function getIsActiveAttribute(): bool
    {
        $now = now()->toDateString();
        if ($this->effective_date && $this->effective_date > $now) {
            return false;
        }
        if ($this->expired_date && $this->expired_date < $now) {
            return false;
        }
        return true;
    }

    public function scopeForYear($query, $year = null)
    {
        return $query->where('year', $year ?? now()->year);
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('effective_date')
              ->orWhere('effective_date', '<=', now());
        })->where(function ($q) {
            $q->whereNull('expired_date')
              ->orWhere('expired_date', '>=', now());
        });
    }
}
