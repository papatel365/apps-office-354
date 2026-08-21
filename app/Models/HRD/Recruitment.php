<?php

namespace App\Models\HRD;

use App\Core\Traits\BelongsToCompany;
use App\Core\Traits\BelongsToTenant;
use App\Modules\System\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recruitment extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'user_id',
        'department_id',
        'position_id',
        'candidate_name',
        'email',
        'phone',
        'address',
        'gender',
        'age',
        'education',
        'major',
        'institution',
        'experience_years',
        'current_company',
        'current_position',
        'skills',
        'notes',
        'source',
        'stage',
        'rejection_reason',
        'expected_salary',
        'offered_salary',
        'interview_date',
        'interview_time',
        'offer_date',
        'hire_date',
        'join_date',
        'interview_notes',
        'offer_notes',
    ];

    protected function casts(): array
    {
        return [
            'interview_date' => 'date',
            'interview_time' => 'datetime:H:i',
            'offer_date' => 'date',
            'hire_date' => 'date',
            'join_date' => 'date',
            'expected_salary' => 'decimal:2',
            'offered_salary' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // =====================================================
    // CONSTANTS
    // =====================================================

    const STAGE_NEW = 'new';
    const STAGE_SCREENING = 'screening';
    const STAGE_INTERVIEW_HR = 'interview_hr';
    const STAGE_INTERVIEW_USER = 'interview_user';
    const STAGE_INTERVIEW_DIRECTOR = 'interview_director';
    const STAGE_PSIKOTEST = 'psikotest';
    const STAGE_MEDICAL_CHECKUP = 'medical_checkup';
    const STAGE_OFFER = 'offer';
    const STAGE_HIRING = 'hiring';
    const STAGE_REJECTED = 'rejected';

    // =====================================================
    // RELATIONSHIPS
    // =====================================================

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // =====================================================
    // SCOPES
    // =====================================================

    public function scopeActive($query)
    {
        return $query->whereNotIn('stage', [self::STAGE_HIRING, self::STAGE_REJECTED]);
    }

    public function scopeByStage($query, string $stage)
    {
        return $query->where('stage', $stage);
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

    public function getStageLabelAttribute(): string
    {
        $labels = [
            self::STAGE_NEW => 'Baru',
            self::STAGE_SCREENING => 'Screening',
            self::STAGE_INTERVIEW_HR => 'Interview HR',
            self::STAGE_INTERVIEW_USER => 'Interview User',
            self::STAGE_INTERVIEW_DIRECTOR => 'Interview Director',
            self::STAGE_PSIKOTEST => 'Psikotes',
            self::STAGE_MEDICAL_CHECKUP => 'Medical Checkup',
            self::STAGE_OFFER => 'Penawaran',
            self::STAGE_HIRING => 'Diterima',
            self::STAGE_REJECTED => 'Ditolak',
        ];

        return $labels[$this->stage] ?? $this->stage;
    }

    public function getIsActiveAttribute(): bool
    {
        return !in_array($this->stage, [self::STAGE_HIRING, self::STAGE_REJECTED]);
    }
}
