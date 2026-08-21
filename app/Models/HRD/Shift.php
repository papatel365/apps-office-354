<?php

namespace App\Models\HRD;

use App\Core\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'name',
        'code',
        'start_time',
        'end_time',
        'grace_period_minutes',
        'late_tolerance_minutes',
        'early_out_tolerance_minutes',
        'overtime_start_time',
        'color',
        'is_night_shift',
        'is_active',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'grace_period_minutes' => 'integer',
        'late_tolerance_minutes' => 'integer',
        'early_out_tolerance_minutes' => 'integer',
        'overtime_start_time' => 'datetime:H:i',
        'is_night_shift' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(ShiftSchedule::class, 'shift_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function getWorkingHoursAttribute(): float
    {
        if (!$this->start_time || !$this->end_time) return 0;

        $start = $this->start_time;
        $end = $this->end_time;

        if ($this->is_night_shift) {
            $end = $end->addDay();
        }

        return $start->diffInMinutes($end) / 60;
    }

    public function getOvertimeHoursAttribute(): float
    {
        if (!$this->overtime_start_time || !$this->end_time) return 0;
        return $this->overtime_start_time->diffInMinutes($this->end_time) / 60;
    }
}
