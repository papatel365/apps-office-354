<?php

namespace App\Models\HRD;

use App\Core\Traits\BelongsToCompany;
use App\Modules\System\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Salary extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'employee_id',
        'period_month',
        'period_year',
        'basic_salary',
        'allowances',
        'deductions',
        'late_deduction',
        'bpjs_employee',
        'bpjs_company',
        'tax',
        'other_deduction',
        'total_salary',
        'payment_method',
        'payment_date',
        'payment_status',
        'approved_by',
        'approved_at',
        'notes',
        // Bank information
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
    ];

    protected $casts = [
        'period_month' => 'integer',
        'period_year' => 'integer',
        'basic_salary' => 'decimal:2',
        'allowances' => 'decimal:2',
        'deductions' => 'decimal:2',
        'late_deduction' => 'decimal:2',
        'bpjs_employee' => 'decimal:2',
        'bpjs_company' => 'decimal:2',
        'tax' => 'decimal:2',
        'other_deduction' => 'decimal:2',
        'total_salary' => 'decimal:2',
        'payment_date' => 'date',
        'approved_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';

    const CALC_FIXED = 'fixed';
    const CALC_PERCENTAGE = 'percentage';

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get salary components (allowances and deductions)
     */
    public function components(): HasMany
    {
        return $this->hasMany(SalaryComponent::class, 'salary_id');
    }

    /**
     * Get only allowance components
     */
    public function allowanceComponents(): HasMany
    {
        return $this->hasMany(SalaryComponent::class, 'salary_id')->where('type', 'allowance');
    }

    /**
     * Get only deduction components
     */
    public function deductionComponents(): HasMany
    {
        return $this->hasMany(SalaryComponent::class, 'salary_id')->where('type', 'deduction');
    }

    public function scopeForPeriod($q, $year, $month)
    {
        return $q->where('period_year', $year)->where('period_month', $month);
    }

    public function scopePending($q)
    {
        return $q->where('payment_status', self::STATUS_PENDING);
    }

    /**
     * Calculate total allowances from components
     */
    public function getTotalAllowancesFromComponents(): float
    {
        if (!$this->relationLoaded('components')) {
            $this->load('components');
        }
        return $this->components
            ->where('type', SalaryComponent::TYPE_ALLOWANCE)
            ->sum(function ($component) {
                return $component->calculateAmount((float) $this->basic_salary);
            });
    }

    /**
     * Calculate total deductions from components
     */
    public function getTotalDeductionsFromComponents(): float
    {
        if (!$this->relationLoaded('components')) {
            $this->load('components');
        }
        return $this->components
            ->where('type', SalaryComponent::TYPE_DEDUCTION)
            ->sum(function ($component) {
                return $component->calculateAmount((float) $this->basic_salary);
            });
    }

    /**
     * Calculate gross salary (basic + allowances)
     */
    public function getGrossSalaryAttribute(): float
    {
        return (float) $this->basic_salary + (float) $this->allowances;
    }

    /**
     * Calculate total deductions
     */
    public function getTotalDeductionsAttribute(): float
    {
        return (float) $this->deductions
            + (float) $this->late_deduction
            + (float) $this->bpjs_employee
            + (float) $this->tax
            + (float) $this->other_deduction;
    }

    /**
     * Calculate net salary
     */
    public function calculateTotal(): float
    {
        return $this->gross_salary - $this->total_deductions;
    }

    /**
     * Recalculate totals from components and save
     */
    public function recalculateFromComponents(): void
    {
        $basicSalary = (float) $this->basic_salary;

        $totalAllowances = $this->getTotalAllowancesFromComponents();
        $totalDeductions = $this->getTotalDeductionsFromComponents();

        $this->allowances = $totalAllowances;
        $this->deductions = $totalDeductions;
        $this->total_salary = $basicSalary + $totalAllowances - $totalDeductions;

        $this->save();
    }
}
