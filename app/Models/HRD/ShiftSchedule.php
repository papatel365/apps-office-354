<?php

namespace App\Models\HRD;

use App\Core\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftSchedule extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'employee_id',
        'shift_id',
        'date',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function scopeForDate($q, $date)
    {
        return $q->whereDate('date', $date);
    }

    public function scopeForEmployee($q, $employeeId)
    {
        return $q->where('employee_id', $employeeId);
    }

    public function scopeForMonth($q, $year, $month)
    {
        return $q->whereYear('date', $year)->whereMonth('date', $month);
    }
}
