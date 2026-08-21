<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryComponent extends Model
{
    // Note: Tidak menggunakan BelongsToCompany trait karena salary components diakses melalui parent Salary
    // Company scope tidak diperlukan di level ini

    protected $table = 'employee_salary_components';

    protected $fillable = [
        'salary_id',
        'type',
        'name',
        'calculation_type',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Type constants
     */
    const TYPE_ALLOWANCE = 'allowance';
    const TYPE_DEDUCTION = 'deduction';

    /**
     * Calculation type constants
     */
    const CALC_FIXED = 'fixed';
    const CALC_PERCENTAGE = 'percentage';

    /**
     * Get the salary that owns this component.
     */
    public function salary(): BelongsTo
    {
        return $this->belongsTo(\App\Models\HRD\Salary::class, 'salary_id');
    }

    /**
     * Scope: Only allowances
     */
    public function scopeAllowances($query)
    {
        return $query->where('type', self::TYPE_ALLOWANCE);
    }

    /**
     * Scope: Only deductions
     */
    public function scopeDeductions($query)
    {
        return $query->where('type', self::TYPE_DEDUCTION);
    }

    /**
     * Calculate the actual amount based on calculation type and basic salary
     */
    public function calculateAmount(?float $basicSalary = null): float
    {
        if ($this->calculation_type === self::CALC_PERCENTAGE && $basicSalary !== null) {
            return ($basicSalary * $this->amount) / 100;
        }
        return (float) $this->amount;
    }

    /**
     * Get formatted amount display
     */
    public function getFormattedAmountAttribute(): string
    {
        if ($this->calculation_type === self::CALC_PERCENTAGE) {
            return $this->amount . '%';
        }
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    /**
     * Get calculation type label
     */
    public function getCalculationTypeLabelAttribute(): string
    {
        return $this->calculation_type === self::CALC_FIXED ? 'Tetap' : 'Persentase';
    }
}
