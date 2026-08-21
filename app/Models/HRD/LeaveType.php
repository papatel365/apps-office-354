<?php

namespace App\Models\HRD;

use App\Core\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'name',
        'code',
        'color',
        'icon',
        'default_days',
        'is_paid',
        'is_active',
        'requires_document',
        'max_consecutive_days',
        'min_advance_days',
    ];

    protected $casts = [
        'default_days' => 'integer',
        'is_paid' => 'boolean',
        'is_active' => 'boolean',
        'requires_document' => 'boolean',
        'max_consecutive_days' => 'integer',
        'min_advance_days' => 'integer',
    ];

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class, 'leave_type_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopePaid($q)
    {
        return $q->where('is_paid', true);
    }

    public function getBalanceForEmployee(int $employeeId): float
    {
        $entitled = $this->default_days;
        $used = Leave::where('employee_id', $employeeId)
            ->where('leave_type_id', $this->id)
            ->whereIn('status', ['approved', 'completed'])
            ->whereYear('start_date', now()->year)
            ->sum('total_days');

        return max(0, $entitled - $used);
    }
}
