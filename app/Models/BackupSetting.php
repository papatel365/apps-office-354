<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupSetting extends Model
{
    use HasFactory;

    protected $table = 'backup_settings';

    protected $fillable = [
        'uuid',
        'company_id',
        'schedule_type',
        'backup_time',
        'backup_day',
        'retention_count',
        'disk',
        'compress',
        'is_enabled',
        'last_backup_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'backup_time' => 'datetime:H:i',
        'backup_day' => 'string',
        'retention_count' => 'integer',
        'compress' => 'boolean',
        'is_enabled' => 'boolean',
        'last_backup_at' => 'datetime',
    ];

    // Schedule type constants
    const SCHEDULE_MANUAL = 'manual';
    const SCHEDULE_DAILY = 'daily';
    const SCHEDULE_WEEKLY = 'weekly';
    const SCHEDULE_MONTHLY = 'monthly';

    // Schedule type options
    const SCHEDULE_TYPES = [
        self::SCHEDULE_MANUAL => 'Manual',
        self::SCHEDULE_DAILY => 'Harian',
        self::SCHEDULE_WEEKLY => 'Mingguan',
        self::SCHEDULE_MONTHLY => 'Bulanan',
    ];

    // Retention options
    const RETENTION_OPTIONS = [
        7 => '7 Backup Terakhir',
        14 => '14 Backup Terakhir',
        30 => '30 Backup Terakhir',
        60 => '60 Backup Terakhir',
        90 => '90 Backup Terakhir',
        365 => '365 Backup Terakhir',
    ];

    // Week days
    const WEEK_DAYS = [
        'monday' => 'Senin',
        'tuesday' => 'Selasa',
        'wednesday' => 'Rabu',
        'thursday' => 'Kamis',
        'friday' => 'Jumat',
        'saturday' => 'Sabtu',
        'sunday' => 'Minggu',
    ];

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Accessors
    public function getScheduleTypeLabelAttribute(): string
    {
        return self::SCHEDULE_TYPES[$this->schedule_type] ?? $this->schedule_type;
    }

    public function getFormattedBackupTimeAttribute(): string
    {
        return \Carbon\Carbon::parse($this->backup_time)->format('H:i') . ' WIB';
    }

    public function getBackupDayLabelAttribute(): ?string
    {
        if (!$this->backup_day) {
            return null;
        }

        return self::WEEK_DAYS[$this->backup_day] ?? $this->backup_day;
    }

    public function getRetentionLabelAttribute(): string
    {
        return self::RETENTION_OPTIONS[$this->retention_count] ?? $this->retention_count . ' Backup';
    }

    public function getNextScheduledRunAttribute(): ?string
    {
        if (!$this->is_enabled || $this->schedule_type === self::SCHEDULE_MANUAL) {
            return null;
        }

        $now = now();
        $backupTime = \Carbon\Carbon::parse($this->backup_time);

        switch ($this->schedule_type) {
            case self::SCHEDULE_DAILY:
                $next = $now->copy()->setTimeFromTimeString($this->backup_time);
                if ($next->isPast()) {
                    $next->addDay();
                }
                return $next->format('d M Y, H:i') . ' WIB';

            case self::SCHEDULE_WEEKLY:
                $targetDay = array_search($this->backup_day, array_keys(self::WEEK_DAYS));
                if ($targetDay === false) {
                    $targetDay = 1;
                }
                $next = $now->copy();
                $next->setTimeFromTimeString($this->backup_time);
                while ($next->dayOfWeek !== $targetDay) {
                    $next->addDay();
                }
                if ($next->isPast()) {
                    $next->addWeek();
                }
                return $next->format('d M Y, H:i') . ' WIB (' . $this->backup_day_label . ')';

            case self::SCHEDULE_MONTHLY:
                $targetDayOfMonth = (int) $this->backup_day ?: 1;
                $next = $now->copy();
                $next->setTimeFromTimeString($this->backup_time);
                $next->day($targetDayOfMonth);
                if ($next->isPast()) {
                    $next->addMonth();
                }
                return $next->format('d M Y, H:i') . ' WIB';

            default:
                return null;
        }
    }

    // Static methods

    /**
     * Get single active company ID for this application.
     *
     * Single Company Rule:
     * - If exactly 1 company exists → return that company ID
     * - Otherwise → return null
     *
     * @return int|null
     */
    protected static function getActiveCompanyId(): ?int
    {
        $count = Company::count();

        if ($count !== 1) {
            return null;
        }

        $company = Company::first();
        return $company ? $company->id : null;
    }

    /**
     * Get or create backup settings for the active single company.
     *
     * Uses single company context: if exactly 1 company exists, use it.
     * Otherwise returns an empty model.
     *
     * @return static
     */
    public static function getOrCreateForCurrentCompany(): static
    {
        $companyId = static::getActiveCompanyId();

        if (!$companyId) {
            return new static();
        }

        return static::getOrCreateForCompany($companyId);
    }

    /**
     * Get or create backup settings for a specific company.
     *
     * @param int|null $companyId
     * @return static
     */
    public static function getOrCreateForCompany(?int $companyId): static
    {
        // If no company ID provided, fall back to active company
        if (!$companyId) {
            return static::getOrCreateForCurrentCompany();
        }

        // Verify company exists before creating settings
        $company = Company::find($companyId);
        if (!$company) {
            // Company doesn't exist - return empty model
            return new static();
        }

        $setting = static::where('company_id', $companyId)->first();

        if (!$setting) {
            $setting = static::create([
                'company_id' => $companyId,
                'schedule_type' => static::SCHEDULE_MANUAL,
                'backup_time' => '01:00',
                'retention_count' => 7,
                'disk' => 'local',
                'compress' => true,
                'is_enabled' => false,
            ]);
        }

        return $setting;
    }

    // Generate UUID before creating
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (BackupSetting $setting) {
            if (empty($setting->uuid)) {
                $setting->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }
}
