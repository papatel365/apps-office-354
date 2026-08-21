<?php

namespace App\Models\HRD;

use App\Core\Traits\BelongsToCompany;
use App\Modules\System\Models\User;
use App\Traits\NotifiableActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeProfile extends Model
{
    use BelongsToCompany;
    use NotifiableActivity;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_id',
        'employee_number',
        'full_name',
        'nick_name',
        'gender',
        'birth_date',
        'birth_place',
        'phone',
        'mobile',
        'address',
        'city',
        'province',
        'postal_code',
        'ktp_number',
        'ktp_address',
        'npwp_number',
        'bpjs_kesehatan',
        'bpjs_number',
        'bpjs_ketenagakerjaan',
        'blood_type',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'bank_account_holder',
        'photo',
        'signature',
        'department_id',
        'division_id',
        'position_id',
        'supervisor_id',
        'placement_id',
        'placement_name',
        'join_date',
        'probation_end_date',
        'contract_end_date',
        'contract_end',
        'employee_type_id',
        'employment_type',
        'employment_status',
        'marital_status',
        'punya_anak',
        'jumlah_anak',
        'religion',
        'education',
        'institution',
        'graduation_year',
        'previous_company',
        'notes',
        'is_active',
        'resign_date',
        'resign_reason',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'join_date' => 'date',
        'probation_end_date' => 'date',
        'contract_end_date' => 'date',
        'contract_end' => 'date',
        'resign_date' => 'date',
        'is_active' => 'boolean',
        'punya_anak' => 'boolean',
        'jumlah_anak' => 'integer',
    ];

    // Constants
    const TYPE_PERMANENT = 'permanent';
    const TYPE_CONTRACT = 'contract';
    const TYPE_PROBATION = 'probation';
    const TYPE_PART_TIME = 'part_time';
    const TYPE_INTERN = 'intern';
    const TYPE_OUTSOURCE = 'outsource';

    const STATUS_PERMANENT = 'permanent';
    const STATUS_CONTRACT = 'contract';
    const STATUS_PROBATION = 'probation';
    const STATUS_INTERN = 'intern';
    const STATUS_OUTSOURCE = 'outsource';

    const GENDER_MALE = 'male';
    const GENDER_FEMALE = 'female';

    // Accessors for legacy compatibility
    public function getNikAttribute()
    {
        return $this->ktp_number;
    }

    public function setNikAttribute($value)
    {
        $this->attributes['ktp_number'] = $value;
    }

    public function getPlaceOfBirthAttribute()
    {
        return $this->birth_place;
    }

    public function setPlaceOfBirthAttribute($value)
    {
        $this->attributes['birth_place'] = $value;
    }

    public function getDateOfBirthAttribute()
    {
        return $this->birth_date;
    }

    public function setDateOfBirthAttribute($value)
    {
        $this->attributes['birth_date'] = $value;
    }

    public function getBankAccountAttribute()
    {
        return $this->bank_account_number;
    }

    public function setBankAccountAttribute($value)
    {
        $this->attributes['bank_account_number'] = $value;
    }

    public function getBankAccountNameAttribute()
    {
        return $this->bank_account_holder;
    }

    public function setBankAccountNameAttribute($value)
    {
        $this->attributes['bank_account_holder'] = $value;
    }

    public function getBasicSalaryAttribute()
    {
        // Get from latest salary record
        return $this->salaries()->latest()->first()?->basic_salary ?? 0;
    }

    public function getAllowancesAttribute()
    {
        // Get from latest salary record
        return $this->salaries()->latest()->first()?->allowances ?? 0;
    }

    public function getDeductionsAttribute()
    {
        // Get total deductions from latest salary record
        $latestSalary = $this->salaries()->latest()->first();
        if (!$latestSalary) return 0;
        return (float) $latestSalary->deductions
            + (float) $latestSalary->late_deduction
            + (float) $latestSalary->bpjs_employee
            + (float) $latestSalary->tax
            + (float) $latestSalary->other_deduction;
    }

    public function getNetSalaryAttribute()
    {
        return $this->basic_salary + $this->allowances - $this->deductions;
    }

    public function getLatestSalaryBankNameAttribute()
    {
        return $this->salaries()->latest()->first()?->bank_name ?? '-';
    }

    public function getLatestSalaryAccountAttribute()
    {
        $account = $this->salaries()->latest()->first()?->bank_account_number;
        if (!$account) return '-';
        return substr($account, 0, 4) . '****' . substr($account, -4);
    }

    public function getLatestSalaryAccountNameAttribute()
    {
        return $this->salaries()->latest()->first()?->bank_account_holder ?? '-';
    }

    public function getLeaveBalanceAttribute()
    {
        // Calculate from leave entitlements
        return $this->getAvailableLeaveBalance();
    }

    public function getAvailableLeaveBalance(): float
    {
        $entitled = $this->leaveEntitlements()->sum('entitled_days');
        $used = $this->leaves()
            ->whereIn('status', ['approved'])
            ->whereYear('start_date', now()->year)
            ->sum('total_days');
        return max(0, $entitled - $used);
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if this employee has a linked user account
     */
    public function hasUserAccount(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Check if this employee can perform self face attendance
     * Requirements:
     * 1. Has a linked user account
     * 2. Is active
     */
    public function canSelfAttendance(): bool
    {
        return $this->hasUserAccount() && $this->is_active;
    }

    /**
     * Get the user account status label
     */
    public function getUserAccountStatusLabelAttribute(): string
    {
        if (!$this->hasUserAccount()) {
            return 'Belum Terhubung';
        }

        if (!$this->is_active) {
            return 'Nonaktif';
        }

        return 'Terhubung';
    }

    /**
     * Get the user account status badge class
     */
    public function getUserAccountStatusBadgeClassAttribute(): string
    {
        if (!$this->hasUserAccount()) {
            return 'bg-amber-100 text-amber-700';
        }

        if (!$this->is_active) {
            return 'bg-red-100 text-red-700';
        }

        return 'bg-green-100 text-green-700';
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(\App\Models\HRD\Department::class, 'department_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Division::class, 'division_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(\App\Models\HRD\Position::class, 'position_id');
    }

    public function employeeType(): BelongsTo
    {
        return $this->belongsTo(\App\Models\HRD\EmployeeType::class, 'employee_type_id');
    }

    /**
     * Get the supervisor of this employee (self-referencing relationship).
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\HRD\EmployeeProfile::class, 'supervisor_id');
    }

    /**
     * Get the subordinates (employees) under this employee.
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(\App\Models\HRD\EmployeeProfile::class, 'supervisor_id');
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(\App\Models\HRD\Placement::class, 'placement_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(\App\Models\HRD\EmployeeDocument::class, 'employee_id');
    }

    public function leaves(): HasMany
    {
        // Join through user_id since leaves table uses user_id
        return $this->hasMany(\App\Models\HRD\Leave::class, 'user_id', 'user_id');
    }

    public function leaveEntitlements(): HasMany
    {
        return $this->hasMany(\App\Models\HRD\LeaveEntitlement::class, 'employee_id');
    }

    public function attendances(): HasMany
    {
        // Join through user_id
        return $this->hasMany(\App\Models\HRD\Attendance::class, 'user_id', 'user_id');
    }

    public function overtimes(): HasMany
    {
        return $this->hasMany(\App\Models\HRD\Overtime::class, 'user_id', 'user_id');
    }

    public function salaries(): HasMany
    {
        return $this->hasMany(\App\Models\HRD\Salary::class, 'employee_id');
    }

    /**
     * Get sidebar permissions for this employee.
     */
    public function sidebarPermissions(): HasMany
    {
        return $this->hasMany(\App\Models\HRD\SidebarPermission::class, 'employee_id');
    }

    /**
     * Check if this employee has custom sidebar permissions set.
     */
    public function hasCustomSidebarPermissions(): bool
    {
        return $this->sidebarPermissions()->exists();
    }

    /**
     * Get enabled menu keys for this employee.
     */
    public function getEnabledSidebarPermissions(): array
    {
        return $this->sidebarPermissions()
            ->enabled()
            ->pluck('menu_key')
            ->toArray();
    }

    // Scopes
    public function scopeActive($q)
    {
        // Use qualified column name to avoid ambiguity when joining with users table
        return $q->where('employee_profiles.is_active', true);
    }

    public function scopeContract($q)
    {
        return $q->where('employment_type', self::TYPE_CONTRACT);
    }

    public function scopeExpiringContract($q, $days = 30)
    {
        return $q->where('employment_type', self::TYPE_CONTRACT)
            ->whereNotNull('contract_end')
            ->whereBetween('contract_end', [now(), now()->addDays($days)]);
    }

    public function scopeNewThisMonth($q)
    {
        return $q->whereMonth('join_date', now()->month)
            ->whereYear('join_date', now()->year);
    }

    // Get full name attribute - KEMBALIKAN KE DATABASE VALUE
    // JANGAN ambil dari user.name karena itu field terpisah
    public function getFullNameAttribute(): string
    {
        return $this->attributes['full_name'] ?? 'Unknown';
    }

    public function getNameAttribute(): string
    {
        return $this->full_name;
    }

    /**
     * Accessor for contract_start - maps to contract_end column
     * Used by wizard form which sends contract_start
     */
    public function getContractStartAttribute()
    {
        return $this->contract_end;
    }

    // Contract helpers
    public function getIsContractExpiredAttribute(): bool
    {
        return $this->contract_end && $this->contract_end->isPast();
    }

    public function getContractDaysRemainingAttribute(): int
    {
        if (!$this->contract_end) return 0;
        return max(0, now()->diffInDays($this->contract_end));
    }

    public function getEmploymentTypeLabelAttribute(): string
    {
        return match($this->employment_type) {
            self::TYPE_PERMANENT => 'Pegawai Tetap',
            self::TYPE_CONTRACT => 'Kontrak',
            self::TYPE_PROBATION => 'Probation',
            self::TYPE_PART_TIME => 'Part-time',
            self::TYPE_INTERN => 'Magang',
            self::TYPE_OUTSOURCE => 'Outsource',
            default => ucfirst($this->employment_type ?? '-'),
        };
    }

    public function getGenderLabelAttribute(): string
    {
        return match($this->gender) {
            self::GENDER_MALE => 'Laki-laki',
            self::GENDER_FEMALE => 'Perempuan',
            default => '-',
        };
    }

    public function getMaritalStatusLabelAttribute(): string
    {
        return match($this->marital_status) {
            'single' => 'Belum Menikah',
            'married' => 'Menikah',
            'divorced' => 'Cerai',
            'widowed' => 'Duda/Janda',
            default => '-',
        };
    }
}
