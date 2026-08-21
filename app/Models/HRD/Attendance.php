<?php

namespace App\Models\HRD;

use App\Core\Traits\BelongsToCompany;
use App\Traits\NotifiableActivity;
use App\Helpers\IndonesiaTimezoneHelper;
use App\Modules\System\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use BelongsToCompany;
    use NotifiableActivity;

    protected $fillable = [
        'user_id',
        'employee_id',
        'company_id',
        'placement_id',
        'date',
        'check_in',
        'check_in_time',
        'check_out',
        'check_out_time',

        // Check-in timezone info
        'check_in_timezone',
        'check_in_timezone_name',
        'check_in_timezone_offset',
        'check_in_province',
        'check_in_city',

        // Check-out timezone info
        'check_out_timezone',
        'check_out_timezone_name',
        'check_out_timezone_offset',
        'check_out_province',
        'check_out_city',

        // Photos
        'check_in_photo',
        'check_out_photo',

        // GPS coordinates
        'check_in_latitude',
        'check_in_longitude',
        'check_in_address',
        'check_in_gps_accuracy',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_address',
        'check_out_gps_accuracy',

        // Distance & Radius
        'distance_meters',
        'is_outside_radius',

        // Location
        'attendance_location_name',

        // Shift
        'shift_id',
        'shift_name',
        'shift_start',
        'shift_end',

        // Status
        'status',
        'late_minutes',
        'early_leave_minutes',
        'overtime_minutes',
        'working_hours',

        // Verification
        'is_face_verified',
        'is_location_verified',
        'face_verification_score',
        'face_landmarks',
        'is_suspicious',
        'suspicious_reasons',

        // Device info
        'check_in_ip',
        'check_in_device',
        'check_in_browser',
        'check_in_os',
        'check_out_ip',
        'check_out_device',
        'check_out_browser',
        'check_out_os',

        // Notes & Approval
        'notes',
        'approved_by',
        'approved_at',
        'approval_notes',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'check_in_gps_accuracy' => 'decimal:2',
        'check_out_gps_accuracy' => 'decimal:2',
        'distance_meters' => 'decimal:2',
        'late_minutes' => 'integer',
        'early_leave_minutes' => 'integer',
        'overtime_minutes' => 'integer',
        'working_hours' => 'decimal:2',
        'is_face_verified' => 'boolean',
        'is_location_verified' => 'boolean',
        'is_outside_radius' => 'boolean',
        'face_verification_score' => 'decimal:4',
        'face_landmarks' => 'array',
        'is_suspicious' => 'boolean',
        'suspicious_reasons' => 'array',
        'shift_start' => 'datetime',
        'shift_end' => 'datetime',
        'approved_at' => 'datetime',
    ];

    const STATUS_PRESENT = 'present';
    const STATUS_LATE = 'late';
    const STATUS_ABSENT = 'absent';
    const STATUS_LEAVE = 'leave';
    const STATUS_ONTIME = 'ontime';
    const STATUS_SUSPICIOUS = 'suspicious';
    const STATUS_OUTSIDE_AREA = 'outside_area';

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class, 'placement_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getCompanyId(): ?int
    {
        return $this->company_id ?? $this->employee?->company_id;
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

    public function scopeSuspicious($q)
    {
        return $q->where('is_suspicious', true);
    }

    public function scopeLate($q)
    {
        return $q->where('late_minutes', '>', 0);
    }

    public function scopePresent($q)
    {
        return $q->whereIn('status', [self::STATUS_PRESENT, self::STATUS_ONTIME, self::STATUS_LATE]);
    }

    public function scopeOutsideRadius($q)
    {
        return $q->where('is_outside_radius', true);
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->is_suspicious) return 'Mencurigakan';
        if ($this->is_outside_radius) return 'Di Luar Area';
        if ($this->late_minutes > 0) return 'Terlambat ' . $this->late_minutes . ' menit';
        if ($this->status === self::STATUS_ONTIME) return 'Tepat Waktu';
        return ucfirst($this->status);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        if ($this->is_suspicious) return 'bg-red-100 text-red-700';
        if ($this->is_outside_radius) return 'bg-orange-100 text-orange-700';
        if ($this->late_minutes > 0) return 'bg-yellow-100 text-yellow-700';
        if ($this->is_face_verified === false) return 'bg-gray-100 text-gray-700';
        return 'bg-green-100 text-green-700';
    }

    // =====================================================
    // Timezone-Aware Formatted Times
    // =====================================================

    /**
     * Get check-in time formatted with timezone (e.g., "09:15:10 WITA")
     */
    public function getCheckInWithTimezoneAttribute(): string
    {
        if (!$this->check_in_time) {
            return '-';
        }

        $timezone = $this->check_in_timezone ?? 'Asia/Jakarta';
        return IndonesiaTimezoneHelper::formatTimeWithTimezone(
            $this->check_in_time,
            $timezone,
            'H:i:s'
        );
    }

    /**
     * Get check-out time formatted with timezone (e.g., "17:05:33 WITA")
     */
    public function getCheckOutWithTimezoneAttribute(): string
    {
        if (!$this->check_out_time) {
            return '-';
        }

        $timezone = $this->check_out_timezone ?? 'Asia/Jakarta';
        return IndonesiaTimezoneHelper::formatTimeWithTimezone(
            $this->check_out_time,
            $timezone,
            'H:i:s'
        );
    }

    /**
     * Get check-in time in user's timezone (legacy accessor)
     */
    public function getCheckInFormattedAttribute(): string
    {
        return $this->check_in_with_timezone;
    }

    /**
     * Get check-out time in user's timezone (legacy accessor)
     */
    public function getCheckOutFormattedAttribute(): string
    {
        return $this->check_out_with_timezone;
    }

    /**
     * Get timezone badge for display
     */
    public function getTimezoneBadgeAttribute(): ?string
    {
        if (!$this->check_in_timezone_name) {
            return null;
        }

        return $this->check_in_timezone_name;
    }

    /**
     * Get location display with timezone
     */
    public function getLocationDisplayAttribute(): string
    {
        $parts = [];

        if ($this->check_in_city) {
            $parts[] = $this->check_in_city;
        }

        if ($this->check_in_province) {
            $parts[] = $this->check_in_province;
        }

        if ($this->check_in_timezone_name) {
            $parts[] = $this->check_in_timezone_name;
        }

        return implode(' • ', $parts) ?: '-';
    }

    /**
     * Get formatted time for display with badge
     */
    public function getFormattedTimeRangeAttribute(): string
    {
        $checkIn = $this->check_in_with_timezone;
        $checkOut = $this->check_out_with_timezone;

        if ($checkIn === '-' && $checkOut === '-') {
            return '-';
        }

        if ($checkIn !== '-' && $checkOut !== '-') {
            return $checkIn . ' - ' . $checkOut;
        }

        return $checkIn !== '-' ? $checkIn : $checkOut;
    }

    // =====================================================
    // Helper Methods
    // =====================================================

    /**
     * Set timezone info from address (reverse geocoding result)
     */
    public function setTimezoneFromAddress(string $type, ?string $address, ?string $city = null, ?string $province = null): void
    {
        $timezoneField = $type . '_timezone';
        $nameField = $type . '_timezone_name';
        $offsetField = $type . '_timezone_offset';
        $cityField = $type . '_city';
        $provinceField = $type . '_province';

        // Determine timezone from province first, then city, then address
        $timezone = IndonesiaTimezoneHelper::getTimezoneFromProvince($province);

        if ($timezone === 'Asia/Jakarta' && $city) {
            $timezone = IndonesiaTimezoneHelper::getTimezoneFromCity($city);
        }

        if ($timezone === 'Asia/Jakarta' && $address) {
            $timezone = IndonesiaTimezoneHelper::getTimezoneFromAddress($address);
        }

        $timezoneInfo = IndonesiaTimezoneHelper::getTimezoneInfo($timezone);

        $this->$timezoneField = $timezone;
        $this->$nameField = $timezoneInfo['name'];
        $this->$offsetField = $timezoneInfo['offset'];

        if ($city) {
            $this->$cityField = $city;
        }

        if ($province) {
            $this->$provinceField = $province;
        }
    }

    public function getDistanceFormattedAttribute(): ?string
    {
        if (!$this->distance_meters) return null;
        if ($this->distance_meters < 1000) {
            return round($this->distance_meters) . ' m';
        }
        return round($this->distance_meters / 1000, 1) . ' km';
    }

    public function markAsSuspicious(array $reasons): void
    {
        $this->update([
            'is_suspicious' => true,
            'suspicious_reasons' => array_merge($this->suspicious_reasons ?? [], $reasons),
        ]);
    }

    public function approve(User $user, ?string $notes = null): bool
    {
        if ($this->approved_by) return false;

        $this->update([
            'approved_by' => $user->id,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);

        return true;
    }

    public function calculateWorkingHours(): float
    {
        if (!$this->check_in_time || !$this->check_out_time) return 0;

        $minutes = $this->check_in_time->diffInMinutes($this->check_out_time);
        $breakMinutes = 60; // Default 1 hour break
        return max(0, ($minutes - $breakMinutes) / 60);
    }

    public function calculateLateMinutes(): int
    {
        if (!$this->check_in_time || !$this->shift_start) return 0;

        $shiftStartMinutes = $this->shift_start->format('H') * 60 + $this->shift_start->format('i');
        $checkInMinutes = $this->check_in_time->format('H') * 60 + $this->check_in_time->format('i');

        $diff = $checkInMinutes - $shiftStartMinutes;
        $gracePeriod = 5; // 5 minutes grace period

        return max(0, $diff - $gracePeriod);
    }

    /**
     * Calculate distance from a given location using Haversine formula.
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // Earth's radius in meters

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Check if attendance location is within allowed radius.
     */
    public function checkRadiusViolation(?float $lat, ?float $lng): bool
    {
        if (!$lat || !$lng || !$this->placement) {
            return false;
        }

        if (!$this->placement->latitude || !$this->placement->longitude) {
            return false;
        }

        $distance = $this->calculateDistance(
            $lat, $lng,
            $this->placement->latitude,
            $this->placement->longitude
        );

        $this->distance_meters = $distance;
        $this->is_outside_radius = $distance > $this->placement->radius_meters;

        return $this->is_outside_radius;
    }

    /**
     * Set face verification data.
     */
    public function setFaceVerification(float $score, ?array $landmarks = null): void
    {
        $this->face_verification_score = $score;
        $this->face_landmarks = $landmarks;
        $this->is_face_verified = $score >= 0.8; // Threshold of 80%
    }

    /**
     * Get location verification status.
     */
    public function getLocationStatusAttribute(): string
    {
        if ($this->is_outside_radius) {
            return 'Di Luar Radius (' . $this->distance_formatted . ')';
        }
        if ($this->placement) {
            return 'Sesuai (' . $this->distance_formatted . ')';
        }
        return 'Tidak Diketahui';
    }

    /**
     * Get face verification status.
     */
    public function getFaceStatusAttribute(): string
    {
        if ($this->is_face_verified) {
            return 'Terverifikasi (' . round($this->face_verification_score * 100) . '%)';
        }
        if ($this->face_verification_score !== null) {
            return 'Gagal (' . round($this->face_verification_score * 100) . '%)';
        }
        return 'Belum Ada';
    }
}
